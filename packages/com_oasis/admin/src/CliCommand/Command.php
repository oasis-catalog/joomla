<?php

namespace Oasiscatalog\Component\Oasis\Administrator\CliCommand;

defined('_JEXEC') or die;

use Exception;
use Joomla\CMS\Language\Text;
use Joomla\CMS\MVC\Factory\MVCFactoryAwareTrait;
use Oasiscatalog\Component\Oasis\Administrator\Helper\Config as OasisConfig;
use Oasiscatalog\Component\Oasis\Administrator\Helper\OasisHelper;
use Oasiscatalog\Component\Oasis\Administrator\Model\OasisModel;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;

/**
 * @package     Oasis
 * @subpackage  Administrator
 *
 * @author      Viktor G. <ever2013@mail.ru>
 * @copyright   Copyright (C) 2023 Oasiscatalog. All rights reserved.
 * @license     GNU/GPLv3 http://www.gnu.org/licenses/gpl-3.0.html
 * @link        https://www.oasiscatalog.com/
 * @since 4.0
 */
class Command extends \Joomla\Console\Command\AbstractCommand
{
    use MVCFactoryAwareTrait;

    private OasisConfig $cf;

    /**
     * The default command name
     *
     * @var    string
     * @since 4.0
     */
    protected static $defaultName = 'oasis:import';

    /**
     * Model class
     *
     * @var    null
     *
     * @since 4.0
     */
    private $model = null;

    /**
     * Categories oasis
     *
     * @var null
     *
     * @since 4.0
     */
    private $categories = null;

    /**
     * Products oasis
     *
     * @var null
     *
     * @since 4.0
     */
    private $products = null;

    /**
     * Manufacturers oasis
     *
     * @var null
     *
     * @since 4.0
     */
    private $manufacturers = null;

    /**
     * Custom Field Id - Multi Variant
     *
     * @var null
     *
     * @since 4.0
     */
    private $customFields = null;

    /**
     * Configure the command.
     *
     * @return  void
     * @since 4.0
     */
    protected function configure(): void
    {
        $this->setDescription(Text::_('COM_OASIS_XML_DESCRIPTION'));
        $this->setHelp(Text::_('COM_OASIS_CLI_HELP'));
        $this->addOption('key', '', InputOption::VALUE_REQUIRED, Text::_('COM_OASIS_CLI_KEY_DESC'));
        $this->addOption('up', '', InputOption::VALUE_NONE, Text::_('COM_OASIS_CLI_UP_DESC'));
        $this->addOption('debug', '', InputOption::VALUE_NONE, 'Console debug');
        $this->addOption('debug_log', '', InputOption::VALUE_NONE, 'Debug log');
    }

    /**
     * @inheritDoc
     * @since 4.0
     */
    protected function doExecute(InputInterface $input, OutputInterface $output): int
    {
        set_time_limit(0);
        ini_set('memory_limit', '4G');
        $this->cf = OasisConfig::instance([
            'debug' =>      !empty($input->getOption('debug')),
            'debug_log' =>  !empty($input->getOption('debug_log'))
        ]);
        $this->cf->lock(\Closure::bind(function() use ($input, $output) {
            $this->cf->configureSymfonyIO($input, $output);
            $this->cf->init();
            $this->cf->initRelation();

            if (empty($this->cf->api_key)) {
                $this->cf->log(Text::_('COM_OASIS_CLI_NOT_API_KEY'), 'warn');
                return 1;
            } elseif (empty($input->getOption('key'))) {
                $this->cf->log(Text::_('COM_OASIS_CLI_NOT_KEY'), 'warn');
                return 1;
            } elseif (!$this->cf->checkCronKey($input->getOption('key'))) {
                $this->cf->log(Text::_('COM_OASIS_CLI_INVALID_KEY'), 'warn');
                return 1;
            }
            if (!$this->cf->checkPermissionImport()) {
                $this->cf->log('Import once day', 'warn');
                return 1;
            }

            $this->model = new OasisModel();

            try {
                $start_time = microtime(true);

                if (!$input->getOption('up')) {
                    $this->upProduct();
                } else {
                    $this->upStock();
                }

                $end_time = microtime(true);
                $this->cf->log('Время выполнения скрипта: ' . ($end_time - $start_time) . ' сек.', 'success');
            } catch (Exception $e) {
                echo $e->getMessage() . PHP_EOL;
                exit($e->getCode());
            }
        }, $this), \Closure::bind(function() {
            $this->cf->log('Already running');
        }, $this));
        return 0;
    }

