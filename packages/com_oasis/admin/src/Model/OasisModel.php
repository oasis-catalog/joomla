<?php
namespace Oasiscatalog\Component\Oasis\Administrator\Model;

use Joomla\CMS\Factory;
use Joomla\CMS\Form\Form;
use Joomla\CMS\MVC\Model\AdminModel;
use Oasiscatalog\Component\Oasis\Administrator\Helper\OasisHelper;
use Oasiscatalog\Component\Oasis\Administrator\Helper\Config as OasisConfig;
use VmConfig;

defined('_JEXEC') or die;

require_once JPATH_ADMINISTRATOR . '/components/com_virtuemart/helpers/config.php';

/**
 * Template model.
 *
 * @package     Oasis
 * @subpackage  Oasis
 *
 * @since 4.0
 */
class OasisModel extends AdminModel
{
    /**
     * Virtuemart config
     *
     * @var    VmConfig
     *
     * @since 4.0
     */
    protected $vmconfig = null;

    /**
     * Database connector
     *
     * @var    Database
     *
     * @since 4.0
     */
    protected $db;

    /**
     * Public class constructor
     *
     * @since 4.0
     */
    public function __construct($config = [])
    {
        parent::__construct($config);

        $this->db = Factory::getContainer()->get('DatabaseDriver');
        $this->vmconfig = VmConfig::loadConfig();
    }

    /**
     * Get the form.
     *
     * @param array $data Data for the form.
     * @param boolean $loadData True if the form is to load its own data (default case), false if not.
     * @return  mixed  A JForm object on success | False on failure.
     *
     * @throws \Exception
     * @since 4.0
     */
    public function getForm($data = [], $loadData = true)
    {
        Form::addFormPath(JPATH_ADMINISTRATOR . '/components/com_oasis/tmpl/oasis/');

        $form = $this->loadForm(
            'com_oasis.oasis',
            'oasis',
            [
                'control'   => 'jform',
                'load_data' => $loadData,
            ]
        );

        if ($form === null) {
            return false;
        }

        return $form;
    }

    /**
     * Load data the form.
     *
     * @return array
     *
     * @since 4.0
     */
    protected function loadFormData(): array
    {
        $cf = OasisConfig::instance(['init' => true]);

        return [
            'currency' =>           $cf->currency,
            'is_no_vat' =>          $cf->is_no_vat,
            'is_not_on_order' =>    $cf->is_not_on_order,
            'price_from' =>         $cf->price_from,
            'price_to' =>           $cf->price_to,
            'rating' =>             $cf->rating,
            'is_wh_moscow' =>       $cf->is_wh_moscow,
            'is_wh_europe' =>       $cf->is_wh_europe,
            'is_wh_remote' =>       $cf->is_wh_remote,
            'limit' =>              $cf->limit,
            'price_factor' =>       $cf->price_factor,
            'price_increase' =>     $cf->price_increase,
            'is_price_dealer' =>    $cf->is_price_dealer,
            'is_not_up_cat' =>      $cf->is_not_up_cat,
            'is_cdn_photo' =>       $cf->is_cdn_photo,
            'is_fast_import' =>     $cf->is_fast_import,
            'is_up_photo' =>        $cf->is_up_photo,
            'is_import_anytime' =>  $cf->is_import_anytime
        ];
    }

    /**
     * @param $category
     * @return int|bool
     *
     * @since 4.0
     */
    public function getCategoryId($category)
    {
        $result = null;
        $oaCat = $this->getData('#__oasis_categories', ['category_id'], ['category_id_oasis' => $category->id]);

        if (!empty($oaCat)) {
            $result = $this->getData('#__virtuemart_categories', ['virtuemart_category_id'], ['virtuemart_category_id' => $oaCat]);

            if (empty($result)) {
                $this->deleteData('#__oasis_categories', ['category_id_oasis' => $category->id]);
            }
        }

        return $result;
    }

    /**
     * @param $data
     * @return int
     *
     * @since 4.0
     */
    public function getManufacturer($data): int
    {
        $manufacturer_id = $this->getData('#__virtuemart_manufacturers', ['virtuemart_manufacturer_id'], ['slug' => $data->slug], true);

        if (is_null($manufacturer_id)) {
            $manufacturer_id = $this->addData('#__virtuemart_manufacturers', ['virtuemart_manufacturercategories_id' => $this->getManufacturerCategories()]);

            $data_manuf = [
                'virtuemart_manufacturer_id' => $manufacturer_id,
                'mf_name'                    => $data->name,
                'slug'                       => $data->slug,
            ];
            $this->addData('#__virtuemart_manufacturers', $data_manuf, true);
            unset($data_manuf);

            $data_img = [
                'folder_name' => 'images/virtuemart/manufacturer',
                'img_url'     => $data->logotype,
            ];

            $img = OasisHelper::saveImg($data_img);

            if ($img) {
                $this->addData('#__virtuemart_manufacturer_medias', [
                    'virtuemart_manufacturer_id' => $manufacturer_id,
                    'virtuemart_media_id'        => $this->addVirtuemartMedias($img, 'manufacturer'),
                    'ordering'                   => 1,
                ]);
            }
        }

        return (int)$manufacturer_id;
    }

