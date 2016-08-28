<?php
$this->pageTitle=Yii::app()->name . ' - ' . tt('Manage payments', 'payment');

$this->menu = array(
	array(),
);

$this->adminTitle = tt('Manage payments', 'payment');

$columns = array(
	array(
		'name' => 'id',
		'htmlOptions' => array(
			'class'=>'id_column',
		),
	),
	array(
		'name' => 'status',
		'type' => 'raw',
		'value' => '$data->returnStatusHtml()',
		'htmlOptions' => array(
			'class'=>'width150',
		),
		'filter' => CHtml::dropDownList('Payments[status]', $model->status, $model->getStatuses()),
	),
	array(
		'header' => tc('User'),
		'type' => 'raw',
		'value' => 'isset($data->user) ? CHtml::link(CHtml::encode($data->user->username),array("/users/backend/main/view","id" => $data->user->id)) : ""',
	),
	array(
		'name' => 'apartment_id',
		'type' => 'raw',
		'value' => '(isset($data->ad) && $data->ad->id) ? CHtml::link($data->ad->id, $data->ad->getUrl()) : tc("No")',
		'filter' => false,
		'sortable' => true
	)
);

if (issetModule('tariffPlans')) {
	$columns[] = array(
		'header' => tt('Tariff_id', 'tariffPlans'),
		'name' => 'tariff_id',
		'type' => 'raw',
		'value' => '(isset($data->tariffInfo) && $data->tariffInfo->name) ? $data->tariffInfo->name : tc("No")',
		'filter' => false,
		'sortable' => false
	);
}

$columns[] = array(
	'header' => tc('Paid Service'),
	'type' => 'raw',
	'value' => '$data->getPaidserviceName()',
);

$columns[] = array(
	'name' => 'paysystem_name',
	'type' => 'raw',
	'value' => '(isset($data->paysystem) && $data->paysystem) ? $data->paysystem->name : ""',
);

$columns[] = array(
	'name'=>'amount',
	'type'=>'raw',
	'value'=>'$data->amount . " " . $data->currency_charcode',
	'htmlOptions' => array('style' => 'width:70px;'),
);

$columns[] = array(
	'name'=>'date_created',
	'type'=>'raw',
	'filter'=>false,
	'htmlOptions' => array('style' => 'width:130px;'),
);

$columns[] = array(
	'class'=>'bootstrap.widgets.TbButtonColumn',
	'template' => '{confirm} {delete}',
	'htmlOptions' => array(
		'class'=>'width100',
	),
	'buttons' => array(
		'delete' => array(
			'visible' => '$data->status == Payments::STATUS_WAITPAYMENT || $data->status == Payments::STATUS_WAITOFFLINE',
		),
		'confirm' => array(
			'visible' => '$data->status == Payments::STATUS_WAITOFFLINE',
			'imageUrl' => Yii::app()->theme->baseUrl.'/images/active.png',
			'url'=>'Yii::app()->createUrl("/payment/backend/main/confirm", array("id"=>$data->id))',
			'label' => tt('Confirm payment')
		),
	)
);

$this->widget('CustomGridView', array(
		'dataProvider'=>$model->search(),
		'filter'=>$model,
		'afterAjaxUpdate' => 'function(){$("a[rel=\'tooltip\']").tooltip(); $("div.tooltip-arrow").remove(); $("div.tooltip-inner").remove(); attachStickyTableHeader();}',
		'columns' => $columns
	)
); ?>