    /**
     * Update product stock
     *
     * @since 4.0
     */
    private function upProduct()
    {
        $this->cf->log('Start import/update products');

        $args = [];
        if ($this->cf->limit > 0) {
            $args['limit'] = $this->cf->limit;
            $args['offset'] = $this->cf->progress['step'] * $this->cf->limit;
        }

        $this->categories = OasisHelper::getOasisCategories();
        $this->products = OasisHelper::getOasisProducts($args);

        $this->manufacturers = OasisHelper::getOasisManufacturers();
        $this->customFields = $this->checkVirtuemartCustoms();

        $count = count($this->products);
        $stat = OasisHelper::getOasisStat();

        $this->cf->progressStart($stat->products, $count);

        $group_ids = [];

        foreach ($this->products as $product) {
            $group_ids[$product->group_id][$product->id] = $product;
        }

        if ($group_ids) {
            foreach ($group_ids as $group_id => $products) {
                $this->cf->log('Начало обработки модели '.$this->cf->progress['step_total'].'-'.($this->cf->progress['step_item'] + 1));

                if (count($products) === 1) {
                    $product = reset($products);

                    $dbProductId = $this->model->getData('#__virtuemart_products', ['virtuemart_product_id'], [
                        'product_parent_id' => 0,
                        'product_sku'       => $product->article,
                    ]);

                    if (is_null($dbProductId)) {
                        $dbProductId = $this->addProduct($product);

                        $this->cf->log('OAId='.$product->id.' add JId=' . $dbProductId);
                    } else {
                        $this->editProduct($dbProductId, $product);
                        $this->cf->log('OAId='.$product->id.' updated JId=' . $dbProductId);
                    }
                    $this->cf->progressUp();
                } else {
                    $firstProduct = reset($products);
                    $dbFirstProductId = $this->model->getData('#__virtuemart_products', ['virtuemart_product_id'], [
                        'product_parent_id' => 0,
                        'product_sku'       => $firstProduct->article,
                    ]);

                    if (is_null($dbFirstProductId)) {
                        $dbFirstProductId = $this->addProduct($firstProduct, true);
                        $this->cf->log('Parent OAId='.$firstProduct->id.' add JId=' . $dbFirstProductId);
                    } else {
                        $this->editProduct($dbFirstProductId, $firstProduct, true);
                        $this->cf->log('Parent OAId='.$firstProduct->id.' updated JId=' . $dbFirstProductId);
                    }

                    foreach ($products as $product) {
                        $dbProductId = $this->model->getData('#__virtuemart_products', ['virtuemart_product_id'], [
                            'product_parent_id' => $dbFirstProductId,
                            'product_sku'       => $product->article,
                        ]);

                        if (is_null($dbProductId)) {
                            $dbProductId = $this->addProductChild($dbFirstProductId, $product);
                            $this->cf->log('  Child OAId='.$product->id.' add JId=' . $dbProductId);
                        } else {
                            $this->editProductChild($dbProductId, $product);
                            $this->cf->log('  Child OAId='.$product->id.' edit JId=' . $dbProductId);
                        }
                        $this->cf->progressUp();
                    }
                }
            }
        }

        $productsOasis = $this->model->getData('#__oasis_product', ['article'], [], false, 'loadAssocList');

        foreach ($productsOasis as $productOasis) {
            if (is_null($this->model->getData('#__virtuemart_products', ['virtuemart_product_id'], ['product_sku' => $productOasis['article']]))) {
                $this->model->deleteData('#__oasis_product', ['article' => $productOasis['article']]);
            }
        }

        $this->cf->progressEnd();
    }


    /**
     * Update product stock
     *
     * @since 4.0
     */
    private function upStock()
    {
        $this->cf->log('Start update stock');
        //TODO проверить обновление остатков
        $stock = OasisHelper::getOasisStock();

        foreach ($stock as $item) {
            $oasisProduct = $this->model->getData('#__oasis_product', ['rating', 'product_id'], ['product_id_oasis' => $item->id], false, 'loadAssoc');

            if (!is_null($oasisProduct) && (int)$oasisProduct['rating'] !== 5) {
                $this->model->upData('#__virtuemart_products', ['virtuemart_product_id' => $oasisProduct['product_id']], ['product_in_stock' => intval($item->stock)]);
            }
        }
        unset($item, $oasisProduct);
    }


