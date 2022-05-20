<?php

use Joomla\Registry\Registry;

defined('_JEXEC') or die;

/**
 * Base controller.
 *
 * @package     Oasis
 * @subpackage  Administrator
 *
 * @since 2.0
 */
class OasisController extends JControllerLegacy
{
    protected $default_view = 'oasis';

    /**
     * Typical view method for MVC based architecture
     *
     * This function is provide as a default implementation, in most cases
     * you will need to override it in your own controllers.
     *
     * @param boolean $cachable If true, the view output will be cached
     * @param array $urlparams An array of safe url parameters and their variable types, for valid values see {@link JFilterInput::clean()}.
     * @return  JControllerLegacy  A JControllerLegacy object to support chaining.
     *
     * @since 2.0
     */
    public function display($cachable = false, $urlparams = [])
    {
        parent::display($cachable, $urlparams);

        return $this;
    }
}
