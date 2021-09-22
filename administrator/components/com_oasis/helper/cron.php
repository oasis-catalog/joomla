<?php
/**
 * @package     Oasis
 * @subpackage  Administrator
 *
 * @author      Viktor G. <ever2013@mail.ru>
 * @copyright   Copyright (C) 2021 Oasiscatalog. All rights reserved.
 * @license     GNU/GPLv3 http://www.gnu.org/licenses/gpl-3.0.html
 * @link        https://www.oasiscatalog.com/
 */

use Joomla\Registry\Registry;

/**
 * This is a CRON script which should be called from the command-line, not the
 * web. For example something like:
 * /usr/local/bin/php /path/to/site/administrator/components/com_oasis/helper/cron.php --key=
 * /usr/local/bin/php /path/to/site/administrator/components/com_oasis/helper/cron.php --key= --up
 */

define('_JEXEC', 1);

error_reporting(E_ALL ^ E_NOTICE ^ E_WARNING);
ini_set('display_errors', 1);

if (file_exists(dirname(dirname(dirname(dirname(__DIR__)))) . '/defines.php')) {
    require_once dirname(dirname(dirname(dirname(__DIR__)))) . '/defines.php';
}

if (!defined('_JDEFINES')) {
    define('JPATH_BASE', dirname(dirname(dirname(__DIR__))));
    require_once JPATH_BASE . '/includes/defines.php';
}

if (file_exists(JPATH_LIBRARIES . '/import.legacy.php')) {
    require_once JPATH_LIBRARIES . '/import.legacy.php';
} elseif (file_exists(JPATH_LIBRARIES . '/import.php')) {
    require_once JPATH_LIBRARIES . '/import.php';
}

require_once JPATH_LIBRARIES . '/cms.php';

require_once JPATH_CONFIGURATION . '/configuration.php';

jimport('joomla.environment.uri');
jimport('joomla.event.dispatcher');
jimport('joomla.utilities.utility');
jimport('joomla.utilities.arrayhelper');
jimport('joomla.environment.request');
jimport('joomla.application.component.helper');
jimport('joomla.application.component.helper');
jimport('joomla.filesystem.path');

JFactory::getApplication('administrator');
JFactory::getApplication()->input->set('option', 'com_oasis');

define('JPATH_COMPONENT', JPATH_ROOT . '/components/com_oasis');
define('JPATH_COMPONENT_SITE', JPATH_ROOT . '/components/com_oasis');
define('JPATH_COMPONENT_ADMINISTRATOR', JPATH_ADMINISTRATOR . '/components/com_oasis');

define('OASIS_VERSION', '1.0');

$config = JFactory::getConfig();

JLoader::registerPrefix('Oasis', JPATH_ADMINISTRATOR . '/components/com_oasis', true);
require_once JPATH_ROOT . '/administrator/components/com_oasis/helper/oasis.php';
require_once JPATH_ROOT . '/administrator/components/com_oasis/models/oasis.php';

/**
 * Runs a Oasis cron job
 *
 * --arguments can have any value
 * -arguments are boolean
 *
 * @package     Oasis
 * @subpackage  CLI
 *
 * @since       1.0
 */
class Oasiscron extends JApplicationCli
{

    /**
     * Settings class
     *
     * @var    OasisHelperSettings
     * @since  1.0
     */
    private $settings = null;

    /**
     * Database class
     *
     * @var    JDatabaseDriver
     * @since  1.0
     */
    private $db = null;

    /**
     * Model class
     *
     * @var    null
     * @since  1.0
     */
    private $model = null;

    /**
     * Categories oasis
     *
     * @var null
     * @since 1.0
     */
    private $categories = null;

    /**
     * Products oasis
     *
     * @var null
     * @since 1.0
     */
    private $products = null;

    /**
     * Manufacturers oasis
     *
     * @var null
     * @since 1.0
     */
    private $manufacturers = null;

    /**
     * Const ignore attribute
     *
     * @var null
     * @since 1.0
     */
    private $var_size = 'Размер';

