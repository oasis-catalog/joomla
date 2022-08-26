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

/**
 *
 * @package     Oasis
 * @subpackage  Imports
 *
 * @since 2.0
 */
class OasisViewOasis extends JViewLegacy
{
    /**
     * The form with the field
     *
     * @var    JForm
     *
     * @since 2.0
     */
    protected $form;

    /**
     * JInput.
     *
     * @var    JInput
     *
     * @since 2.0
     */
    protected $input;

    /**
     * Execute and display a template script.
     *
     * @param string $tpl The name of the template file to parse; automatically searches through the template paths.
     * @return  mixed  A string if successful, otherwise a JError object.
     * @throws  Exception
     * @throws  RuntimeException
     * @throws  InvalidArgumentException
     * @throws  UnexpectedValueException
     *
     * @since 2.0
     */
    public function display($tpl = null)
    {
        $params = JComponentHelper::getParams('com_oasis');
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
            echo '<div class="alert alert-danger text-center" role="alert"><span class="icon-info"> </span> ' . JText::_('COM_OASIS_TEXT_NOT_VALID', true) . '</div>';
        }

        $this->setDocument();
    }

    /**
     * Add the page title and toolbar.
     *
     * @return  void
     * @throws  Exception
     *
     * @since 2.0
     */
    private function addToolbar($valid = true)
    {
        JToolbarHelper::title('Oasis - ' . JText::_('COM_OASIS_TITLE_IMPORTS'), 'upload');

        if ($valid) {
            JToolbarHelper::apply('oasis.apply');
        }

        JToolbarHelper::preferences('com_oasis');
    }

    /**
     * @since 2.2
     */
    private function setDocument()
    {
        $document = JFactory::getDocument();
        $document->setTitle(JText::_('COM_OASIS_TITLE'));
        $document->addStyleSheet(JURI::base() . 'components/com_oasis/assets/css/stylesheet.css','text/css','screen');
        //TODO adapt to joomla 4
    }
}

