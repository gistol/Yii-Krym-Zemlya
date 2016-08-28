<div class="form">

<?php $form=$this->beginWidget('CustomForm', array(
	'id'=>$this->modelName.'-form',
	'enableAjaxValidation'=>false,
	'htmlOptions' => array('class' => 'well form-disable-button-after-submit'),
)); ?>

	<p class="note"><?php echo Yii::t('common', 'Fields with <span class="required">*</span> are required.'); ?></p>
	<?php echo $form->errorSummary($model); ?>

    <?php
    $this->widget('application.modules.lang.components.langFieldWidget', array(
		'model' => $model,
		'field' => 'name',
		'type' => 'string'
	));
    ?>
	<div class="clear"></div>

	<?php
	$this->widget('application.modules.lang.components.langFieldWidget', array(
		'model' => $model,
		'field' => 'description',
		'type' => 'text-editor'
	));
	?>
	<div class="clear"></div><br />


	<fieldset>
		<legend><?php echo tt('Browse ads', 'tariffPlans'); ?></legend>

		<div class="rowold">
			<?php echo $form->checkboxRow($model, 'show_address'); ?>
		</div>

		<div class="rowold">
			<?php echo $form->checkboxRow($model, 'show_phones'); ?>
		</div>

	</fieldset>

	<div class="clear"></div><br />

	<fieldset>
		<legend><?php echo tt('Adding ads', 'tariffPlans'); ?></legend>

		<div class="rowold">
			<?php echo $form->labelEx($model,'limit_objects'); ?>
			<?php echo $form->textField($model,'limit_objects', array('class' => 'width50')); ?>
			<span class="label label-info">
				<?php echo tt('If null or 0 then unlimited');?>
			</span>
			<?php echo $form->error($model,'limit_objects'); ?>
		</div>
		<div class="clear"></div>

		<div class="rowold">
			<?php echo $form->labelEx($model,'limit_photos'); ?>
			<?php echo $form->textField($model,'limit_photos', array('class' => 'width50')); ?>
			<span class="label label-info">
				<?php echo tt('If null or 0 then unlimited');?>
			</span>
			<?php echo $form->error($model,'limit_photos'); ?>
		</div>
		<div class="clear"></div><br />
	</fieldset>

	<?php if ($model->id != TariffPlans::DEFAULT_TARIFF_PLAN_ID):?>
		<fieldset>
			<legend><?php echo tt('Duration and price', 'tariffPlans'); ?></legend>

			<div class="rowold">
				<?php echo $form->labelEx($model,'duration'); ?>
				<?php echo $form->textField($model,'duration', array('class' => 'width50')); ?>
				<span><?php echo tt('days'); ?></span>
				<?php echo $form->error($model,'duration'); ?>
			</div>
			<div class="clear"></div>

			<div class="rowold">
				<?php echo $form->labelEx($model,'price'); ?>
				<?php echo $form->textField($model,'price', array('class' => 'width50')); ?>
				<span><?php echo Currency::getDefaultCurrencyModel()->name; ?></span>
				<?php echo $form->error($model,'price'); ?>
			</div>
			<div class="clear"></div>
		</fieldset>
	<?php endif;?>

	<div class="rowold buttons">
		<?php $this->widget('bootstrap.widgets.TbButton',
			array('buttonType'=>'submit',
				'type'=>'primary',
				'icon'=>'ok white',
				'label'=> $model->isNewRecord ? tc('Add') : tc('Save'),
				'htmlOptions' => array(
					'class' => 'submit-button',
				),
			)); ?>
	</div>

<?php $this->endWidget(); ?>

</div><!-- form -->