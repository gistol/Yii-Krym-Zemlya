<?php
/**
 * @var $model SeoSettings
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
echo $form->errorSummary($model->models);

foreach($model->models as $key => $tm){
    //deb($tm->message);
    $this->widget('application.modules.lang.components.langFieldWidget', array(
        'model' => $tm,
        'field' => 'translation',
        'type' => 'text',
        'fieldPrefix' => '['.$key.']',
        'labelSet' => $model->getLabel($tm->message),
    ));
}

echo '<hr>';

echo $form->checkBoxRow($settingsForm, 'genFirendlyUrl');
echo $form->checkBoxRow($settingsForm, 'urlExtension');
echo $form->checkBoxRow($settingsForm, 'allowUserSeo');
echo $form->checkBoxRow($settingsForm, 'useSchemaOrgMarkup');

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
</div>

<?php $this->endWidget(); ?>

</div><!-- form -->