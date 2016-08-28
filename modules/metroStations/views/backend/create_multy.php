<?php
$this->breadcrumbs=array(
	tt('Manage subway stations', 'metroStations')=>array('admin'),
	tt('Add multiple stations', 'metroStations'),
);

$this->menu=array(
	array('label'=>tt('Manage subway stations', 'metroStations'), 'url'=>array('admin')),
	array('label'=>tt('Add station', 'metroStations'), 'url'=>array('create')),
);
$this->adminTitle = tt('Add multiple stations', 'metroStations');
?>
<div class="flash-notice"><?php echo tt('Add multiple stations help', 'metroStations');?></div>
<div class="form">

<?php $form=$this->beginWidget('CustomForm', array(
	'id'=>$this->modelName.'-form',
	'enableAjaxValidation'=>false,
	'htmlOptions' => array('class' => 'well form-disable-button-after-submit'),
)); ?>

	<p class="note"><?php echo Yii::t('common', 'Fields with <span class="required">*</span> are required.'); ?></p>
	<?php echo $form->errorSummary($model); ?>

	<?php if(issetModule('location')): ?>
		<div class="rowold">
			<?php echo CHtml::label(tc('Country'), 'countryId', array('required' => true));?>
			<?php
				echo CHtml::dropDownList(
					'MetroStations[country]',
					$model->country,
					Country::getCountriesArray(2, 0),
					array('class' => 'span3', 'id' => 'countryId',
						'ajax' => array(
							'type'=>'GET', //request type
							'url'=>$this->createUrl('/location/main/getRegions'), //url to call.
							'data'=>'js:"country="+$("#countryId").val()+"&type=2"',
							'success'=>'function(result){
								$("#regionId").html(result);
								$("#regionId").change();
							}'
						)
					)
				);
			?>
		</div>
		<div class="rowold">
			<?php echo CHtml::label(tc('Region'), 'regionId', array('required' => true));?>
			<?php
				echo CHtml::dropDownList(
					'MetroStations[region]',
					$model->region,
					Region::getRegionsArray($model->country, 2),
					array('class' => 'span3', 'id' => 'regionId',
						'ajax' => array(
							'type'=>'GET', //request type
							'url'=>$this->createUrl('/location/main/getCities'), //url to call.
							'data'=>'js:"region="+$("#regionId").val()+"&type=0"',
							'success'=>'function(result){
								$("#cityId").html(result);
							}'
						)
					)
				);
			?>
		</div>
		<div class="rowold">
			<?php echo CHtml::label(tc('City'), 'cityId', array('required' => true));?>
			<?php		
				echo CHtml::dropDownList(
					'MetroStations[loc_city]',
					$model->loc_city,
					CArray::merge(array(0 => tc('select city')), City::getCitiesArray($model->region, 0)),
					array('class' =>'span3', 'id' => 'cityId')
				);
			?>
		</div>
	<?php else:?>
		<div class="rowold">
			<?php echo CHtml::label(tc('City'), 'cityId', array('required' => true));?>
			<?php		
				echo CHtml::dropDownList(
					'MetroStations[city_id]',
					$model->city_id,
					ApartmentCity::getAllCity(),
					array('class' =>'span3', 'id' => 'cityId')
				);
			?>
		</div>
	<?php endif;?>
	<div class="clear"></div><br />
		
	<div class="rowold">
		<?php echo $form->labelEx($model, 'multy'); ?>
		<?php echo $form->textArea($model, 'multy',array('class'=>'span6','rows'=>7)); ?>
		<?php echo $form->error($model, 'multy'); ?>
	</div>
	<div class="clear"></div><br />

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