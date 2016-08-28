<?php
$this->pageTitle=Yii::app()->name . ' - ' . tt('Edit value');

$this->menu = array(
    array('label' => tt('Manage SEO settings'), 'url' => array('admin')),
);

$this->adminTitle = tt('Edit value');
?>

<div class="form">

<?php $form=$this->beginWidget('CustomForm', array(
		'id'=>'Seo-form',
		'enableClientValidation'=>false,
		'htmlOptions' => array('class' => 'well form-disable-button-after-submit'),
	)); ?>
	<p class="note">
		<?php echo Yii::t('common', 'Fields with <span class="required">*</span> are required.'); ?>
	</p>

	<?php echo $form->errorSummary($model); ?>

    <?php
    $this->widget('application.modules.lang.components.langFieldWidget', array(
        'model' => $model,
        'field' => 'alt',
        'type' => 'string',
    ));
    ?>
    <br/>

    <div class="clear"></div>
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