    /**
     * @param      $product
     * @param bool $firstProduct
     * @return int
     *
     * @since 4.0
     */
    public function addProduct($product, bool $firstProduct = false): int
    {
        // add product
        $product_id = $this->addVirtuemartProducts($product);

        // add product in table default lang virtuemart
        $this->virtuemartProductsLang($product_id, $product);

        // categories
        $this->virtuemartProductCategories($product_id, $product);

        // manufacturer
        $this->virtuemartProductManufacturers($product_id, $product);

        // images
        $this->processingProductMedias($product_id, $product);

        // prices
        $this->virtuemartProductPrices($product_id, $product);

        // first product for children
        if ($firstProduct) {
            $data['customfield_params'] = $this->encodeCustomfieldParams([
                'selectoptions' => [],
                'options'       => [
                    $product_id => ['', '']
                ]
            ]);

            $this->addProductCustomfields($product_id, $data);
        } else {
            // add table oasis product
            $this->oasisProduct($product_id, $product);
        }

        return $product_id;
    }

    /**
     * @param       $product_Id
     * @param       $product
     * @param bool $firstProduct
     *
     * @since 4.0
     */
    public function editProduct($product_Id, $product, bool $firstProduct = false)
    {
        $this->editVirtuemartProducts($product_Id, $product);
        $this->virtuemartProductsLang($product_Id, $product, false, true);

        if(!$this->cf->is_not_up_cat)
            $this->virtuemartProductCategories($product_Id, $product);

        if($this->cf->is_up_photo) {
            $vmMedias = $this->model->getData('#__virtuemart_product_medias', ['virtuemart_media_id'], ['virtuemart_product_id' => $product_Id], false, 'loadAssocList');
            foreach ($vmMedias as $vmMedia) {
                $this->model->deleteData('#__virtuemart_medias', ['virtuemart_media_id' => $vmMedia['virtuemart_media_id']]);
                $this->model->deleteData('#__virtuemart_product_medias', [ 'virtuemart_media_id' => $vmMedia['virtuemart_media_id']]);
            }

            $this->processingProductMedias($product_Id, $product);
        }

        $this->virtuemartProductManufacturers($product_Id, $product);
        $this->virtuemartProductPrices($product_Id, $product);

        if ($firstProduct == false) {
            $this->oasisProduct($product_Id, $product);
        }
    }

    /**
     * @param $parent_id
     * @param $product
     * @return int
     *
     * @since 4.0
     */
    public function addProductChild($parent_id, $product): int
    {
        // add product
        $dataProducts = [
            'product_parent_id' => $parent_id,
            'product_in_stock'  => $product->rating === 5 ? 1000000 : intval($product->total_stock),
            'has_categories'    => 0,
            'has_manufacturers' => 0,
            'has_medias'        => 1,
            'has_prices'        => 1,
        ];

        if (is_null($product->total_stock) && $product->rating !== 5) {
            $dataProducts['published'] = 0;
        }

        $product_id = $this->addVirtuemartProducts($product, $dataProducts);

        // images
        $this->processingProductMedias($product_id, $product, true);

        // add product in table default lang virtuemart
        $this->virtuemartProductsLang($product_id, $product, true);
        $this->virtuemartProductPrices($product_id, $product);
        $this->addСhildProductCustomFields($parent_id, $product_id, $product);

        // add table oasis product
        $this->oasisProduct($product_id, $product);

        return $product_id;
    }

    /**
     * @param $product_id
     * @param $product
     *
     * @since 4.0
     */
    public function editProductChild($product_id, $product)
    {
        $dataProducts = [
            'product_in_stock' => $product->rating === 5 ? 1000000 : intval($product->total_stock),
        ];

        if (is_null($product->total_stock) && $product->rating !== 5) {
            $dataProducts['published'] = 0;
        }

        if($this->cf->is_up_photo) {
            $this->processingProductMedias($product_id, $product, true);
        }

        // add table oasis product
        $this->oasisProduct($product_id, $product);

        if (!is_null($product_id)) {
            $this->editVirtuemartProducts($product_id, $product, $dataProducts);
            $this->virtuemartProductsLang($product_id, $product, true, true);
            $this->virtuemartProductPrices($product_id, $product);
        }
    }

