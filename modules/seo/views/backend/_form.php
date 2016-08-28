<?php
$canUseDirectUrl = $model->model_name == 'InfoPages' ? 1 : 0;
$showBodyTextField = ($model->model_name == 'City' || $model->model_name == 'ApartmentCity' || $model->model_name == 'ApartmentObjType') ? 1 : 0;
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

    <?php if($canUseDirectUrl){ ?>
        <div class="rowold no-mrg">
            <?php
            echo CHtml::activeCheckBox($model, 'direct_url');
            echo '&nbsp;'.CHtml::activeLabelEx($model, 'direct_url', array('class' => 'noblock'));;
            ?>
        </div>
    <?php } ?>

    <?php
    echo CHtml::hiddenField('canUseDirectUrl', $canUseDirectUrl ? 1 : 0);

    $this->widget('application.modules.lang.components.langFieldWidget', array(
        'model' => $model,
        'field' => 'url',
        'type' => 'string',
        'note' => $model->prefixUrl,
    ));
    ?>
    <br/>

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

    <div class="clear"></div><br />

    <?php
    $this->widget('application.modules.lang.components.langFieldWidget', array(
        'model' => $model,
        'field' => 'keywords',
        'type' => 'string',
    ));
    ?>
    <br/>
	
	<?php if ($showBodyTextField):?>
		<div class="seo-body_text-block">
			<?php
			$this->widget('application.modules.lang.components.langFieldWidget', array(
				'model' => $model,
				'field' => 'body_text',
				'type' => 'text-editor',
			));
			?>
			<?php echo CHtml::hiddenField('showBodyTextField', 1); ?>
		</div>
		<br/>
	<?php endif;?>

    <div class="clear"></div>
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

