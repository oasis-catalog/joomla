<?php

namespace Oasiscatalog\Component\Oasis\Administrator\Controller;

defined('_JEXEC') or die;

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