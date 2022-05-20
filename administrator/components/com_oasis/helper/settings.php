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

use Joomla\Registry\Registry;

/**
 * Settings class.
 *
 * @package     Oasis
 * @subpackage  Helper
 *
 * @since 2.0
 */
final class OasisHelperSettings
{
    /**
     * Contains the Oasis settings
     *
     * @var    Registry
     *
     * @since 2.0
     */
    private $params = false;

    /**
     * Construct the Settings helper.
     *
     * @param JDatabaseDriver $db Joomla database connector
     *
     * @since 2.0
     */
    public function __construct(JDatabaseDriver $db)
    {
        $query = $db->getQuery(true)
            ->select($db->quoteName('params'))
            ->from($db->quoteName('#__extensions'))
            ->where($db->quoteName('element') . ' = ' . $db->quote('com_oasis'))
            ->where($db->quoteName('type') . ' = ' . $db->quote('component'));
        $db->setQuery($query);
        $settings = $db->loadResult();
        $registry = new Registry($settings);
        $this->params = $registry;
    }

    /**
     * Get a requested value.
     *
     * @param string $setting The setting to get the value for
     * @param mixed $default The default value if no $setting is found
     * @return  array  The field option objects.
     *
     * @since 2.0
     */
    public function get($setting, $default = false)
    {
        return $this->params->get($setting, $default);
    }
}