    /**
     * @param $parent_id
     * @param $product_id
     * @param $product
     *
     * @since 4.0
     */
    public function addСhildProductCustomFields($parent_id, $product_id, $product)
    {
        $data = [];

        foreach ($product->attributes as $attribute) {
            if (isset($attribute->id) && $attribute->id === 1000000001) {
                $data['Цвет'] = htmlspecialchars($attribute->value, ENT_QUOTES);
            }
        }
        unset($attribute);

        if (!empty($product->size)) {
            $data['Размер'] = htmlspecialchars($product->size, ENT_QUOTES);
        }

        $parentCustomFields = $this->getProductCustomfieldParams($parent_id);
        $parentCustomParams = $this->decodeCustomfieldParams($parentCustomFields['customfield_params']);
        $options = (array)$parentCustomParams['options'];

        foreach ($data as $key => $item) {
            $exist = false;

            foreach ($parentCustomParams['selectoptions'] as $param) {
                if ($param->clabel == $key) {
                    $values = preg_split('/\r\n?/', $param->values);

                    if (array_search($item, $values) === false) {
                        $values[] = $item;
                        $param->values = implode(PHP_EOL, $values);
                    }

                    $exist = true;
                }
            }

            if (!$exist) {
                array_unshift($parentCustomParams['selectoptions'], (object)[
                    'voption' => 'clabels',
                    'clabel'  => $key,
                    'values'  => $item
                ]);
            }

            $childCustomParam = $this->getProductCustomfieldParams($product_id, $item);

            if (is_null($childCustomParam)) {
                $this->addProductCustomfields($product_id, [
                    'virtuemart_custom_id' => $this->customFields['string_id'],
                    'customfield_value'    => $item,
                ]);
            }

            if (empty($options[$product_id])) {
                $options[$product_id] = [$item];
            } else {
                if (array_search($item, $options[$product_id]) === false) {
                    $options[$product_id][] = $item;
                }
            }
        }
        unset($key, $item, $exist, $param, $values, $childCustomParam);

        $parentCustomParams['options'] = (object)$options;
        $this->model->upData('#__virtuemart_product_customfields', ['virtuemart_customfield_id' => $parentCustomFields['virtuemart_customfield_id']], ['customfield_params' => $this->encodeCustomfieldParams($parentCustomParams)]);
    }

    /**
     * @param $product_id
     * @param $product
     *
     * @since 4.0
     */
    public function oasisProduct($product_id, $product)
    {
        $data = [
            'product_id_oasis'     => $product->id,
            'group_id'             => $product->group_id,
            'color_group_id'       => $product->color_group_id,
            'rating'               => $product->rating,
            'option_date_modified' => date('Y-m-d H:i:s'),
            'product_id'           => $product_id,
            'article'              => $product->article,
        ];

        $vmOasisProductId = $this->model->getData('#__oasis_product', ['product_id_oasis'], ['product_id_oasis' => $product->id]);

        if (is_null($vmOasisProductId)) {
            $this->model->addData('#__oasis_product', $data);
        } else {
            $this->model->upData('#__oasis_product', ['product_id_oasis' => $product->id], $data);
        }
    }

    /**
     * Insert into table #__virtuemart_products
     *
     * @param       $product
     * @param array $data
     * @return int
     *
     * @since 4.0
     */
    public function addVirtuemartProducts($product, array $data = []): int
    {
        $dataProduct = [
            'product_parent_id'    => 0,
            'product_sku'          => $product->article,
            'product_gtin'         => '',
            'product_mpn'          => '',
            'product_weight_uom'   => 'KG',
            'product_lwh_uom'      => 'M',
            'product_url'          => '',
            'product_in_stock'     => intval($product->total_stock),
            'product_availability' => '',
            'product_unit'         => 'KG',
            'product_params'       => 'min_order_level=null|max_order_level=null|step_order_level=null|shared_stock="0"|product_box=null|',
            'intnotes'             => '',
            'metarobot'            => '',
            'metaauthor'           => '',
            'layout'               => '',
            'published'            => (is_null($product->total_stock) && $product->rating !== 5) ? 0 : 1,
            'has_categories'       => 1,
            'has_manufacturers'    => 1,
            'has_medias'           => 1,
            'has_prices'           => 1,
            'has_shoppergroups'    => 0,
            'created_on'           => date('Y-m-d H:i:s'),
        ];

        $data += $dataProduct;

        return $this->model->addData('#__virtuemart_products', $data);
    }

    /**
     * Insert into table #__virtuemart_products
     *
     * @param       $vmProductId
     * @param       $product
     * @param array $data
     *
     * @since 4.0
     */
    public function editVirtuemartProducts($vmProductId, $product, array $data = [])
    {
        $dataProduct['product_in_stock'] = intval($product->total_stock);
        $dataProduct['published'] = (is_null($product->total_stock) && $product->rating !== 5) ? 0 : 1;

        $data += $dataProduct;

        $this->model->upData('#__virtuemart_products', ['virtuemart_product_id' => $vmProductId], $data);
    }

