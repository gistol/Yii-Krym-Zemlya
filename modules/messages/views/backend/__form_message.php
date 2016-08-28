<?php
$action = Yii::app()->controller->createUrl('/messages/main/dosend', array('id' => $uid));

if (isset($apId) && $apId)
	$action = Yii::app()->controller->createUrl('/messages/main/dosend', array('id' => $uid, 'apId' => $apId));

$form=$this->beginWidget('CustomForm', array(
	'action' => $action,
	'id' => $this->modelName.'-form',
	'htmlOptions' => array('enctype'=>'multipart/form-data', 'class' => 'well form-disable-button-after-submit'),
	'enableAjaxValidation' =>false,
));
?>

	<?php echo $form->errorSummary($model); ?>

	<div class="rowold">
		<?php echo $form->labelEx($model,'message'); ?>
		<?php if (Yii::app()->user->checkAccess('messages_admin')): ?>
			<?php
			$this->widget('application.extensions.editMe.widgets.ExtEditMe', array(
				'model' => $model,
				'attribute' => 'message',
				'toolbar' => array(
					array('Source', '-', 'Bold', 'Italic', 'Underline', 'Strike'),
					array('Cut', 'Copy', 'Paste', 'PasteText', 'PasteFromWord', '-', 'Undo', 'Redo'),
					array('NumberedList', 'BulletedList', '-', 'JustifyLeft', 'JustifyCenter', 'JustifyRight', 'JustifyBlock'),
					array('Styles', 'Format', 'Font', 'FontSize', 'TextColor', 'BGColor'),
					array('Image', 'Link', 'Unlink', 'SpecialChar'),
				),
				'htmlOptions' => array('id' => 'message')
			));
			?>
		<?php else:?>
			<?php echo $form->textArea($model, 'message', array('class' => 'textarea-message')); ?>
		<?php endif;?>
		<?php echo $form->error($model, 'message'); ?>
	</div>
	<div class="clear">&nbsp;</div>

	<div class="rowold">
		<?php echo $form->labelEx($model,'file'); ?>
		<div class="padding-bottom10">
				<span class="label label-info">
					<?php echo Yii::t('module_messages', 'Supported file: {supportExt}.', array('{supportExt}' => $model->supportExt)).'';?>
					<br />
					<?php echo Yii::t('module_messages', 'Max file size: {fileMaxSize}.', array('{fileMaxSize}' => formatBytes($model->fileMaxSize))).'';?>
				</span>
		</div>

		<?php
		$this->widget('CMultiFileUpload', array(
			'name' => 'files',
			'accept' => "{$model->supportExtForUploader}",
			'duplicate' => ''.tt("The selected file has already been added!", "messages").'',
			'denied' => ''.tt("Unsupported file type!", "messages").'',
		));
		?>
		<?php echo $form->error($model,'file'); ?>
	</div>
	<div class="clear">&nbsp;</div>

	<?php if (Yii::app()->user->checkAccess('messages_admin')): ?>
		<div class="rowold buttons">
			<?php
				if(param('useBootstrap')){
					$this->widget('bootstrap.widgets.TbButton',
						array('buttonType'=>'submit',
							'type'=>'primary',
							//'icon'=>'ok white',
							'label'=> tt('Send', 'messages'),
							'htmlOptions' => array(
								'class' => 'btn-save submit-button'
							),
						));
				} else {
					echo CHtml::submitButton(tt('Send', 'messages'), array('class' => 'big_button button-blue submit-button'));
				}
			?>
		</div>
	<?php else:?>
		<div class="row buttons save">
			<?php echo CHtml::submitButton(tt('Send', 'messages'), array('class' => 'big_button button-blue submit-button')); ?>
		</div>
	<?php endif;?>
<?php $this->endWidget(); ?>