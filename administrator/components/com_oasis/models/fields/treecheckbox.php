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

use Joomla\CMS\Factory;
use Joomla\CMS\Form\FormField;

/**
 * Класс поля формы TreeCheckbox компонента Oasis.
 *
 * @since 1.0
 */
class JFormFieldTreeCheckbox extends FormField
{
    /**
     * @var string
     * @since 1.0
     */
    protected $type = 'TreeCheckbox';

    /**
     * @var string
     * @since 1.0
     */
    private $treeCats = '';

    /**
     *
     * @return string
     *
     * @since 1.0
     */
    public function getInput()
    {
        $params = JComponentHelper::getParams('com_oasis');
        $api_key = $params->get('oasis_api_key');

        if (isset($api_key) && $api_key !== '') {
            $categories = OasisHelper::getOasisCategories();
        }

        $arr_cat = [];
        foreach ($categories as $item) {
            if (empty($arr_cat[(int)$item->parent_id])) {
                $arr_cat[(int)$item->parent_id] = [];
            }
            $arr_cat[(int)$item->parent_id][] = (array)$item;
        }

        $this->buildTreeCats($arr_cat);
        unset($arr_cat, $item);

        return $this->treeCats;
    }

    /**
     * @param      $data
     * @param int  $parent_id
     * @param bool $sw
     *
     *
     * @since 1.0
     */
    public function buildTreeCats($data, int $parent_id = 0, bool $sw = false)
    {
        if (empty($data[$parent_id])) {
            return;
        }

        $this->treeCats .= $sw ? '<fieldset><legend></legend>' . PHP_EOL : '';
        for ($i = 0; $i < count($data[$parent_id]); $i++) {
            $checked = $data[$parent_id][$i]['level'] == 1 ? ' checked' : '';
            $this->treeCats .= '<label><input id="categories" type="checkbox" name="jform[oasis_categories][]" value="' . $data[$parent_id][$i]['id'] . '"' . $checked . '> ' . $data[$parent_id][$i]['name'] . '</label>' . PHP_EOL;
            $this->buildTreeCats($data, $data[$parent_id][$i]['id'], true);
        }
        $this->treeCats .= $sw ? '</fieldset>' . PHP_EOL : '';
    }
}