    /**
     * Insert into table #__virtuemart_products_ru_ru (ru_ru - default lang virtuemart)
     *
     * @param       $product_id
     * @param       $product
     * @param bool $isChild
     * @param bool $edit
     *
     * @since 4.0
     */
    public function virtuemartProductsLang($product_id, $product, bool $isChild = false, bool $edit = false)
    {
        $data = [
            'virtuemart_product_id' => $product_id,
            'product_s_desc'        => OasisHelper::textExcerpt($product->description, 10),
            'product_desc'          => '<p>' . nl2br($product->description) . '</p>',
            'product_name'          => $isChild ? $product->full_name : $product->name,
            'metadesc'              => '',
            'metakey'               => '',
            'customtitle'           => '',
        ];

        $productAttribute = [];

        foreach ($product->attributes as $attribute) {
            $dim = isset($attribute->dim) ? ' ' . $attribute->dim : '';

            if ($attribute->name !== 'Размер') {
                $needed = array_search($attribute->name, array_column($productAttribute, 'name'));

                if ($needed === false) {
                    $productAttribute[] = [
                        'name'  => $attribute->name,
                        'value' => $attribute->value . $dim,
                    ];
                } else {
                    $productAttribute[$needed]['value'] .= ', ' . $attribute->value . $dim;
                }
                unset($needed);
            } elseif ($isChild) {
                $productAttribute[] = [
                    'name'  => $attribute->name,
                    'value' => $attribute->value . $dim,
                ];
                $data['product_name'] = $product->full_name . ' ' . $attribute->value;
            }
        }
        unset($attribute, $dim);

        $data['product_desc'] .= '<br />
<table class="o-attributes">
<tbody>';
        foreach ($productAttribute as $attribute) {
            $data['product_desc'] .= '
    <tr>
        <td>' . $attribute['name'] . '</td>' . '
        <td>' . $attribute['value'] . '</td>
    </tr>';
        }
        unset($attribute);
        $data['product_desc'] .= '
</tbody>
</table>';

        if ($edit) {
            $data['slug'] = $this->model->getData('#__virtuemart_products', ['slug'], ['virtuemart_product_id' => $product_id], true);
            $this->model->upData('#__virtuemart_products', ['virtuemart_product_id' => $product_id], $data, true);
        } else {
            $data['slug'] = $this->getSlug('#__virtuemart_products', $data['product_name']);
            $this->model->addData('#__virtuemart_products', $data, true);
        }
    }

    /**
     * Add categories
     * Insert into table #__virtuemart_product_categories
     *
     * @param $product_id
     * @param $product
     *
     * @since 4.0
     */
    public function virtuemartProductCategories($product_id, $product)
    {
        $vmProductCategories = $this->model->getData('#__virtuemart_product_categories', ['virtuemart_category_id'], ['virtuemart_product_id' => $product_id], false, 'loadAssocList');
        $productCategories = $this->getProductCategories($product->categories);

        if (!$vmProductCategories) {
            foreach ($productCategories as $productCategory) {
                $this->model->addData('#__virtuemart_product_categories', ['virtuemart_product_id' => $product_id, 'virtuemart_category_id' => $productCategory]);
            }
        } else {
            $dataEditCat = [];

            foreach ($vmProductCategories as $vmProductCategory) {
                if (array_search($vmProductCategory['virtuemart_category_id'], $productCategories) === false) {
                    $this->model->deleteData('#__virtuemart_product_categories', [
                        'virtuemart_category_id' => $vmProductCategory['virtuemart_category_id'],
                        'virtuemart_product_id'  => $product_id,
                    ]);
                } else {
                    $dataEditCat[] = $vmProductCategory['virtuemart_category_id'];
                }
            }
            unset($vmProductCategory);

            foreach ($productCategories as $productCategory) {
                if (array_search($productCategory, $dataEditCat) === false) {
                    $this->model->addData('#__virtuemart_product_categories', ['virtuemart_product_id' => $product_id, 'virtuemart_category_id' => $productCategory]);
                }
            }
        }
        unset($productCategory);
    }

    public function getProductCategories(array $categories_oasis): array
    {
        $result = [];
        foreach ($categories_oasis as $categoryId) {
            $rel_id = $this->cf->getRelCategoryId($categoryId);
            if(isset($rel_id)){
                $result = array_merge($result, $this->getCategoryParents($rel_id));
            }
            else{
                $full_categories = $this->getOasisParentsCategoriesId($categoryId);

                foreach ($full_categories as $cat_id) {
                    $result[] = $this->getVmCategoryId($cat_id);
                }
            }
        }
        return $result;
    }

    public function getCategoryParents($cat_id): array {
        $parents = [$cat_id];
        while($cat_id != 0){
            $category_parent_id = $this->model->getData('#__virtuemart_categories', ['category_parent_id'], ['virtuemart_category_id' => $cat_id]);
            if($category_parent_id){
                $parents []= $category_parent_id;
                $cat_id = $category_parent_id;
            }
            else{
                break;
            }
        }
        return array_reverse($parents);
    }

