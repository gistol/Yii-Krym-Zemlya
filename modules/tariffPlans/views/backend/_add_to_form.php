<?php
$form = $this->beginWidget('CustomForm', array(
	'id'=>'addToUser-form',
	'htmlOptions'=>array(
		'class' => 'width500',
	),
));
echo CHtml::hiddenField('user_id', $id);
echo CHtml::hiddenField('withDate', $withDate);

echo $form->radioButtonList($model, 'tariff_id', $tariffsArray);

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
?>

<?php
echo '<div class="clear"></div>';

$this->widget('bootstrap.widgets.TbButton',
	array(
		'type' => 'primary',
		'icon' => 'ok white',
		'label' => tc('Apply'),
		'htmlOptions' => array(
			'onclick' => "addToUser.apply(); return false;",
		)
	));

$this->endWidget();