    /**
     * Class constructor.
     *
     * @param JInputCli        $input          An optional argument to provide dependency injection for the application's
     *                                         input object.  If the argument is a JInputCli object that object will become
     *                                         the application's input object, otherwise a default input object is created.
     * @param Registry         $config         An optional argument to provide dependency injection for the application's
     *                                         config object.  If the argument is a Registry object that object will become
     *                                         the application's config object, otherwise a default config object is created.
     * @param JEventDispatcher $dispatcher     An optional argument to provide dependency injection for the application's
     *                                         event dispatcher.  If the argument is a JEventDispatcher object that object will become
     *                                         the application's event dispatcher, if it is null then the default event dispatcher
     *                                         will be created based on the application's loadDispatcher() method.
     *
     * @see     JApplicationBase::loadDispatcher()
     * @since   1.0
     */

    public function __construct(JInputCli $input = null, Registry $config = null, JEventDispatcher $dispatcher = null)
    {
        if (array_key_exists('REQUEST_METHOD', $_SERVER)) {
            echo 'You are not supposed to access this script from the web. You have to run it from the command line. ' .
                'If you don\'t understand what this means, you must not try to use this file before reading the ' .
                'documentation. Thank you.';
            $this->close();
        }

        $cgiMode = false;

        if (!defined('STDOUT') || !defined('STDIN') || !isset($_SERVER['argv'])) {
            $cgiMode = true;
        }

        if ($input instanceof JInput) {
            $this->input = $input;
        } else {
            if (class_exists('JInput')) {
                if ($cgiMode) {
                    $query = 'cron.php ';

                    if (!empty($_GET)) {
                        foreach ($_GET as $k => $v) {
                            $query .= " $k";

                            if ($v != '') {
                                $query .= "=$v";
                            }
                        }
                    }

                    $query = ltrim($query);
                    $argv = explode(' ', $query);

                    $_SERVER['argv'] = $argv;
                }

                if (class_exists('JInputCLI')) {
                    $this->input = new JInputCLI;
                } else {
                    $this->input = new JInputCli;
                }
            }
        }

        define('OASIS_CLI', true);

        if ($config instanceof Registry) {
            $this->config = $config;
        } else {
            $this->config = new Registry;
        }

        $this->loadDispatcher($dispatcher);
        $this->loadConfiguration($this->fetchConfigurationData());
        $this->model = new OasisModelOasis();
    }

    /**
     * Entry point for the script
     *
     * @return  void
     *
     * @throws  OasisException
     *
     * @since   1.0
     */
    public function doExecute()
    {
        $this->onBeforeExecute();

        $this->db = JFactory::getDbo();
        $this->settings = new OasisHelperSettings($this->db);

        $hostname = $this->settings->get('oasis_host', 'http://example.com');
        $uri = JUri::getInstance($hostname);
        $_SERVER['HTTP_HOST'] = $uri->getHost();
        $_SERVER['REQUEST_METHOD'] = 'GET';

        $secret = md5($this->settings->get('oasis_api_key'));
        $key = $this->input->get('key', '', 'raw');
        $up = $this->input->get('up', '', 'raw');

        if ($key === $secret) {
            // Set limits
            set_time_limit(0);
            ini_set('memory_limit', '2G');

            try {
                $start_time = microtime(true);

                if ($up === '') {
                    $this->categories = OasisHelper::getOasisCategories();
                    $this->products = OasisHelper::getOasisProducts();
                    $this->manufacturers = OasisHelper::getOasisManufacturers();

                    $count = count($this->products);
                    $i = 1;

                    foreach ($this->products as $product) {
                        OasisHelper::saveToLog($product->id, 'count:' . $count . ' | item:' . $i);
                        $this->import($product);
                        $i++;
                    }
                    unset($product);

                    $productsOasis = $this->model->getData('#__oasis_product', ['article'], [], false, 'loadAssocList');

                    foreach ($productsOasis as $productOasis) {
                        if (is_null($this->model->getData('#__virtuemart_products', ['virtuemart_product_id'], ['product_sku' => $productOasis['article']]))) {
                            $this->model->deleteData('#__oasis_product', ['article' => $productOasis['article']]);
                        }
                    }
                } elseif ($up === true) {
                    $stock = OasisHelper::getOasisStock();
                    $this->upStock($stock);
                }

                $end_time = microtime(true);
                OasisHelper::saveToLog('', 'Время выполнения скрипта: ' . ($end_time - $start_time) . ' сек.');
            } catch (Exception $e) {

                echo $e->getMessage() . "\r\n";

                exit($e->getCode());
            }
        }
    }

