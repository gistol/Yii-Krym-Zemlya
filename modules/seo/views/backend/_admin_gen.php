<?php
/**
 * @var $model SeoGenerateForm
 * @var $form CustomForm
 */
?>
<div class="form">

    <?php $form=$this->beginWidget('CustomForm', array(
        'id'=>'seo-form',
        'enableAjaxValidation'=>true,
		'htmlOptions' => array('class' => 'well form-disable-button-after-submit'),
    )); ?>

    <p class="note"><?php echo Yii::t('common', 'Fields with <span class="required">*</span> are required.'); ?></p>

    <?php
    echo $form->errorSummary($model);

    echo $form->checkBoxRow($model, 'regenOld');
    echo '<hr>';
    echo $form->checkBoxListRow($model, 'forModels', SeoFriendlyUrl::getModelNameList());

    ?>
    <div class="clear"></div>

    <div class="rowold buttons">
        <?php $this->widget('bootstrap.widgets.TbButton',
            array('buttonType'=>'submit',
                'type'=>'primary',
                'icon'=>'ok white',
                'label'=> tt('Generate'),
				'htmlOptions' => array(
					'class' => 'submit-button',
				),
            )); ?>
    </div>

    <?php $this->endWidget(); ?>

</div><!-- form -->