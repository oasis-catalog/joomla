<?php

namespace Oasiscatalog\Component\Oasis\Administrator\Controller;

defined('_JEXEC') or die;

use Joomla\CMS\MVC\Controller\BaseController;


class DisplayController extends BaseController {
    /**
     * @var string
     * @since 4.0
     */
    protected $default_view = 'oasis';

    public function display($cachable = false, $urlparams = []) {
        return parent::display($cachable, $urlparams);
    }

}