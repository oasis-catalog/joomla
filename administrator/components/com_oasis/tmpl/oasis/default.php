<?php

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

use Joomla\CMS\Component\ComponentHelper;
use Joomla\CMS\HTML\HTMLHelper;
use Joomla\CMS\Language\Text;
use Joomla\CMS\Layout\LayoutHelper;
use Joomla\CMS\Router\Route;

$wa = $this->document->getWebAssetManager();
$wa->useScript('keepalive');
$wa->useScript('form.validate');

$params = ComponentHelper::getParams('com_oasis');
$cron_key = md5($params->get('oasis_api_key'));

$progressTotal = (int)$params->get('progress_total');
$progressItem = (int)$params->get('progress_item');
$progressStepTotal = (int)$params->get('progress_step_total');
$progressStepItem = (int)$params->get('progress_step_item');
$progressDate = $params->get('progress_date');
$limit = (int)$params->get('oasis_limit');

if (!empty($limit)) {
    $step = (int)$params->get('oasis_step');
    $stepTotal = !empty($progressTotal) ? ceil($progressTotal / $limit) : 0;
}

if (!empty($progressTotal) && !empty($progressItem)) {
    $percentTotal = round(($progressItem / $progressTotal) * 100);
} else {
    $percentTotal = 0;
}

if (!empty($progressStepTotal) && !empty($progressStepItem)) {
    $percentStep = round(($progressStepItem / $progressStepTotal) * 100);
} else {
    $percentStep = 0;
}
?>
<div class="row">
    <div class="col-md-12">
        <div class="progress-notice">
            <div class="row my-2">
                <div class="col-md-5 col-sm-12">
                    <h3><?php echo Text::_('COM_OASIS_PROGRESS_TOTAL', true); ?></h3>
                </div>
                <div class="col-md-7 col-sm-12">
                    <div class="progress">
                        <div class="progress-bar" role="progressbar" style="width: <?php echo $percentTotal; ?>%" aria-label="<?php echo Text::_('COM_OASIS_PROGRESS_TOTAL', true); ?>" aria-valuenow="<?php echo $percentTotal; ?>" aria-valuemin="0" aria-valuemax="100"></div>
                    </div>
                </div>
            </div>
            <?php if (!empty($limit)) { ?>
                <div class="row my-2">
                    <div class="col-md-5 col-sm-12">
                        <h3><?php echo sprintf(Text::_('COM_OASIS_PROGRESS_STEP', true), ++$step, $stepTotal); ?></h3>
                    </div>
                    <div class="col-md-7 col-sm-12">
                        <div class="progress">
                            <div class="progress-bar" role="progressbar" style="width: <?php echo $percentStep; ?>%" aria-label="<?php echo Text::_('COM_OASIS_PROGRESS_STEP', true); ?>" aria-valuenow="<?php echo $percentStep; ?>" aria-valuemin="0" aria-valuemax="100"></div>
                        </div>
                    </div>
                </div>
            <?php } ?>
            <p><?php
                echo Text::_('COM_OASIS_PROGRESS_DATE', true);
                echo !empty($progressDate) ? $progressDate : '';
                ?></p>
        </div>
    </div>
