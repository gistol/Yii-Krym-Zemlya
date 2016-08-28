<div class="form">

	<?php $form=$this->beginWidget('CustomForm', array(
		'id'=>'Slider-form',
		'enableClientValidation'=>false,
		'htmlOptions' => array('enctype'=>'multipart/form-data', 'class' => 'well form-disable-button-after-submit'),
	)); ?>

	<?php
		$this->widget('application.modules.lang.components.langFieldWidget', array(
			'model' => $model,
			'field' => 'title',
			'type' => 'text'
		));
	?>

	<div class="rowold">
		<?php echo $form->labelEx($model,'url'); ?>
		<?php echo $form->textField($model,'url',array('style' => 'width: 400px;')); ?>
		<?php echo $form->error($model,'url'); ?>
	</div>

	<?php if ($isCreate): ?>
		<div class="rowold">
			<?php echo $form->checkBox($model,'use_effect'); ?>
			<?php echo $form->labelEx($model,'use_effect', array('class' => 'noblock')); ?>
			<?php echo '<div class="padding-bottom10"><span class="label label-info">'.tc('Note:').'</span> '.tt('This option is only available if PHP is compiled with bundled support for GD library, becouse use imagefilter php function.').'</div>';?>

			<?php echo $form->error($model,'use_effect'); ?>
		</div>


		<div class="rowold">
			<?php echo $form->labelEx($model,'img'); ?>
			<div class="padding-bottom10">
				<span class="label label-info">
					<?php echo Yii::t('module_slider', 'Supported file: {supportExt}.', array('{supportExt}' => $model->supportExt)).'';?>
				</span>
			</div>
			<?php echo $form->fileField($model, 'img'); ?>
			<?php echo $form->error($model,'img'); ?>
		</div>
	<?php endif; ?>

    <div class="rowold buttons">
           <?php $this->widget('bootstrap.widgets.TbButton',
				array('buttonType'=>'submit',
					'type'=>'primary',
					'icon'=>'ok white',
					'label'=> $model->isNewRecord ? tc('Add') : tc('Save'),
					'htmlOptions' => array(
						'class' => 'submit-button',
					),
				)); 
		   ?>
   	</div>
	<?php $this->endWidget(); ?>

</div><!-- form -->