    /**
     * @param $product
     *
     * @return int|null
     *
     * @since 1.0
     */
    public function import($product): ?int
    {
        $result = null;

        if (!is_null($product->parent_size_id) || !is_null($product->parent_volume_id)) {
            $parent_product_id = null;

            if (!is_null($product->parent_size_id)) {
                $parent_product_id = $product->parent_size_id;
            } elseif (!is_null($product->parent_volume_id)) {
                $parent_product_id = $product->parent_volume_id;
            }

            if ($parent_product_id === $product->id) {
                // add parent
                $result = $this->checkProduct($product, true);
            } else {
                $parent_product = [];

                foreach ($this->products as $item) {
                    if ($item->id === $parent_product_id) {
                        $parent_product = $item;
                        break;
                    }
                }

                if (!$parent_product) {
                    $parent_product_oasis = OasisHelper::getOasisProducts(['ids' => $parent_product_id]);
                    $parent_product = $parent_product_oasis ? array_shift($parent_product_oasis) : false;
                }

                if (!empty($parent_product)) {
                    $vmParentProductId = $this->model->getData('#__virtuemart_products', ['virtuemart_product_id'], [
                        'product_sku' => $parent_product->article,
                        'product_parent_id' => 0,
                    ]);

                    if (is_null($vmParentProductId)) {
                        $vmParentProductId = $this->import($parent_product);
                    }

                    $vmChildProductId = $this->model->getData('#__virtuemart_products', ['virtuemart_product_id'], [
                        'product_sku' => $product->article,
                        'product_parent_id' => $vmParentProductId,
                    ]);

                    if (is_null($vmChildProductId)) {
                        $this->addProductChild($vmParentProductId, $product);
                    } else {
                        $this->productRelated($vmParentProductId, $product);
                        $this->editProductChild($vmParentProductId, $product);
                    }
                }
                unset($vmParentProductId, $parent_product_oasis, $parent_product);
            }
        } else {
            $this->checkProduct($product);
        }

        return $result;
    }

    /**
     * update product stock
     *
     * @param $stock
     *
     *
     * @since 1.0
     */
    public function upStock($stock)
    {
        foreach ($stock as $item) {
            $oasisProduct = $this->model->getData('#__oasis_product', ['rating', 'product_id'], ['product_id_oasis' => $item->id], false, 'loadAssoc');

            if (!is_null($oasisProduct) && (int)$oasisProduct['rating'] !== 5) {
                $this->model->upData('#__virtuemart_products', ['virtuemart_product_id' => $oasisProduct['product_id']], ['product_in_stock' => $item->stock]);
            }
        }
        unset($item, $oasisProduct);
    }

    /**
     * @param      $product
     * @param bool $customField
     * @return int
     *
     * @since 1.0
     */
    public function checkProduct($product, bool $customField = false): int
    {
        $vmProductId = $this->model->getData('#__virtuemart_products', ['virtuemart_product_id'], ['product_sku' => $product->article]);

        if (is_null($vmProductId)) {
            $vmProductId = $this->addProduct($product, $customField);
        } else {
            $this->editProduct($vmProductId, $product, $customField);
        }

        return $vmProductId;
    }

    /**
     * @param      $product
     * @param bool $customField
     * @return int
     *
     * @since 1.0
     */
    public function addProduct($product, bool $customField = false): int
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
        $this->addVirtuemartProductMedias($product_id, $product);

        // prices
        $this->virtuemartProductPrices($product_id, $product);

        // child product
        if ($customField) {
            $this->addVirtuemartProductCustomfields($product_id);
            $this->addProductChild($product_id, $product);
        } else {
            // add table oasis product
            $this->oasisProduct($product_id, $product);
        }