    /**
     * Get oasis parents id categories
     *
     * @param null $cat_id
     *
     * @return array
     */
    public function getOasisParentsCategoriesId($cat_id): array {
        $result = [];
        $parent_id = $cat_id;

        while($parent_id){
            foreach ($this->categories as $category) {
                if ($parent_id == $category->id) {
                    array_unshift($result, $category->id);
                    $parent_id = $category->parent_id;
                    continue 2;
                }
            }
            break;
        }
        return $result;
    }

    /**
     * Add manufacturer
     * Insert into table #__virtuemart_product_manufacturers
     *
     * @param $product_id
     * @param $product
     * @return mixed
     *
     * @since 4.0
     */
    public function virtuemartProductManufacturers($product_id, $product)
    {
        $vmProductManufacturerId = $this->model->getData('#__virtuemart_product_manufacturers', ['virtuemart_manufacturer_id'], ['virtuemart_product_id' => $product_id]);

        if (!is_null($product->brand_id)) {
            $productManufacturerId = $this->addBrand($product->brand_id);

            if (is_null($vmProductManufacturerId)) {
                $vmProductManufacturerId = $this->model->addData('#__virtuemart_product_manufacturers',
                    ['virtuemart_product_id' => $product_id, 'virtuemart_manufacturer_id' => $productManufacturerId]);
            } else {
                $this->model->upData('#__virtuemart_product_manufacturers', ['virtuemart_product_id' => $product_id], ['virtuemart_manufacturer_id' => $productManufacturerId]);
            }
        }

        return $vmProductManufacturerId;
    }

    /**
     * Processing product images
     *
     * @param $product_id
     * @param $product
     * @param false $isChild
     *
     * @since 4.0
     */
    public function processingProductMedias($product_id, $product, bool $isChild = false)
    {
        if (is_array($product->images)) {
            foreach ($product->images as $key => $image) {
                if (isset($image->superbig)) {
                    $vmImageId = $this->model->getData('#__virtuemart_medias', ['virtuemart_media_id'], ['file_title' => pathinfo($image->superbig)['filename']]);

                    if (is_null($vmImageId)) {
                        if ($isChild) {
                            $existParentProductId = $this->model->getData('#__oasis_product', ['product_id'], ['color_group_id' => $product->color_group_id]);

                            if (!empty($existParentProductId)) {
                                $vmMediaIds = $this->model->getData('#__virtuemart_product_medias', ['virtuemart_media_id'], ['virtuemart_product_id' => $existParentProductId], false, 'loadAssocList');

                                if ($vmMediaIds) {
                                    foreach ($vmMediaIds as $vmMediaId) {
                                        $this->addVirtuemartProductMedia($product_id, $vmMediaId['virtuemart_media_id'], ++$key);
                                    }
                                    break;
                                }
                            }
                        }

                        $vmImageId = $this->saveProductImage($image);
                    }

                    if ($vmImageId) {
                        $this->addVirtuemartProductMedia($product_id, $vmImageId, ++$key);
                    }
                }
            }
        }
    }

    /**
     * Save image
     *
     * @param $image
     * @return int|null
     *
     * @since 4.0
     */
    public function saveProductImage($image): ?int
    {
        if($this->cf->is_cdn_photo){
            $url = preg_replace("(^https?:)", "", $image->superbig);
            $vmImageId = $this->model->addVirtuemartMedias($url);
        }
        else {
            $vmImageId = NULL;
            $data_img = [
                'folder_name' => 'images/virtuemart/product/oasis',
                'img_url'     => $image->superbig,
            ];
            $img = OasisHelper::saveImg($data_img);
            if ($img) {
                $vmImageId = $this->model->addVirtuemartMedias($img);
            }
        }
        return $vmImageId;
    }

    /**
     * Add image
     * Insert into table #__virtuemart_product_medias
     *
     * @param $product_id
     * @param $vmImageId
     * @param $ordering
     *
     * @since 4.0
     */
    public function addVirtuemartProductMedia($product_id, $vmImageId, $ordering)
    {
        $vmProducImagetId = $this->model->getData('#__virtuemart_product_medias', ['id'], [
            'virtuemart_product_id' => $product_id,
            'virtuemart_media_id'   => $vmImageId,
        ]);

        if (is_null($vmProducImagetId)) {
            $this->model->addData('#__virtuemart_product_medias', [
                'virtuemart_product_id' => $product_id,
                'virtuemart_media_id'   => $vmImageId,
                'ordering'              => $ordering,
            ]);
        }
    }

