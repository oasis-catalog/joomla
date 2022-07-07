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

?>
<style type="text/css">
    .tree-select {
        line-height: normal;
    }

    .tree-select label {
        position: relative;
        display: block;
        padding: 0 0 0 1.2em !important;
        margin: 0;
    }

    .tree-select label:not(:nth-last-of-type(1)) {
        border-left: 1px solid #94a5bd;
    }

    .tree-select label:before {
        content: "";
        position: absolute;
        top: 0;
        left: 0;
        width: 1.1em;
        height: .5em;
        border-bottom: 1px solid #94a5bd;
    }

    .tree-select label:nth-last-of-type(1):before {
        border-left: 1px solid #94a5bd;
    }

    .tree-select fieldset,
    .tree-select fieldset[class=""] .razvernut {
        position: absolute;
        visibility: hidden;
        margin: 0;
        padding: 0 0 0 2em;
        border: none;
    }

    .tree-select fieldset:not(:last-child) {
        border-left: 1px solid #94a5bd;
    }

    .tree-select .razvernut {
        position: relative;
        visibility: visible;
    }

    .tree-select > fieldset > legend,
    .tree-select .razvernut > fieldset > legend {
        position: absolute;
        left: -5px;
        top: 2px;
        height: 10px;
        width: 10px;
        margin-top: -1em;
        padding: 0;
        border: 1px solid #94a5bd;
        border-radius: 2px;
        background-repeat: no-repeat;
        background-position: 50% 50%;
        background-color: #fff;
        background-image: linear-gradient(to left, #1b4964, #1b4964), linear-gradient(#1b4964, #1b4964), linear-gradient(315deg, #a0b6d8, #e8f3ff 60%, #fff 60%);
        background-size: 1px 5px, 5px 1px, 100% 100%;
        visibility: visible;
        cursor: pointer;
    }

    .tree-select fieldset[class=""] .razvernut fieldset legend {
        visibility: hidden;
    }

    .tree-select .razvernut > legend {
        background-image: linear-gradient(#1b4964, #1b4964) !important;
        background-size: 5px 1px !important;
    }

    .icon-publish:before {
        color: #fff;
    }
</style>
<?php
$cron_key = md5(JComponentHelper::getParams('com_oasis')->get('oasis_api_key'));
?>
<div class="row-fluid">
    <div class="row-fluid form-horizontal span10">
        <div class="row-fluid span12">
            <div class="alert alert-info">
                <h4 class="alert-heading"><?php echo JText::_('COM_OASIS_CRON_TITLE', true); ?></h4>
                <p><?php echo JText::_('COM_OASIS_CRON_DESC', true); ?></p>
                <div class="row-fluid">
                    <div class="span4"><?php echo JText::_('COM_OASIS_CRON_TEXT_IMPORT', true); ?></div>
                    <div class="span8"><?php echo '' . DS . 'usr' . DS . 'local' . DS . 'bin' . DS . 'php ' . JPATH_ADMINISTRATOR . DS . 'components' . DS . 'com_oasis' . DS . 'helper' . DS . 'cron.php --key=' . $cron_key; ?></div>
                </div>
                <div class="row-fluid">
                    <div class="span4"><?php echo JText::_('COM_OASIS_CRON_TEXT_UP', true); ?></div>
                    <div class="span8"><?php echo '' . DS . 'usr' . DS . 'local' . DS . 'bin' . DS . 'php ' . JPATH_ADMINISTRATOR . DS . 'components' . DS . 'com_oasis' . DS . 'helper' . DS . 'cron.php --key=' . $cron_key . ' --up'; ?></div>
                </div>
            </div>
        </div>
        <form method="post" action="index.php?option=com_oasis&view=oasis" id="adminForm" name="adminForm">
            <?php echo JHtml::_('bootstrap.startTabSet', 'oasisTab', ['active' => 'options']); ?>
            <?php foreach ($this->form->getFieldsets() as $name => $fieldset) {
                if ($name === 'options') {
                    ?>
                    <?php echo JHtml::_('bootstrap.addTab', 'oasisTab', 'options', JText::_('COM_OASIS_OPTION', true)); ?>
                    <fieldset class="adminform">
                        <legend><?php echo JText::_($fieldset->label); ?></legend>
                        <div class="row-fluid">
                            <div class="span6">
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
                            <div class="span6">
                                <?php foreach ($this->form->getFieldset($name) as $field) {
                                    if ($field->type === 'TreeCheckbox') {
                                        $params = JComponentHelper::getParams('com_oasis');
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
                    </fieldset>
                    <?php echo JHtml::_('bootstrap.endTab'); ?>
                <?php } else { ?>
                    <?php echo JHtml::_('bootstrap.addTab', 'oasisTab', 'orders', JText::_('COM_OASIS_ORDER', true)); ?>
                    <fieldset class="adminform">
                        <legend><?php echo JText::_($fieldset->label); ?></legend>
                        <div class="row-fluid">
                            <div class="span12">
                                <?php foreach ($this->form->getFieldset($name) as $field) { ?>
                                    <div class="control-group">
                                        <?php echo !empty($field->input) ? $field->input : ''; ?>
                                    </div>
                                <?php } ?>
                            </div>
                        </div>
                    </fieldset>
                    <?php echo JHtml::_('bootstrap.endTab'); ?>
                    <?php
                }
            }
            ?>
            <?php echo JHtml::_('bootstrap.endTabSet'); ?>
            <input type="hidden" name="jform[oasis_host]" value="<?php echo $_SERVER['REQUEST_SCHEME'] . '://' . $_SERVER['HTTP_HOST']; ?>"/>
            <input type="hidden" name="task" value=""/>
            <?php echo JHtml::_('form.token'); ?>
        </form>
    </div>
</div>
<div class="span10 center">
    <a href="https://www.oasiscatalog.com/" target="_blank">Oasis</a> 2.0 | Copyright (C) <?php echo date('Y'); ?>
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