</div>
<div class="row">
    <div class="col-md-12">
        <div class="row">
            <div class="col-md-12">
                <div class="alert alert-info">
                    <h4 class="alert-heading"><?php echo Text::_('COM_OASIS_CRON_TITLE', true); ?></h4>
                    <p><?php echo Text::_('COM_OASIS_CRON_DESC', true); ?></p>
                    <div class="row">
                        <div class="col-md-4"><?php echo Text::_('COM_OASIS_CRON_TEXT_IMPORT', true); ?></div>
                        <div class="col-md-8"><?php echo '' . DS . 'usr' . DS . 'bin' . DS . 'php ' . JPATH_CLI . DS . 'joomla.php oasis:import --key=' . $cron_key; ?></div>
                    </div>
                    <div class="row">
                        <div class="col-md-4"><?php echo Text::_('COM_OASIS_CRON_TEXT_UP', true); ?></div>
                        <div class="col-md-8"><?php echo '' . DS . 'usr' . DS . 'bin' . DS . 'php ' . JPATH_CLI . DS . 'joomla.php oasis:import --key=' . $cron_key . ' --up'; ?></div>
                    </div>
                </div>
            </div>
        </div>
        <form method="post" action="index.php?option=com_oasis&view=oasis" id="adminForm" name="adminForm" class="main-card form-validate">
            <?php echo JHtml::_('bootstrap.startTabSet', 'oasisTab', ['active' => 'options']); ?>
            <?php foreach ($this->form->getFieldsets() as $name => $fieldset) {
                if ($name === 'options') {
                    ?>
                    <?php echo JHtml::_('bootstrap.addTab', 'oasisTab', 'options', Text::_('COM_OASIS_OPTION', true)); ?>
                    <fieldset class="options-form">
                        <div class="form-grid">
                            <legend><?php echo Text::_($fieldset->label); ?></legend>
                            <div class="row">
                                <div class="col-md-6">
                                    <?php foreach ($this->form->getFieldset($name) as $field) {
                                        if ($field->type !== 'TreeCheckbox') {
                                            ?>
                                            <div class="control-group">
                                                <div class="control-label">
                                                    <?php echo $field->label; ?>
                                                </div>
                                                <div class="controls">
                                                    <?php echo $field->input; ?>
                                                </div>
                                            </div>
                                            <?php
                                        }
                                    } ?>
                                </div>
                                <div class="col-md-6">
                                    <?php foreach ($this->form->getFieldset($name) as $field) {
                                        if ($field->type === 'TreeCheckbox') {
                                            $params = ComponentHelper::getParams('com_oasis');
                                            ?>
                                            <div class="control-group">
                                                <div class="control-label">
                                                    <?php echo $field->label; ?>
                                                </div>
                                                <div class="controls">
                                                    <div class="tree-select razvernut">
                                                        <?php echo $field->input; ?>
                                                    </div>
                                                </div>
                                            </div>
                                            <?php
                                        }
                                    } ?>
                                </div>
                            </div>
                        </div>
                    </fieldset>
                    <?php echo JHtml::_('bootstrap.endTab'); ?>
                <?php } else { ?>
                    <?php echo JHtml::_('bootstrap.addTab', 'oasisTab', 'orders', Text::_('COM_OASIS_ORDER', true)); ?>
                    <fieldset class="options-form">
                        <legend><?php echo Text::_($fieldset->label); ?></legend>
                        <div class="form-grid">
                            <div class="row">
                                <div class="col-md-12">
                                    <?php foreach ($this->form->getFieldset($name) as $field) { ?>
                                        <div class="control-group">
                                            <?php echo $field->input; ?>
                                        </div>
                                    <?php } ?>
                                </div>
                            </div>
                        </div>
                    </fieldset>
                    <?php echo JHtml::_('bootstrap.endTab'); ?>
                    <?php
                }
            }
            ?>
            <?php echo JHtml::_('bootstrap.endTabSet'); ?>
            <?php echo $this->form->renderField('title'); ?>
            <input type="hidden" name="jform[oasis_host]" value="<?php echo $_SERVER['REQUEST_SCHEME'] . '://' . $_SERVER['HTTP_HOST']; ?>"/>
            <input type="hidden" name="task" value=""/>
            <?php //echo JHtml::_('form.token'); ?>
            <?php echo HTMLHelper::_('form.token'); ?>
        </form>
    </div>
</div>
<div class="row text-center">
    <div class="col-md-12">
        <a href="https://www.oasiscatalog.com/" target="_blank">Oasis</a> 4.0 | Copyright (C) <?php echo date('Y'); ?>
    </div>
</div>

<script type="text/javascript">
    function sendHere(postfix) {
        var token = jQuery("#token").attr("name");
        var $button = jQuery("#btn-" + postfix).button('loading');

        jQuery.ajax({
            data: {
                [token]: "1",
                task: "oasis.sendOrder",
                format: "json",
                orderId: jQuery("#order-" + postfix).attr("value")
            },
            success: function () {
                setTimeout(function () {
                    $button.button('complete');
                    location.reload();
                }, 2 * 1000);
            },
            error: function () {
            },
        });
    }

    var t = document.forms.adminForm;
    [].forEach.call(t.querySelectorAll('fieldset'), function (eFieldset) {
        var main = [].filter.call(t.querySelectorAll('[type="checkbox"]'), function (element) {
            return element.parentNode.nextElementSibling == eFieldset;
        });
        main.forEach(function (eMain) {
            var l = [].filter.call(eFieldset.querySelectorAll('legend'), function (e) {
                return e.parentNode == eFieldset;
            });
            l.forEach(function (eL) {
                var all = eFieldset.querySelectorAll('[type="checkbox"]');
                eL.onclick = Razvernut;
                eFieldset.onchange = Razvernut;

                <?php if ($params->get('oasis_categories')) { ?>
                var category = <?php echo '[' . implode(',', $params->get('oasis_categories')) . ']'; ?>;
                for (var i = 0; i < category.length; i++)
                    t.querySelector('[type="checkbox"][value="' + category[i] + '"]').checked = true;
                Razvernut('true');
                <?php } ?>

                function Razvernut(load) {
                    var allChecked = eFieldset.querySelectorAll('[type="checkbox"]:checked').length;
                    eMain.checked = allChecked == all.length;
                    eMain.indeterminate = allChecked > 0 && allChecked < all.length;
                    if (eMain.indeterminate || eMain.checked || ((eFieldset.className == '') && (load != 'true'))) {
                        eFieldset.className = 'razvernut';
                    } else {
                        eFieldset.className = '';
                    }
                }

                eMain.onclick = function () {
                    for (var i = 0; i < all.length; i++)
                        all[i].checked = this.checked;
                    if (this.checked) {
                        eFieldset.className = 'razvernut';
                    } else {
                        eFieldset.className = '';
                    }
                }
            });
        });
    });
</script>