    /**
     * Add price
     * Insert into table #__virtuemart_product_prices
     *
     * @param $product_id
     * @param $product
     *
     * @since 4.0
     */
    public function virtuemartProductPrices($product_id, $product)
    {
        $vmProductPrice = $this->model->getData('#__virtuemart_product_prices', ['*'], ['virtuemart_product_id' => $product_id], false, 'loadAssocList');

        $price = $this->cf->is_price_dealer ? $product->discount_price : $product->price;

        if (!empty($this->cf->price_factor)) {
            $price = $price * $this->cf->price_factor;
        }

        if (!empty($this->cf->price_increase)) {
            $price = $price + $this->cf->price_increase;
        }

        if (!$vmProductPrice) {
            $data = [
                'virtuemart_product_id'  => $product_id,
                'product_price'          => $price,
                'override'               => 0,
                'product_override_price' => '0.00000',
                'product_tax_id'         => 0,
                'product_discount_id'    => 0,
                'product_currency'       => (int)$this->model->getData('#__virtuemart_vendors', ['vendor_currency']),
                'created_on'             => date('Y-m-d H:i:s'),
            ];
            $this->model->addData('#__virtuemart_product_prices', $data);
        } else {
            foreach ($vmProductPrice as $item) {
                $this->model->upData('#__virtuemart_product_prices', ['virtuemart_product_price_id' => $item['virtuemart_product_price_id']], ['product_price' => $price]);
            }
        }
    }

    /**
     * @return array
     *
     * @since 4.0
     */
    public function checkVirtuemartCustoms(): array
    {
        $result['string_id'] = $this->model->getData('#__virtuemart_customs', ['virtuemart_custom_id'], ['custom_value' => 'o_string']);

        if (is_null($result['string_id'])) {
            $result['string_id'] = $this->model->addData('#__virtuemart_customs', [
                'custom_title'               => 'Строка',
                'show_title'                 => 0,
                'custom_value'               => 'o_string',
                'custom_desc'                => '',
                'field_type'                 => 'S',
                'is_cart_attribute'          => 0,
                'layout_pos'                 => '',
                'custom_params'              => 'addEmpty="0"|selectType="0"|multiplyPrice="0"|transform=""|product_sku=""|product_gtin=""|product_mpn=""|',
                'virtuemart_shoppergroup_id' => '',
                'published'                  => 0,
                'created_on'                 => date('Y-m-d H:i:s'),
            ]);
        }

        $result['multi_id'] = $this->model->getData('#__virtuemart_customs', ['virtuemart_custom_id'], ['custom_value' => 'o_multi_variant']);

        if (is_null($result['multi_id'])) {

            $result['multi_id'] = $this->model->addData('#__virtuemart_customs', [
                'custom_title'               => 'Мультивариант',
                'show_title'                 => 1,
                'custom_value'               => 'o_multi_variant',
                'custom_desc'                => '',
                'field_type'                 => 'C',
                'is_cart_attribute'          => 1,
                'layout_pos'                 => 'addtocart',
                'custom_params'              => 'usecanonical="1"|showlabels="0"|browseajax="1"|sCustomId="' . $result['string_id'] . '"|selectType="0"|withImage="0"|images="0"|selectoptions="0"|clabels="0"|options="0"|',
                'virtuemart_shoppergroup_id' => '',
                'created_on'                 => date('Y-m-d H:i:s'),
            ]);
        }

        return $result;
    }

    /**
     * @param $product_id
     * @param string $value
     * @return mixed
     *
     * @since 4.0
     */
    public function getProductCustomfieldParams($product_id, string $value = '')
    {
        $where['virtuemart_product_id'] = $product_id;

        if (!empty($value)) {
            $where['customfield_value'] = $value;
        }

        return $this->model->getData('#__virtuemart_product_customfields', ['*'], $where, false, 'loadAssoc');
    }

    /**
     * @param $data
     * @return array
     *
     * @since 4.0
     */
    public function decodeCustomfieldParams($data): array
    {
        $params = explode('|', $data);

        $result = [];
        foreach ($params as $param) {
            if (!empty($param)) {
                $dataParam = explode('=', $param);
                $result[$dataParam[0]] = json_decode($dataParam[1]);
            }
        }

        return $result;
    }

    /**
     * @param array $dataParams
     * @return string
     *
     * @since 4.0
     */
    public function encodeCustomfieldParams(array $dataParams): string
    {
        $result = '';

        foreach ($dataParams as $key => $value) {
            if (!empty($key)) {
                $result .= $key . '=' . \vmJsApi::safe_json_encode($value) . '|';
            }
        }

        return $result;
    }

