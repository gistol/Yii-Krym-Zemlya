<?php

$form = $this->beginWidget('CustomForm', array(
	'id'=>'addToAd-form',
	'htmlOptions'=>array(
		'class' => 'width500',
	),
));
echo CHtml::hiddenField('ad_id', $id);
echo CHtml::hiddenField('withDate', $withDate);

echo $form->radioButtonList($model, 'paid_id', $paidServicesArray);

echo $form->errorSummary($model);

echo CHtml::label(tc('is valid till'), '');

$this->widget('zii.widgets.jui.CJuiDatePicker', array(
	'model' => $model,
	'attribute' => 'date_end',
	'language' => Yii::app()->controller->datePickerLang,
	'options' => array(
		'showAnim' => 'fold',
		'dateFormat' => 'yy-mm-dd',
		'minDate' => 'new Date()',
	),
	'htmlOptions' => array(
		'class' => 'width100 eval_period'
	),
));

echo '<div class="clear"></div>';

$this->widget('bootstrap.widgets.TbButton',
	array(
		'type' => 'primary',
		'icon' => 'ok white',
		'label' => tc('Apply'),
		'htmlOptions' => array(
			'onclick' => "addTo.apply(); return false;",
		)
	));

 $this->endWidget();