<?php
defined('_JEXEC') or die;

use Joomla\CMS\HTML\HTMLHelper;
use Joomla\CMS\Language\Text;
use Oasiscatalog\Component\Oasis\Administrator\Helper\Config as OasisConfig;


$wa = $this->document->getWebAssetManager();
$wa->useScript('keepalive');
$wa->useScript('form.validate');

$cf = OasisConfig::instance(['init' => true]);

$optBar = $cf->getOptBar();
$cron_key = $cf->getCronKey();
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
						<div class="progress-bar" role="progressbar" style="width: <?php echo $optBar['p_total']; ?>%" aria-label="<?php echo Text::_('COM_OASIS_PROGRESS_TOTAL', true); ?>" aria-valuenow="<?php echo $optBar['p_total']; ?>" aria-valuemin="0" aria-valuemax="100"></div>
					</div>
				</div>
			</div>
			<?php if (!empty($cf->limit)) { ?>
				<div class="row my-2">
					<div class="col-md-5 col-sm-12">
						<h3><?php echo sprintf(Text::_('COM_OASIS_PROGRESS_STEP', true), $optBar['step'] + 1, $optBar['steps']); ?></h3>
					</div>
					<div class="col-md-7 col-sm-12">
						<div class="progress">
							<div class="progress-bar" role="progressbar" style="width: <?php echo $optBar['p_step']; ?>%" aria-label="<?php echo Text::_('COM_OASIS_PROGRESS_STEP', true); ?>" aria-valuenow="<?php echo $optBar['p_step']; ?>" aria-valuemin="0" aria-valuemax="100"></div>
						</div>
					</div>
				</div>
			<?php } ?>
			<p><?php
				echo Text::_('COM_OASIS_PROGRESS_DATE', true);
				echo ($optBar['date'] ?? '');
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
								<?php foreach ($this->form->getFieldset($name) as $field) {	?>
								<div class="control-group">
									<div class="control-label">
										<?php echo $field->label; ?>
									</div>
									<div class="controls">
										<?php echo $field->input; ?>
									</div>
								</div>
								<?php } ?>
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
			<input type="hidden" name="task" value=""/>
			<?php //echo JHtml::_('form.token'); ?>
			<?php echo HTMLHelper::_('form.token'); ?>
		</form>
	</div>
</div>
<div class="row text-center">
	<div class="col-md-12">
		<a href="https://www.oasiscatalog.com/" target="_blank">Oasis</a> 4.1 | Copyright (C) <?php echo date('Y'); ?>
	</div>
</div>
<div id="oasis-relation" class="modal fade" tabindex="-1" tabindex="-1" aria-modal="true" role="dialog">
	<div class="modal-dialog modal-dialog-centered modal-dialog-scrollable">
		<div class="modal-content">
			<div class="modal-header">
				<h5 class="modal-title"><?= Text::_('COM_OASIS_HEAD_SELECT_CATEGORIES', true) ?></h5>
				<button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
			</div>
			<div class="modal-body"></div>
			<div class="modal-footer">
				<button type="button" class="btn btn-danger mx-3 js-clear"><?= Text::_('COM_OASIS_BTN_CLEAR', true) ?></button>
				<button type="button" class="btn btn-primary js-ok"><?= Text::_('COM_OASIS_BTN_SELECT', true) ?></button>
			</div>
		</div>
	</div>
</div>