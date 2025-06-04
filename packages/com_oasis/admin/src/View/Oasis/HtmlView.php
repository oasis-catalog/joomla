<?php

namespace Oasiscatalog\Component\Oasis\Administrator\View\Oasis;

defined('_JEXEC') or die;

use Joomla\CMS\Factory;
use Joomla\CMS\MVC\View\HtmlView as BaseHtmlView;
use Joomla\CMS\Toolbar\ToolbarHelper;
use Joomla\CMS\Language\Text;
use Joomla\CMS\Uri\Uri;
use Joomla\CMS\Document\Document;
use Oasiscatalog\Component\Oasis\Administrator\Helper\OasisHelper;
use Oasiscatalog\Component\Oasis\Administrator\Helper\Config as OasisConfig;


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
        $cf = OasisConfig::instance(['init' => true]);
        $api_success = false;

        if (!empty($cf->api_key)) {
            $api_success = $cf->checkApi();
            if ($api_success) {
                $this->form = $this->get('Form');
            }
        }

        if ($api_success) {
            $this->addToolbar();
            parent::display($tpl);
        } else {
            $this->addToolbar(false);
            echo '<div class="alert alert-danger text-center" role="alert"><span class="icon-info"> </span> ' . Text::_('COM_OASIS_TEXT_NOT_VALID', true) . '</div>';
        }
        $document = Factory::getDocument();
        $document->setTitle(Text::_('COM_OASIS_TITLE'));
        $wa = $document->getWebAssetManager();
        $wa->useScript('jquery')
            ->useScript('bootstrap.modal');
        $wa->registerAndUseStyle('com_oasis.css', URI::base() . 'components/com_oasis/assets/css/stylesheet.css', [], [], []);
        $wa->registerAndUseScript('com_oasis.tree', URI::base() . 'components/com_oasis/assets/js/tree.js', [], [], []);
        $wa->registerAndUseScript('com_oasis.common', URI::base() . 'components/com_oasis/assets/js/common.js', [], [], []);
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
}
