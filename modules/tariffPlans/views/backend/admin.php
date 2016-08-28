<?php

$this->pageTitle=Yii::app()->name . ' - ' . tt('Manage tariff plans', 'tariffPlans');

$this->menu=array(
	array('label'=>tt('Add tariff plan'), 'url'=>array('create')),
);

$this->adminTitle = tt('Manage tariff plans', 'tariffPlans');

$this->widget('CustomGridView', array(
	'id'=>'tariff-plans-grid',
	'dataProvider'=>$model->search(),
	'filter'=>$model,
	'afterAjaxUpdate' => 'function(){$("a[rel=\'tooltip\']").tooltip(); $("div.tooltip-arrow").remove(); $("div.tooltip-inner").remove(); attachStickyTableHeader();}',
	'columns'=>array(
		array(
			'name' => 'active',
			'type' => 'raw',
			'value' => 'Yii::app()->controller->returnStatusHtml($data, "tariff-plans-grid", 1, TariffPlans::DEFAULT_TARIFF_PLAN_ID)',
			'htmlOptions' => array('class'=>'infopages_status_column'),
			'filter' => false,
			'sortable' => false,
			//'visible' => '$data->id != TariffPlans::DEFAULT_TARIFF_PLAN_ID',
		),
		'name',
		array(
			'name' => 'show_address',
			'value' => '($data->show_address) ? tc("Yes") : tc("No")',
			'sortable' => false,
			'filter' => array(0 => tc('No'), 1 => tc('Yes')),
		),
		array(
			'name' => 'show_phones',
			'value' => '($data->show_phones) ? tc("Yes") : tc("No")',
			'sortable' => false,
			'filter' => array(0 => tc('No'), 1 => tc('Yes')),
		),
		array(
			'name' => 'limit_objects',
			'value' => '$data->getLimitObjectForGrid()',
			'sortable' => false,
			//'filter' => true,
		),
		array(
			'name' => 'limit_photos',
			'value' => '$data->getLimitPhotosForGrid()',
			'sortable' => false,
			//'filter' => true,
		),
		array(
			'header' => tt('Price', 'tariffPlans').', ('.Currency::getDefaultCurrencyModel()->name.')',
			'name' => 'price',
			'value' => '$data->getPriceForGrid()',
			'sortable' => false,
			//'filter' => true,
		),
		array(
			'header' => tt('Duration', 'tariffPlans').', ('.tt('days').')',
			'name' => 'duration',
			'value' => '$data->getDurationForGrid()',
			'sortable' => false,
			//'filter' => true,
		),
		array(
			'class'=>'bootstrap.widgets.TbButtonColumn',
			'deleteConfirmation' => tt('Are you sure you want to delete this item? All user tariffs will also be deleted!'),
			'template'=>'{update} {delete}',
			'buttons' => array(
				'delete' => array(
					'visible' => '$data->id != TariffPlans::DEFAULT_TARIFF_PLAN_ID',
				),
			),
		),
	),
)); ?>
