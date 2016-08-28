<?php

$this->adminTitle = Yii::t('module_payment','Payment System Settings');

$model->payModel->printInfo();

?>
<div class="form">

<?php echo CHtml::beginForm('', 'post', array('class'=>'well form-disable-button-after-submit')); ?>

	<p class="note"><?php echo Yii::t('common', 'Fields with <span class="required">*</span> are required.'); ?></p>

	<?php echo CHtml::errorSummary(array($model, $model->payModel), '', '', array('class'=>'alert alert-block alert-error')); ?>

    <?php
    $this->widget('application.modules.lang.components.langFieldWidget', array(
    		'model' => $model,
    		'field' => 'name',
            'type' => 'string',
    	));
    ?>

    <?php
    $this->widget('application.modules.lang.components.langFieldWidget', array(
    		'model' => $model,
    		'field' => 'description',
            'type' => 'text-editor',
    	));
    ?>

	<?php
		$this->renderPartial('paymentsystems/'.$model->viewName, array('model' => $model->payModel));
	?>

	<div class="rowold">
		<?php echo CHtml::activeLabelEx($model,'active'); ?>
		<?php echo CHtml::activeDropDownList($model,'active',$this->getStatusOptions()); ?>
		<?php echo CHtml::error($model,'active'); ?>
	</div>

	<div class="rowold buttons">
        <?php $this->widget('bootstrap.widgets.TbButton',
			array('buttonType'=>'submit',
				'type'=>'primary',
				'icon'=>'ok white',
				'label'=> tc('Save'),
				'htmlOptions' => array(
					'class' => 'submit-button',
				),
			)); 
		?>
	</div>

<?php echo CHtml::endForm(); ?>

</div><!-- form -->