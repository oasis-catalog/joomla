<?php
/**
 * @package     Oasis
 * @subpackage  Administrator
 *
 * @author      Viktor G. <ever2013@mail.ru>
 * @copyright   Copyright (C) 2023 Oasiscatalog. All rights reserved.
 * @license     GNU/GPLv3 http://www.gnu.org/licenses/gpl-3.0.html
 * @link        https://www.oasiscatalog.com/
 */

namespace Oasiscatalog\Component\Oasis\Administrator\Field;

use Joomla\CMS\Component\ComponentHelper;
use Joomla\CMS\Form\FormField;
use Oasiscatalog\Component\Oasis\Administrator\Helper\OasisHelper;

defined('_JEXEC') or die;

/**
 * The TreeCheckbox form field class of the Oasis component
 *
 * @since 4.0
 */
class TreeCheckboxField extends FormField
{
    /**
     * @var string
     *
     * @since 4.0
     */
    protected $type = 'TreeCheckbox';

    /**
     * @var string
     *
     * @since 4.0
     */
    private $treeCats = '';

    /**
     * @return string
     *
     * @since 4.0
     */
    public function getInput()
    {
        $params = ComponentHelper::getParams('com_oasis');
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
     * @param int $parent_id
     * @param bool $sw
     *
     * @since 4.0
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
