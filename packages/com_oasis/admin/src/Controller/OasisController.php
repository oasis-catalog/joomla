<?php

namespace Oasiscatalog\Component\Oasis\Administrator\Controller;

defined('_JEXEC') or die;


use Joomla\CMS\Component\ComponentHelper;
use Joomla\CMS\Factory;
use Joomla\CMS\Language\Text;
use Joomla\CMS\MVC\Controller\BaseController;
use Oasiscatalog\Component\Oasis\Administrator\Helper\OasisHelper;


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
class OasisController extends BaseController
{

    /**
     * Save params oasis
     *
     * @since 4.0
     */
    public function apply()
    {
        $post = $_POST['jform'] ?? [];
        $data = [
            'currency' =>           $post['currency'] ?? 'rub',
            'limit' =>              $post['limit'] ?? '',
            'categories' =>         $post['categories'] ?? [],
            'categories_rel' =>     $post['categories_rel'] ?? [],
            'is_no_vat' =>          $post['is_no_vat'] ?? 0,
            'is_not_on_order' =>    $post['is_not_on_order'] ?? '',
            'price_from' =>         $post['price_from'] ?? '',
            'price_to' =>           $post['price_to'] ?? '',
            'rating' =>             $post['rating'] ?? '',
            'is_wh_moscow' =>       $post['is_wh_moscow'] ?? '',
            'is_wh_europe' =>       $post['is_wh_europe'] ?? '',
            'is_wh_remote' =>       $post['is_wh_remote'] ?? '',
            'price_factor' =>       $post['price_factor'] ?? '',
            'price_increase' =>     $post['price_increase'] ?? '',
            'is_price_dealer' =>    $post['is_price_dealer'] ?? '',
            'is_not_up_cat' =>      $post['is_not_up_cat'] ?? '',
            'is_cdn_photo' =>       $post['is_cdn_photo'] ?? '',
            'is_up_photo' =>        $post['is_up_photo'] ?? '',
            'is_import_anytime' =>  $post['is_import_anytime'] ?? '',

            // clear progress
            'progress_total' => 0,
            'progress_step' => 0,
            'progress_item' => 0,
            'progress_step_item' => 0,
            'progress_step_total' => 0,
        ];

        $params = ComponentHelper::getParams('com_oasis');

        foreach ($data as $key => $value) {
            $params->set($key, $value);
        }

        $db = Factory::getContainer()->get('DatabaseDriver');
        $query = $db->getQuery(true)
            ->update($db->quoteName('#__extensions'))
            ->set($db->quoteName('params') . ' = ' . $db->quote((string)$params))
            ->where($db->quoteName('element') . ' = ' . $db->quote('com_oasis'))
            ->where($db->quoteName('type') . ' = ' . $db->quote('component'));
        $db->setQuery($query);
        $db->execute();

        $query = $db->getQuery(true)
            ->update($db->quoteName('#__extensions'))
            ->set($db->quoteName('enabled') . ' = ' . ($data['is_cdn_photo'] ? '1' : '0'))
            ->where($db->quoteName('element') . ' = ' . $db->quote('oasis'))
            ->where($db->quoteName('type') . ' = ' . $db->quote('plugin'))
            ->where($db->quoteName('folder') . ' = ' . $db->quote('system'));
        $db->setQuery($query);
        $db->execute();

        $this->setRedirect('index.php?option=com_oasis', Text::_('COM_OASIS_OPTION_SAVE'), 'Message');
        $this->redirect();
    }


    public function get_all_categories() {
        if (!class_exists('vmCustomPlugin')){
            require(JPATH_ROOT .'/administrator/components/com_virtuemart/helpers/config.php');
            if(class_exists('VmConfig')) \VmConfig::loadConfig();
        }

        $categoryModel = \VmModel::getModel('Category');
        $cats = $categoryModel->getCategoryTree(0, -1, false);
        $arr = [];
        foreach ($cats as $cat) {
            if (empty($arr[$cat->category_parent_id])) {
                $arr[$cat->category_parent_id] = [];
            }
            $arr[$cat->category_parent_id][] = [
                'id' => $cat->virtuemart_category_id,
                'name' => $cat->category_name,
            ];
        }

        echo '<div class="oa-tree">
            <div class="oa-tree-ctrl">
                <button type="button" class="btn btn-sm btn-light oa-tree-ctrl-m">'.Text::_('COM_OASIS_BTN_COLLAPSE_ALL', true).'</button>
                <button type="button" class="btn btn-sm btn-light oa-tree-ctrl-p">'.Text::_('COM_OASIS_BTN_EXPAND_ALL', true).'</button>
            </div>' . OasisHelper::buildTreeRadioCats($arr) . '</div>';
        exit();
    }
}