    /**
     * @return int
     *
     * @since 4.0
     */
    public function getManufacturerCategories(): int
    {
        $manuf_cat_id = $this->getData('#__virtuemart_manufacturercategories', ['virtuemart_manufacturercategories_id'], ['mf_category_name' => 'oasis'], true);

        if (is_null($manuf_cat_id)) {
            $manuf_cat_id = $this->addData('#__virtuemart_manufacturercategories');

            $data_manuf_cat = [
                'virtuemart_manufacturercategories_id' => $manuf_cat_id,
                'mf_category_name'                     => 'oasis',
                'slug'                                 => 'oasis',
            ];
            $this->addData('#__virtuemart_manufacturercategories', $data_manuf_cat, true);
            unset($data_manuf_cat);
        }

        return (int)$manuf_cat_id;
    }

    /**
     * Insert into table #__virtuemart_medias
     *
     * @param        $img
     * @param string $file_type
     * @return int
     *
     * @since 4.0
     */
    public function addVirtuemartMedias($img, string $file_type = 'product'): int
    {
        $data = [
            'file_title'       => pathinfo($img)['filename'],
            'file_description' => '',
            'file_meta'        => '',
            'file_class'       => '',
            'file_mimetype'    => 'image/jpeg',
            'file_type'        => $file_type,
            'file_url'         => $img,
            'file_url_thumb'   => '',
            'file_params'      => '',
            'file_lang'        => '',
            'created_on'       => date('Y-m-d H:i:s'),
        ];

        return $this->addData('#__virtuemart_medias', $data);
    }

    /**
     * @param        $table
     * @param        $dataSelect
     * @param array $dataWhere
     * @param bool $lang
     * @param string $funcName
     * @param string $operator
     * @return mixed
     *
     * @since 4.0
     */
    public function getData($table, $dataSelect, array $dataWhere = [], bool $lang = false, string $funcName = '', string $operator = '=')
    {
        $postfix = $lang ? '_' . $this->vmconfig->_params['vmlang'] : '';
        $query = $this->db->getQuery(true);

        foreach ($dataSelect as $item) {
            if ($item === '*') {
                $query->select($item);
            } else {
                $query->select($this->db->quoteName($item));
            }
        }
        unset($item);

        $query->from($this->db->quoteName($table . $postfix));

        if ($dataWhere) {
            foreach ($dataWhere as $key => $value) {
                $query->where($this->db->quoteName($key) . ' ' . $operator . ' ' . $this->db->quote($operator === 'LIKE' ? '%' . $value . '%' : $value));
            }
            unset($key, $value);
        }

        $this->db->setQuery($query);
        $result = $funcName ? $this->db->$funcName() : $this->db->loadResult();
        $this->db->execute();

        return $result;
    }

    /**
     * @param       $table
     * @param array $data
     * @param false $lang
     * @return int
     *
     * @since 4.0
     */
    public function addData($table, array $data = [], bool $lang = false): int
    {
        $postfix = $lang ? '_' . $this->vmconfig->_params['vmlang'] : '';

        $query = $this->db->getQuery(true);

        $columns = [];
        $values = [];
        foreach ($data as $key => $value) {
            $columns[] = $key;
            $values[] = $this->db->quote($value);
        }
        unset($key, $value);

        $query
            ->insert($this->db->quoteName($table . $postfix))
            ->columns($this->db->quoteName($columns))
            ->values(implode(',', $values));
        $this->db->setQuery($query);
        $this->db->execute();

        return (int)$this->db->insertid();
    }

    /**
     * @param      $table
     * @param      $dataWhere
     * @param      $data
     * @param bool $lang
     *
     * @since 4.0
     */
    public function upData($table, $dataWhere, $data, bool $lang = false)
    {
        $postfix = $lang ? '_' . $this->vmconfig->_params['vmlang'] : '';

        $query = $this->db->getQuery(true);
        $query->update($this->db->quoteName($table . $postfix));

        foreach ($data as $key => $value) {
            $query->set($this->db->quoteName($key) . ' = ' . $this->db->quote((string)$value));
        }
        unset($key, $value);

        foreach ($dataWhere as $key => $value) {
            $query->where($this->db->quoteName($key) . ' = ' . $this->db->quote($value));
        }
        unset($key, $value);

        $this->db->setQuery($query);
        $this->db->execute();
    }

    /**
     * @param      $table
     * @param      $dataWhere
     * @param bool $lang
     *
     * @since 4.0
     */
    public function deleteData($table, $dataWhere, bool $lang = false)
    {
        $postfix = $lang ? '_' . $this->vmconfig->_params['vmlang'] : '';

        $query = $this->db->getQuery(true);
        $query->delete($this->db->quoteName($table . $postfix));

        foreach ($dataWhere as $key => $value) {
            $query->where($this->db->quoteName($key) . ' = ' . $this->db->quote($value));
        }
        unset($key, $value);

        $this->db->setQuery($query);
        $this->db->execute();
    }
}
