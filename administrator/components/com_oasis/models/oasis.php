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

defined('_JEXEC') or die;

require_once JPATH_ADMINISTRATOR . '/components/com_virtuemart/helpers/config.php';

/**
 * Template model.
 *
 * @package     Oasis
 * @subpackage  Oasis
 * @since       1.0
 */
class OasisModelOasis extends JModelAdmin
{
    /**
     * Database connector
     *
     * @var    JDatabase
     * @since  1.0
     */
    protected $vmconfig = null;

    /**
     * Database connector
     *
     * @var    JDatabase
     * @since  1.0
     */
    protected $db;

    /**
     * Public class constructor
     *
     * @since   1.0
     */
    public function __construct($config = [])
    {
        parent::__construct($config);

        $this->db = JFactory::getDbo();
        $this->vmconfig = VmConfig::loadConfig();
    }

    /**
     * Get the form.
     *
     * @param array   $data     Data for the form.
     * @param boolean $loadData True if the form is to load its own data (default case), false if not.
     *
     * @return  mixed  A JForm object on success | False on failure.
     *
     * @since   1.0
     */
    public function getForm($data = [], $loadData = true)
    {
        JForm::addFormPath(JPATH_ADMINISTRATOR . '/components/com_oasis/views/oasis/tmpl/');

        $form = $this->loadForm(
            'com_oasis.oasis',
            'default',
            [
                'control' => 'jform',
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
     * @since 1.0
     */
    protected function loadFormData()
    {
        $params = JComponentHelper::getParams('com_oasis');

        $data = [
            'oasis_currency' => $params->get('oasis_currency'),
            'oasis_no_vat' => $params->get('oasis_no_vat'),
            'oasis_not_on_order' => $params->get('oasis_not_on_order'),
            'oasis_price_from' => $params->get('oasis_price_from'),
            'oasis_price_to' => $params->get('oasis_price_to'),
            'oasis_rating' => $params->get('oasis_rating'),
            'oasis_warehouse_moscow' => $params->get('oasis_warehouse_moscow'),
            'oasis_warehouse_europe' => $params->get('oasis_warehouse_europe'),
            'oasis_remote_warehouse' => $params->get('oasis_remote_warehouse'),
            'oasis_categories' => $params->get('oasis_categories'),
        ];

        return $data;
    }

    /**
     * @param $data
     *
     * @return int|bool
     *
     * @since 1.0
     */
    public function getCategoryId($data)
    {
        return $this->getData('#__virtuemart_categories', ['virtuemart_category_id'], ['slug' => $data->slug], true);
    }

    /**
     * @param $data
     *
     * @return int
     *
     * @since 1.0
     */
    public function getManufacturer($data): int
    {
        $manufacturer_id = $this->getData('#__virtuemart_manufacturers', ['virtuemart_manufacturer_id'], ['slug' => $data->slug], true);

        if (is_null($manufacturer_id)) {
            $manufacturer_id = $this->addData('#__virtuemart_manufacturers', ['virtuemart_manufacturercategories_id' => $this->getManufacturerCategories()]);

            $data_manuf = [
                'virtuemart_manufacturer_id' => $manufacturer_id,
                'mf_name' => $data->name,
                'slug' => $data->slug,
            ];
            $this->addData('#__virtuemart_manufacturers', $data_manuf, true);
            unset($data_manuf);

            $data_img = [
                'folder_name' => 'images/virtuemart/manufacturer',
                'img_url' => $data->logotype,
            ];

            $img = OasisHelper::saveImg($data_img);

            if ($img) {
                $this->addData('#__virtuemart_manufacturer_medias', [
                    'virtuemart_manufacturer_id' => $manufacturer_id,
                    'virtuemart_media_id' => $this->addVirtuemartMedias($img, 'manufacturer'),
                    'ordering' => 1,
                ]);
            }
        }

        return (int)$manufacturer_id;
    }

    /**
     *
     * @return int
     *
     * @since 1.0
     */
    public function getManufacturerCategories(): int
    {
        $manuf_cat_id = $this->getData('#__virtuemart_manufacturercategories', ['virtuemart_manufacturercategories_id'], ['mf_category_name' => 'oasis'], true);

        if (is_null($manuf_cat_id)) {
            $manuf_cat_id = $this->addData('#__virtuemart_manufacturercategories');

            $data_manuf_cat = [
                'virtuemart_manufacturercategories_id' => $manuf_cat_id,
                'mf_category_name' => 'oasis',
                'slug' => 'oasis',
            ];
            $this->addData('#__virtuemart_manufacturercategories', $data_manuf_cat, true);
            unset($data_manuf_cat);
        }

        return (int)$manuf_cat_id;
    }

    /**
     * @param        $img
     * @param string $file_type
     * @return int
     *
     * @since 1.0
     */
    public function addVirtuemartMedias($img, string $file_type = 'product'): int
    {
        $data = [
            'file_title' => pathinfo($img)['filename'],
            'file_description' => '',
            'file_meta' => '',
            'file_class' => '',
            'file_mimetype' => 'image/jpeg',
            'file_type' => $file_type,
            'file_url' => $img,
            'file_url_thumb' => '',
            'file_params' => '',
            'file_lang' => '',
            'created_on' => date('Y-m-d H:i:s'),
        ];

        return $this->addData('#__virtuemart_medias', $data);
    }

    /**
     * @param        $table
     * @param        $dataSelect
     * @param array  $dataWhere
     * @param bool   $lang
     * @param string $funcName
     * @return mixed
     *
     * @since 1.0
     */
    public function getData($table, $dataSelect, array $dataWhere = [], bool $lang = false, string $funcName = '')
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
                $query->where($this->db->quoteName($key) . ' = ' . $this->db->quote($value));
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
     *
     * @return int
     *
     * @since 1.0
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
     *
     * @param bool $lang
     * @since 1.0
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
     *
     * @param bool $lang
     * @since 1.0
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
