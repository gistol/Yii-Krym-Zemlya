<div class="form">

<?php $form=$this->beginWidget('CustomForm', array(
	'id'=>$this->modelName.'-form',
	'enableAjaxValidation'=>false,
	'htmlOptions' => array('class' => 'well form-disable-button-after-submit'),
)); 
echo CHtml::hiddenField('addValues', 0, array('id' => 'addValues'));
?>

	<p class="note"><?php echo Yii::t('common', 'Fields with <span class="required">*</span> are required.'); ?></p>

	<?php echo $form->errorSummary($model); ?>

	<div class="rowold">
		<?php echo $form->labelEx($model,'country_id'); ?>
		<?php echo $form->dropDownList($model,'country_id', Country::getCountriesArray(0,1)); ?>
		<?php echo $form->error($model,'country_id'); ?>
	</div>

	<?php
	$this->widget('application.modules.lang.components.langFieldWidget', array(
		'model' => $model,
		'field' => 'name',
		'type' => 'string'
	));
	?>
	<div class="clear"></div>

	<div class="rowold buttons">
		<?php $this->widget('bootstrap.widgets.TbButton',
		array('buttonType'=>'submit',
			'type'=>'primary',
			'icon'=>'ok white',
			'label'=> tc('Save'),
			'htmlOptions' => array(
				'class' => 'submit-button',
			),
		)); ?>
		<?php $this->widget('bootstrap.widgets.TbButton',
			array('buttonType'=>'submit',
				'type'=>'primary',
				'icon'=>'ok white',
				'htmlOptions'=>array('name'=>'addValues', 'onclick' => '$("#addValues").val(1)', 'class' => 'submit-button'),
				'label'=> tt('Save and add cities'),
			)); ?>
	</div>

<?php $this->endWidget(); ?>

</div><!-- form -->