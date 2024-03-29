<?php

namespace Oasiscatalog\Component\Oasis\Administrator\Controller;

defined('_JEXEC') or die;

use Joomla\CMS\Component\ComponentHelper;
use Joomla\CMS\Factory;
use Joomla\CMS\Language\Text;
use Joomla\CMS\MVC\Controller\BaseController;

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
        $data['oasis_currency'] = $_POST['jform']['oasis_currency'] ?? 'rub';
        $data['oasis_no_vat'] = $_POST['jform']['oasis_no_vat'] ?? 0;
        $data['oasis_not_on_order'] = $_POST['jform']['oasis_not_on_order'] ?? '';
        $data['oasis_price_from'] = $_POST['jform']['oasis_price_from'] ?? '';
        $data['oasis_price_to'] = $_POST['jform']['oasis_price_to'] ?? '';
        $data['oasis_rating'] = $_POST['jform']['oasis_rating'] ?? '';
        $data['oasis_warehouse_moscow'] = $_POST['jform']['oasis_warehouse_moscow'] ?? '';
        $data['oasis_warehouse_europe'] = $_POST['jform']['oasis_warehouse_europe'] ?? '';
        $data['oasis_remote_warehouse'] = $_POST['jform']['oasis_remote_warehouse'] ?? '';
        $data['oasis_limit'] = $_POST['jform']['oasis_limit'] ?? '';
        $data['oasis_factor'] = $_POST['jform']['oasis_factor'] ?? '';
        $data['oasis_increase'] = $_POST['jform']['oasis_increase'] ?? '';
        $data['oasis_dealer'] = $_POST['jform']['oasis_dealer'] ?? '';
        $data['oasis_step'] = $_POST['jform']['oasis_step'] ?? '';
        $data['oasis_categories'] = $_POST['jform']['oasis_categories'] ?? [];
        $data['oasis_host'] = $_POST['jform']['oasis_host'] ?? '';

        $params = ComponentHelper::getParams('com_oasis');

        foreach ($data as $key => $value) {
            $params->set($key, $value);
            $db = Factory::getContainer()->get('DatabaseDriver');
            $query = $db->getQuery(true);
            $query->update($db->quoteName('#__extensions'));
            $query->set($db->quoteName('params') . ' = ' . $db->quote((string)$params));
            $query->where($db->quoteName('element') . ' = ' . $db->quote('com_oasis'));
            $query->where($db->quoteName('type') . ' = ' . $db->quote('component'));
            $db->setQuery($query);
            $db->execute();
        }

        $this->setRedirect('index.php?option=com_oasis', Text::_('COM_OASIS_OPTION_SAVE'), 'Message');
        $this->redirect();
    }
}