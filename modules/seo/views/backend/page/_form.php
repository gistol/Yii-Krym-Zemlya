<div class="form">
    <?php
        $form=$this->beginWidget('CustomForm', array(
            'id'=>$this->modelName.'-form',
            'enableAjaxValidation'=>false,
			'htmlOptions' => array('class' => 'well form-disable-button-after-submit'),
        ));
    ?>

	<p class="note"><?php echo Yii::t('common', 'Fields with <span class="required">*</span> are required.'); ?></p>

	<?php echo $form->errorSummary($model); ?>

	<div class="rowold">
        <?php echo $form->labelEx($model,'url'); ?>
        <?php echo $form->textField($model,'url',array('class'=>'width300','maxlength'=>255)); ?>
        <?php echo $form->error($model,'url'); ?>
	</div>

    <?php
        $this->widget('application.modules.lang.components.langFieldWidget', array(
            'model' => $model,
            'field' => 'title',
            'type' => 'string',
        ));
    ?>
    <br/>

    <?php
        $this->widget('application.modules.lang.components.langFieldWidget', array(
            'model' => $model,
            'field' => 'description',
            'type' => 'string'
        ));
    ?>

    <div class="clear"></div>
    <br>

    <?php
        $this->widget('application.modules.lang.components.langFieldWidget', array(
            'model' => $model,
            'field' => 'keywords',
            'type' => 'string',
        ));
    ?>
    <br/>

    <div class="rowold buttons">
        <?php $this->widget('bootstrap.widgets.TbButton',
        array('buttonType'=>'submit',
            'type'=>'primary',
            'icon'=>'ok white',
            'label'=> $model->isNewRecord ? tc('Create') : tc('Save'),
			'htmlOptions' => array(
				'class' => 'submit-button',
			),
        )); ?>
    </div>
    <?php $this->endWidget(); ?>
</div><!-- form -->