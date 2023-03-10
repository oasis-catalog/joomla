<?php

namespace Oasiscatalog\Component\Oasis\Administrator\View\Oasis;

defined('_JEXEC') or die;

use Joomla\CMS\Component\ComponentHelper;
use Joomla\CMS\Factory;
use Joomla\CMS\MVC\View\HtmlView as BaseHtmlView;
use Joomla\CMS\Toolbar\ToolbarHelper;
use Joomla\CMS\Language\Text;
use Joomla\CMS\Uri\Uri;
use Oasiscatalog\Component\Oasis\Administrator\Helper\OasisHelper;

/**
 * @package     Oasis
 * @subpackage  Administrator
 *
 * @author      Viktor G. <ever2013@mail.ru>
 * @copyright   Copyright (C) 2023 Oasiscatalog. All rights reserved.
 * @license     GNU/GPLv3 http://www.gnu.org/licenses/gpl-3.0.html
 * @link        https://www.oasiscatalog.com/
 */

defined('_JEXEC') or die;

/**
 *
 * @package     Oasis
 * @subpackage  Imports
 *
 * @since 4.0
 */
class HtmlView extends BaseHtmlView
{
    /**
     * The form with the field
     *
     * @var    JForm
     *
     * @since 4.0
     */
    protected $form;

    /**
     * JInput.
     *
     * @var    JInput
     *
     * @since 4.0
     */
    protected $input;

    /**
     * Execute and display a template script.
     *
     * @param null $tpl The name of the template file to parse; automatically searches through the template paths.
     * @return  mixed  A string if successful, otherwise a JError object.
     *
     * @throws \Exception
     * @since 4.0
     */
    public function display($tpl = null)
    {
        $params = ComponentHelper::getParams('com_oasis');
        $api_key = $params->get('oasis_api_key');

        if (isset($api_key) && $api_key !== '') {
            $currencies = (bool)OasisHelper::getOasisCurrencies();

            if ($currencies) {
                $this->form = $this->get('Form');
            }
        }

        if (isset($currencies) && $currencies) {
            $this->addToolbar();
            parent::display($tpl);
        } else {
            $this->addToolbar(false);
            echo '<div class="alert alert-danger text-center" role="alert"><span class="icon-info"> </span> ' . Text::_('COM_OASIS_TEXT_NOT_VALID', true) . '</div>';
        }
        $this->setDocument();
    }

    /**
     * Add the page title and toolbar.
     *
     * @param bool $valid
     * @return  void
     * @since 4.0
     */
    private function addToolbar($valid = true)
    {
        ToolbarHelper::title(Text::_('COM_OASIS_TITLE_IMPORTS'));

        if ($valid) {
            ToolbarHelper::apply('oasis.apply');
        }

        ToolbarHelper::preferences('com_oasis');
    }

    /**
     * @since 4.0
     */
    private function setDocument()
    {
        $document = Factory::getApplication()->getDocument();
        $document->setTitle(Text::_('COM_OASIS_TITLE'));

        $wa = Factory::getApplication()->getDocument()->getWebAssetManager();
        $wa->registerAndUseStyle('oasis', URI::base() . 'components/com_oasis/assets/css/stylesheet.css', [], [], []);
    }
}