    /**
     * @param        $product_id
     * @param array $data
     *
     * @since 4.0
     */
    public function addProductCustomfields($product_id, array $data = [])
    {
        $data += [
            'virtuemart_custom_id'  => $this->customFields['multi_id'],
            'customfield_value'     => NULL,
            'customfield_params'    => '',
            'virtuemart_product_id' => $product_id,
            'customfield_price'     => 0,
            'published'             => 0,
        ];

        $this->model->addData('#__virtuemart_product_customfields', $data);
    }

    /**
     * @param $customfield_id
     *
     * @since 4.0
     */
    public function editProductCustomfields($customfield_id)
    {
        $this->model->upData('#__virtuemart_product_customfields', ['virtuemart_customfield_id' => (int)$customfield_id], ['has_children' => 1]);
    }

    /**
     * @param $table
     * @param $str
     * @param int $count
     * @return string
     *
     * @since 4.0
     */
    public function getSlug($table, $str, int $count = 0): string
    {
        $mod = '';

        if ($count > 0) {
            $mod = '-' . $count;
        }

        $slug = OasisHelper::transliter($str) . $mod;
        $neededSlug = $this->model->getData($table, ['*'], ['slug' => $slug], true);

        if (!is_null($neededSlug)) {
            $count++;
            $slug = $this->getSlug($table, $str, $count);
        }

        return $slug;
    }

    /**
     * @param $oasisCatId
     * @return bool|int
     *
     * @since 4.0
     */
    public function getVmCategoryId($oasisCatId)
    {
        $oasisCategory = OasisHelper::searchObject($this->categories, $oasisCatId);

        if (!$oasisCategory) {
            return false;
        }

        $category_id = $this->model->getCategoryId($oasisCategory);

        if (is_null($category_id)) {
            $category_id = $this->addCategory($oasisCategory);
        }

        return $category_id;
    }

    /**
     * @param $category
     * @return int
     *
     * @since 4.0
     */
    public function addCategory($category): int
    {
        $data = [
            'category_parent_id'      => 0,
            'category_template'       => '',
            'category_layout'         => '',
            'category_product_layout' => '',
            'limit_list_step'         => 0,
            'limit_list_initial'      => 0,
            'products_per_row'        => '',
            'cat_params'              => 'show_store_desc=""|showcategory_desc=""|showcategory=""|categories_per_row=""|showproducts=""|omitLoaded=""|showsearch=""|productsublayout=""|featured=""|featured_rows=""|omitLoaded_featured=""|discontinued=""|discontinued_rows=""|omitLoaded_discontinued=""|latest=""|latest_rows=""|omitLoaded_latest=""|topten=""|topten_rows=""|omitLoaded_topten=""|recent=""|recent_rows=""|omitLoaded_recent=""|',
            'metarobot'               => '',
            'metaauthor'              => '',
            'has_children'            => 0,
            'has_medias'              => 1,
            'created_on'              => date('Y-m-d H:i:s'),
        ];

        if (!is_null($category->parent_id)) {
            $parent_category = OasisHelper::searchObject($this->categories, $category->parent_id);
            $parent_category_id = $this->model->getCategoryId($parent_category);

            if (is_null($parent_category_id)) {
                $data['category_parent_id'] = $this->addCategory($parent_category);
            } else {
                $data['category_parent_id'] = $parent_category_id;
            }
            $this->model->upData('#__virtuemart_categories', ['virtuemart_category_id' => $data['category_parent_id']], ['has_children' => 1]);
        }

        // insert into table #__virtuemart_categories
        $category_id = $this->model->addData('#__virtuemart_categories', $data);
        $this->model->addData('#__oasis_categories', [
            'category_id_oasis' => $category->id,
            'category_id'       => $category_id,
        ]);

        $data_cat_lang = [
            'virtuemart_category_id' => $category_id,
            'category_name'          => $category->name,
            'category_description'   => '',
            'metadesc'               => '',
            'metakey'                => '',
            'customtitle'            => '',
            'slug'                   => $this->getSlug('#__virtuemart_categories', $category->slug),
        ];

        // insert into table #__virtuemart_categories_ru_ru (ru_ru - default lang virtuemart)
        $this->model->addData('#__virtuemart_categories', $data_cat_lang, true);

        $data_cat_categories = [
            'category_parent_id' => $data['category_parent_id'],
            'category_child_id'  => $category_id,
            'ordering'           => 0,
        ];

        // insert into table #__virtuemart_category_categories
        $this->model->addData('#__virtuemart_category_categories', $data_cat_categories);

        return $category_id;
    }

    /**
     * @param $brand_id
     * @return int
     *
     * @since 4.0
     */
    public function addBrand($brand_id)
    {
        $brand = OasisHelper::searchObject($this->manufacturers, $brand_id);

        if (!$brand) {
            return false;
        }

        return $this->model->getManufacturer($brand);
    }
}