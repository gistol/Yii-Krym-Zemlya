<?php
$this->breadcrumbs=array(
	Yii::t('common', 'Mailing messages'),
);

$this->adminTitle = Yii::t('common', 'Mailing messages');
?>

<?php $this->renderPartial('_search',
	array(
		'messageModel' => $messageModel,
		'model' => $model,
	)
);?>

<div class="form">
	<?php $form = $this->beginWidget('CustomForm', array(
		'action' => Yii::app()->createUrl('/messages/backend/mailing/sendmessages'),
		'htmlOptions' => array('enctype'=>'multipart/form-data', 'class' => 'well form-disable-button-after-submit'),
		'enableAjaxValidation' =>false,
		'id' => 'message-form',
		'method' => 'POST'
	)); ?>

	<?php echo $form->errorSummary($messageModel); ?>

	<?php
		if (!$messageModel->message) {
			$messageModel->message = '<p>'.tt('Hello {username}', 'messages').'</p>';
		}
	?>
	
	<div class="rowold">
		<div class='flash-notice'><?php echo Yii::t('module_messages', 'max_newsletter_limit', array('{n}' => Mailing::MAILING_USERS_LIMIT));?></div>

		<?php
			$columns = array(
				array(
					'class'=>'CCheckBoxColumn',
					'id'=>'itemsSelected',
					'value' => '$data->id',
					'checked' => 'true',
					'selectableRows' => '2',
					'htmlOptions' => array(
						'class'=>'center',
					),
				),
				array(
					'name' => 'username',
				),
				array(
					'name' => 'type',
					'value' => '$data->getTypeName()',
					'filter' => User::getTypeList(),
				),
				'phone',
				'email',
			);

			$this->widget('CustomGridView', array(
				'id'=>'message-mailing-grid',
				'afterAjaxUpdate' => 'function(){attachStickyTableHeader();}',
				'dataProvider'=>$model->search(),
				'filter'=>$model,
				'afterAjaxUpdate' => 'function(){$("a[rel=\'tooltip\']").tooltip(); $("div.tooltip-arrow").remove(); $("div.tooltip-inner").remove();}',
				'columns'=>$columns
			));
		?>
		<div class="clear">&nbsp;</div>
	</div>
	<hr />

	<div class="rowold">
		<?php echo $form->labelEx($messageModel,'message'); ?>
		<?php //echo $form->textArea($messageModel, 'message', array('class' => 'textarea-message')); ?>
		<?php
		$this->widget('application.extensions.editMe.widgets.ExtEditMe', array(
			'model' => $messageModel,
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
		<?php echo $form->error($messageModel, 'message'); ?>
	</div>
	<div class="padding-bottom10">
		<span class="label label-info">
			<?php echo tt('message_macros_help', 'messages');?>
		</span>
	</div>
	<div class="clear">&nbsp;</div>

	<div class="rowold">
		<?php echo $form->labelEx($messageModel,'file'); ?>

		<div class="padding-bottom10">
			<span class="label label-info">
				<?php echo Yii::t('module_messages', 'Supported file: {supportExt}.', array('{supportExt}' => $messageModel->supportExt)).'';?>
				<br />
				<?php echo Yii::t('module_messages', 'Max file size: {fileMaxSize}.', array('{fileMaxSize}' => formatBytes($messageModel->fileMaxSize))).'';?>
			</span>
		</div>

		<?php
		$this->widget('CMultiFileUpload', array(
			'name' => 'files',
			'accept' => "{$messageModel->supportExtForUploader}",
			'duplicate' => ''.tt("The selected file has already been added!", "messages").'',
			'denied' => ''.tt("Unsupported file type!", "messages").'',
		));
		?>
		<?php echo $form->error($messageModel,'file'); ?>
	</div>
	<div class="clear">&nbsp;</div>

	<div class="rowold buttons">
		<?php $this->widget('bootstrap.widgets.TbButton',
			array('buttonType'=>'submit',
				'type'=>'primary',
				'icon'=>'ok white',
				'label'=> tt('Send', 'messages'),
				'htmlOptions' => array(
					'class' => 'submit-button',
				),
			)); ?>
	</div>
	<div class="clear">&nbsp;</div>
	<?php $this->endWidget(); ?>
</div>
