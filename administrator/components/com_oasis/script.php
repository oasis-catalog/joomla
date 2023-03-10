<?php
/**
 * @package     VirtueMart
 * @subpackage  OASIS
 *
 * @author      Viktor G. <ever2013@mail.ru>
 * @copyright   Copyright (C) 2023 oasiscatalog.com. All rights reserved.
 * @license     GNU/GPLv3 http://www.gnu.org/licenses/gpl-3.0.html
 * @link        https://www.oasiscatalog.com/
 */

defined('_JEXEC') or die;

use Joomla\CMS\Factory;
use Joomla\CMS\Language\Text;
use Joomla\CMS\Log\Log;

/**
 * Oasis component import VirtueMart.
 *
 * @package     VirtueMart
 * @subpackage  Oasis
 *
 * @since 4.0
 */
class com_OasisInstallerScript
{

    /**
     * Extension script constructor.
     *
     * @return  void
     *
     * @since 4.0
     */
    public function __construct()
    {
        $this->minJoomla = '4.0';
    }

    /**
     * Actions to perform before installation.
     *
     * @param string $route The type of installation being run.
     * @param object $parent The parent object.
     * @return  bool  True on success | False on failure.
     * @throws Exception
     *
     * @since 4.0
     */
    public function preflight($route, $parent): bool
    {
        if ($route == 'install') {
            // Check for the minimum Joomla version before continuing
            if (!empty($this->minimumJoomla) && version_compare(JVERSION, $this->minimumJoomla, '<')) {
                Log::add(Text::sprintf('JLIB_INSTALLER_MINIMUM_JOOMLA', $this->minimumJoomla), Log::WARNING, 'jerror');

                return false;
            }

            // Check if virtuemart is installed
            if (!file_exists(JPATH_ADMINISTRATOR . '/components/com_virtuemart/')) {
                JFactory::getApplication()->enqueueMessage(JText::_('COM_OASIS_VIRTUEMART_NOT_INSTALLED'), 'error');

                return false;
            }
        }

        return true;
    }

    /**
     * Actions to perform after installation.
     *
     * @param object $parent The parent object.
     * @return  bool  True on success | False on failure.
     * @throws Exception
     *
     * @since 4.0
     */
    public function postflight($parent)
    {
        // Load the application
        $app = JFactory::getApplication();
        $db = Factory::getContainer()->get('DatabaseDriver');

        // Clear the cache
        $cache = Factory::getCache('com_virtuemart', '');
        $cache->clean('com_virtuemart');

        try {
            // Load the tasks
            $app->enqueueMessage(JText::_('COM_OASIS_COMPONENT_ENABLED'));
        } catch (Exception $e) {
            $app->enqueueMessage($e->getMessage());

            return false;
        }

        return true;
    }
}
