<div class="form">

<?php $form=$this->beginWidget('CustomForm', array(
	'id'=>$this->modelName.'-form',
	'enableAjaxValidation'=>false,
	'htmlOptions' => array('class' => 'well form-disable-button-after-submit'),
)); ?>

	<p class="note"><?php echo Yii::t('common', 'Fields with <span class="required">*</span> are required.');?></p>

	<?php
	if($dataModel){
		echo $form->errorSummary(array($model, $dataModel));
	}else{
		echo $form->errorSummary($model);
	}
	?>

	<?php echo $form->labelEx($model,'active'); ?>
	<?php echo $form->dropDownList($model, 'active', array(
	'1' => tt('Active', 'apartments'),
	'0' => tt('Inactive', 'apartments'),
), array('class' => 'width150')); ?>
	<?php echo $form->error($model,'active'); ?>

    <?php
    $this->widget('application.modules.lang.components.langFieldWidget', array(
    		'model' => $model,
    		'field' => 'name',
            'type' => 'string',
    	));
    ?>

   

    <div class="clear"></div>

	<?php
	if($model->dataModel){
		require '_form_'.$model->dataModel.'.php';
	}
	?>

    <br>

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