        return $product_id;
    }

    /**
     * @param       $vmProductId
     * @param       $product
     * @param false $customField
     *
     *
     * @since 1.0
     */
    public function editProduct($vmProductId, $product, bool $customField = false)
    {
        $this->editVirtuemartProducts($vmProductId, $product);
        $this->virtuemartProductsLang($vmProductId, $product, false, true);
        $this->virtuemartProductCategories($vmProductId, $product);
        $this->virtuemartProductManufacturers($vmProductId, $product);
        $this->virtuemartProductPrices($vmProductId, $product);
        $this->productRelated($vmProductId, $product);

        if ($customField) {
            $this->editProductChild($vmProductId, $product);
        } else {
            $this->oasisProduct($vmProductId, $product);
        }
    }

    /**
     * @param $parent_id
     * @param $product
     *
     * @return int
     *
     * @since 1.0
     */
    public function addProductChild($parent_id, $product): int
    {
        // add product
        $dataProducts = [
            'product_parent_id' => $parent_id,
            'product_in_stock' => $product->rating === 5 ? 1000000 : $product->total_stock,
            'has_categories' => 0,
            'has_manufacturers' => 0,
            'has_medias' => 0,
            'has_prices' => 1,
        ];

        if (is_null($product->total_stock) && $product->rating !== 5) {
            $dataProducts['published'] = 0;
        }

        $product_id = $this->addVirtuemartProducts($product, $dataProducts);

        // add table oasis product
        $this->oasisProduct($product_id, $product);

        // add product in table default lang virtuemart
        $this->virtuemartProductsLang($product_id, $product, true);
        $this->virtuemartProductPrices($product_id, $product);

        return $product_id;
    }

    /**
     * @param $parent_id
     * @param $product
     *
     *
     * @since 1.0
     */
    public function editProductChild($parent_id, $product)
    {
        $dataProducts = [
            'product_in_stock' => $product->rating === 5 ? 1000000 : $product->total_stock,
        ];

        if (is_null($product->total_stock) && $product->rating !== 5) {
            $dataProducts['published'] = 0;
        }

        $product_id = $this->model->getData('#__virtuemart_products', ['virtuemart_product_id'], [
            'product_parent_id' => $parent_id,
            'product_sku' => $product->article,
        ]);

        // add table oasis product
        $this->oasisProduct($product_id, $product);

        if (!is_null($product_id)) {
            $this->editVirtuemartProducts($product_id, $product, $dataProducts);
            $this->virtuemartProductsLang($product_id, $product, true, true);
            $this->virtuemartProductPrices($product_id, $product);
        }
    }

    /**
     * @param $product_id
     * @param $product
     *
     *
     * @since 1.0
     */
    public function productRelated($product_id, $product)
    {
        $vmOasisDataProducts = $this->model->getData('#__oasis_product', ['product_id', 'article'], ['group_id' => $product->group_id], false, 'loadAssocList');

        if ($vmOasisDataProducts) {
            foreach ($vmOasisDataProducts as $vmOasisDataProduct) {
                $vmProductId = $this->model->getData('#__virtuemart_products', ['virtuemart_product_id'], [
                    'product_parent_id' => 0,
                    'product_sku' => $vmOasisDataProduct['article'],
                ]);

                if (!is_null($vmProductId)) {
                    if ($product_id !== $vmProductId) {
                        $this->addVirtuemartProductCustomfields($product_id, $vmProductId);
                        $this->addVirtuemartProductCustomfields($vmProductId, $product_id);
                    }
                }
            }
        }
    }

    /**
     * @param $product_id
     * @param $product
     *
     *
     * @since 1.0
     */
    public function oasisProduct($product_id, $product)
    {
        $data = [
            'product_id_oasis' => $product->id,
            'group_id' => $product->group_id,
            'rating' => $product->rating,
            'option_date_modified' => date('Y-m-d H:i:s'),
            'product_id' => $product_id,
            'article' => $product->article,
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
     * @since 1.0
     */
    public function addVirtuemartProducts($product, array $data = []): int
    {
        $dataProduct = [
            'product_parent_id' => 0,
            'product_sku' => $product->article,
            'product_gtin' => '',
            'product_mpn' => '',
            'product_weight_uom' => 'KG',
            'product_lwh_uom' => 'M',
            'product_url' => '',
            'product_in_stock' => $product->total_stock,
            'product_availability' => '',
            'product_unit' => 'KG',
            'product_params' => 'min_order_level=null|max_order_level=null|step_order_level=null|shared_stock="0"|product_box=null|',
            'intnotes' => '',
            'metarobot' => '',
            'metaauthor' => '',
            'layout' => '',
            'published' => 1,
            'has_categories' => 1,
            'has_manufacturers' => 1,
            'has_medias' => 1,
            'has_prices' => 1,
            'has_shoppergroups' => 0,
            'created_on' => date('Y-m-d H:i:s'),
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
     * @since 1.0
     */
    public function editVirtuemartProducts($vmProductId, $product, array $data = [])
    {
        $dataProduct = $this->model->getData('#__virtuemart_products', ['*'], ['virtuemart_product_id' => $vmProductId], false, 'loadAssoc');
        $dataProduct['product_in_stock'] = $product->total_stock;

        $data += $dataProduct;

        $this->model->upData('#__virtuemart_products', ['virtuemart_product_id' => $vmProductId], $data);
    }

    /**
     * Insert into table #__virtuemart_products_ru_ru (ru_ru - default lang virtuemart)
     *
     * @param       $product_id
     * @param       $product
     * @param bool  $isChild
     * @param bool  $edit
     * @since 1.0
     */
    public function virtuemartProductsLang($product_id, $product, bool $isChild = false, bool $edit = false)
    {
        $data = [
            'virtuemart_product_id' => $product_id,
            'product_s_desc' => OasisHelper::textExcerpt($product->description, 10),
            'product_desc' => '<p>' . nl2br($product->description) . '</p>',
            'product_name' => $product->full_name,
            'metadesc' => '',
            'metakey' => '',
            'customtitle' => '',
        ];

        $productAttribute = [];

        foreach ($product->attributes as $attribute) {
            $dim = isset($attribute->dim) ? ' ' . $attribute->dim : '';

            if ($attribute->name !== $this->var_size) {
                $needed = array_search($attribute->name, array_column($productAttribute, 'name'));

                if ($needed === false) {
                    $productAttribute[] = [
                        'name' => $attribute->name,
                        'value' => $attribute->value . $dim,
                    ];
                } else {
                    $productAttribute[$needed]['value'] .= ', ' . $attribute->value . $dim;
                }
                unset($needed);
            } elseif ($isChild) {
                $productAttribute[] = [
                    'name' => $attribute->name,
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
     *
     * @since 1.0
     */
    public function virtuemartProductCategories($product_id, $product)
    {
        $vmProductCategories = $this->model->getData('#__virtuemart_product_categories', ['virtuemart_category_id'], ['virtuemart_product_id' => $product_id], false, 'loadAssocList');
        $productCategories = $this->getArrCategories($product->full_categories);

        if (!$vmProductCategories) {
            foreach ($productCategories as $productCategory) {
                $this->model->addData('#__virtuemart_product_categories', ['virtuemart_product_id' => $product_id, 'virtuemart_category_id' => $productCategory]);
            }
            unset($productCategory);
        } else {
            $dataEditCat = [];

            foreach ($vmProductCategories as $vmProductCategory) {
                if (array_search($vmProductCategory['virtuemart_category_id'], $productCategories) === false) {
                    $this->model->deleteData('#__virtuemart_product_categories', [
                        'virtuemart_category_id' => $vmProductCategory['virtuemart_category_id'],
                        'virtuemart_product_id' => $product_id,
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
            unset($productCategory);
        }
    }

    /**
     * Add manufacturer
     * Insert into table #__virtuemart_product_manufacturers
     *
     * @param $product_id
     * @param $product
     *
     *
     * @return mixed
     * @since 1.0
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
     * Add images
     * Insert into table #__virtuemart_medias
     * Insert into table #__virtuemart_product_medias
     *
     * @param $product_id
     * @param $product
     *
     *
     * @since 1.0
     */
    public function addVirtuemartProductMedias($product_id, $product)
    {
        if (is_array($product->images)) {
            foreach ($product->images as $key => $image) {
                if (isset($image->superbig)) {
                    $vmImageId = $this->model->getData('#__virtuemart_medias', ['virtuemart_media_id'], ['file_title' => pathinfo($image->superbig)['filename']]);
                    if (is_null($vmImageId)) {
                        $data_img = [
                            'folder_name' => 'images/virtuemart/product/oasis',
                            'img_url' => $image->superbig,
                        ];

                        $img = OasisHelper::saveImg($data_img);

                        if ($img) {
                            $vmImageId = $this->model->addVirtuemartMedias($img);
                        }
                    }

                    $vmProducImagetId = $this->model->getData('#__virtuemart_product_medias', ['id'], [
                        'virtuemart_product_id' => $product_id,
                        'virtuemart_media_id' => $vmImageId,
                    ]);

                    if (is_null($vmProducImagetId)) {
                        $this->model->addData('#__virtuemart_product_medias', [
                            'virtuemart_product_id' => $product_id,
                            'virtuemart_media_id' => $vmImageId,
                            'ordering' => ++$key,
                        ]);
                    }
                }
            }
            unset($key, $image, $vmImageId);
        }
    }

    /**
     * Add price
     * Insert into table #__virtuemart_product_prices
     *
     * @param $product_id
     * @param $product
     *
     *
     * @since 1.0
     */
    public function virtuemartProductPrices($product_id, $product)
    {
        $vmProductPrice = $this->model->getData('#__virtuemart_product_prices', ['*'], ['virtuemart_product_id' => $product_id], false, 'loadAssocList');

        if (!$vmProductPrice) {
            $data = [
                'virtuemart_product_id' => $product_id,
                'product_price' => $product->price,
                'override' => 0,
                'product_override_price' => '0.00000',
                'product_tax_id' => 0,
                'product_discount_id' => 0,
                'product_currency' => (int)$this->model->getData('#__virtuemart_vendors', ['vendor_currency']),
                'created_on' => date('Y-m-d H:i:s'),
            ];
            $this->model->addData('#__virtuemart_product_prices', $data);
        } else {
            foreach ($vmProductPrice as $item) {
                $item['product_price'] = $product->price;
                $this->model->upData('#__virtuemart_product_prices', ['virtuemart_product_id' => $product_id], $item);
            }
        }
    }

    /**
     * @param        $product_id
     * @param string $related
     *
     *
     * @since 1.0
     */
    public function addVirtuemartProductCustomfields($product_id, string $related = '')
    {
        if (!$related) {
            $vmCustomId = $this->model->getData('#__virtuemart_customs', ['virtuemart_custom_id'], ['custom_value' => 'o_product_child']);

            if (is_null($vmCustomId)) {
                $dataCustoms = [
                    'custom_title' => $this->var_size,
                    'show_title' => 0,
                    'custom_value' => 'o_product_child',
                    'custom_desc' => '',
                    'field_type' => 'A',
                    'is_cart_attribute' => 1,
                    'layout_pos' => 'ontop',
                    'custom_params' => 'withParent="0"|parentOrderable="0"|wPrice="1"|',
                    'virtuemart_shoppergroup_id' => '',
                    'created_on' => date('Y-m-d H:i:s'),
                ];
                $vmCustomId = $this->model->addData('#__virtuemart_customs', $dataCustoms);
            }

            $data = [
                'virtuemart_custom_id' => $vmCustomId,
                'customfield_value' => 'product_name',
                'customfield_params' => 'withParent="0"|parentOrderable="0"|',
            ];
        } else {
            $vmOasisProductRelated = $this->model->getData('#__virtuemart_product_customfields', ['*'], [
                'virtuemart_product_id' => $product_id,
                'customfield_value' => $related,
            ]);

            if (!is_null($vmOasisProductRelated)) {
                return;
            }

            $data = [
                'virtuemart_custom_id' => 1,
                'customfield_value' => $related,
                'customfield_params' => '',
            ];
        }

        $data += [
            'virtuemart_product_id' => $product_id,
            'customfield_price' => '0.000000',
            'published' => '0',
            'created_on' => date('Y-m-d H:i:s'),
        ];

        $this->model->addData('#__virtuemart_product_customfields', $data);
    }

    /**
     * @param     $table
     * @param     $str
     * @param int $count
     *
     * @return string
     *
     * @since 1.0
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
     * Load settings before we execute.
     *
     * @return  void.
     *
     * @since   1.0
     */
    public function onBeforeExecute()
    {
        // Merge the default translation with the current translation
        $jlang = JFactory::getLanguage();
        $jlang->load('com_oasis', JPATH_COMPONENT_ADMINISTRATOR, 'ru-RU', true);
        $jlang->load('com_oasis', JPATH_COMPONENT_ADMINISTRATOR, $jlang->getDefault(), true);
        $jlang->load('com_oasis', JPATH_COMPONENT_ADMINISTRATOR, null, true);
    }

    /**
     * Write a string to standard output.
     *
     * @param string  $text The text to display.
     * @param boolean $nl   True (default) to append a new line at the end of the output string.
     *
     * @return  JApplicationCli  Instance of $this to allow chaining.
     *
     * @codeCoverageIgnore
     * @since   1.0
     */
    public function out($text = '', $nl = true)
    {
        echo $text;

        if ($nl) {
            echo "\n";
        }

        return $this;
    }

    /**
     * @param $product_categories
     *
     * @return array
     *
     * @since 1.0
     */
    public function getArrCategories($product_categories): array
    {
        $result = [];
        foreach ($product_categories as $category) {
            $vmCategoryId = $this->getVmCategoryId($category);
            $needed_cat = array_search($vmCategoryId, $result);

            if ($needed_cat === false) {
                $result[] = $vmCategoryId;
            }
        }
        unset($category, $needed_cat);

        return $result;
    }

    /**
     * @param $id
     *
     * @return bool|int
     *
     * @since 1.0
     */
    public function getVmCategoryId($id)
    {
        $category = OasisHelper::searchObject($this->categories, $id);

        if (!$category) {
            return false;
        }

        $category_id = $this->model->getCategoryId($category);

        if (is_null($category_id)) {
            $category_id = $this->addCategory($category);
        }

        return $category_id;
    }

    /**
     * @param $category
     *
     * @return int
     *
     * @since 1.0
     */
    public function addCategory($category): int
    {
        $data = [
            'category_parent_id' => 0,
            'category_template' => '',
            'category_layout' => '',
            'category_product_layout' => '',
            'limit_list_step' => 0,
            'limit_list_initial' => 0,
            'products_per_row' => '',
            'cat_params' => 'show_store_desc=""|showcategory_desc=""|showcategory=""|categories_per_row=""|showproducts=""|omitLoaded=""|showsearch=""|productsublayout=""|featured=""|featured_rows=""|omitLoaded_featured=""|discontinued=""|discontinued_rows=""|omitLoaded_discontinued=""|latest=""|latest_rows=""|omitLoaded_latest=""|topten=""|topten_rows=""|omitLoaded_topten=""|recent=""|recent_rows=""|omitLoaded_recent=""|',
            'metarobot' => '',
            'metaauthor' => '',
            'has_children' => 0,
            'has_medias' => 1,
            'created_on' => date('Y-m-d H:i:s'),
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

        $data_cat_lang = [
            'virtuemart_category_id' => $category_id,
            'category_name' => $category->name,
            'category_description' => '',
            'metadesc' => '',
            'metakey' => '',
            'customtitle' => '',
            'slug' => $category->slug,
        ];

        // insert into table #__virtuemart_categories_ru_ru (ru_ru - default lang virtuemart)
        $this->model->addData('#__virtuemart_categories', $data_cat_lang, true);

        $data_cat_categories = [
            'category_parent_id' => $data['category_parent_id'],
            'category_child_id' => $category_id,
            'ordering' => 0,
        ];

        // insert into table #__virtuemart_category_categories
        $this->model->addData('#__virtuemart_category_categories', $data_cat_categories);

        return $category_id;
    }

    /**
     * @param $brand_id
     *
     * @return int
     *
     * @since 1.0
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

try {
    JApplicationCli::getInstance('Oasiscron')
        ->execute();
} catch (Exception $e) {

    echo $e->getMessage() . "\r\n";

    exit($e->getCode());
}

function d($data)
{
    echo "\r\n";
    print_r($data);
    echo "\r\n";
}
