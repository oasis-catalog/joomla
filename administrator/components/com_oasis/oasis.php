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

// Access check.
if (!JFactory::getUser()->authorise('core.manage', 'com_oasis'))
{
	throw new Exception(JText::_('JERROR_ALERTNOAUTHOR'), 404);
}

// Get the input object
$jinput = JFactory::getApplication()->input;

// Include dependencies
jimport('joomla.application.component.controller');

// Define our version number
define('OASIS_VERSION', '2.0');

// Set CLI mode
define('OASIS_CLI', false);

// Setup the autoloader
JLoader::registerPrefix('Oasis', JPATH_ADMINISTRATOR . '/components/com_oasis');

// Set the folder path to the models
JModelLegacy::addIncludePath(JPATH_ADMINISTRATOR . '/components/com_oasis/models');

// Load the helper class for the submenu
require_once JPATH_ADMINISTRATOR . '/components/com_oasis/helper/oasis.php';

try
{
	$controller = JControllerLegacy::getInstance('oasis');
	$controller->execute($jinput->get('task', 'display'));
	$controller->redirect();
}
catch (Exception $e)
{

}
