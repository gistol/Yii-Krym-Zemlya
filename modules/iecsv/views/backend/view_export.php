<?php
$this->pageTitle=Yii::app()->name . ' - ' . IecsvModule::t('Import / Export');

$this->breadcrumbs = array(
	tt('Export'),
);

$this->menu = array(
    array('label' => tc('Import / Export'), 'url' => array('admin')),
	array('label' => tt('Import'), 'url' => array('viewImport')),
);

$this->adminTitle = tt('Export');
?>

<div>
	<p><?php echo tt('The file is exported to the UTP-8 without BOM charset.'); ?></p>
	<p><?php echo tt('Separators are ";".'); ?></p>
</div>

<?php
$columns = array(
	array(
		'class' => 'CCheckBoxColumn',
		'header' => tt('Select'),
		'id' => 'itemsSelectedExport',
	),
	array(
		'name' => 'id',
		'htmlOptions' => array(
			'class' => 'apartments_id_column',
			'style' => 'text-align: center;',
		),
		'sortable' => false,
	),
	array(
		'name' => 'type',
		'type' => 'raw',
		'value' => 'HApartment::getNameByType($data->type)',
		'htmlOptions' => array(
			'style' => 'width: 100px;',
		),
		'filter' => HApartment::getTypesArray(),//CHtml::dropDownList('Apartment[type_filter]', $currentType, HApartment::getTypesArray(true)),
		'sortable' => false,
	),
);

if (issetModule('location')) {
	$columns[]=array(
		'name' => 'loc_country',
		'value' => '$data->loc_country ? $data->locCountry->name : ""',
		'htmlOptions' => array(
			'style' => 'width: 150px;',
		),
		'sortable' => false,
		'filter' => Country::getCountriesArray(0, 1),
	);
	$columns[]=array(
		'name' => 'loc_region',
		'value' => '$data->loc_region ? $data->locRegion->name : ""',
		'htmlOptions' => array(
			'style' => 'width: 150px;',
		),
		'sortable' => false,
		'filter' => Region::getRegionsArray($model->loc_country, 0, 1),
	);
	$columns[]=array(
		'name' => 'loc_city',
		'value' => '$data->loc_city ? $data->locCity->name : ""',
		'htmlOptions' => array(
			'style' => 'width: 150px;',
		),
		'sortable' => false,
		'filter' => City::getCitiesArray($model->loc_region, 0, 1),
	);
} else {
	$columns[]=array(
		'name' => 'city_id',
		'value' => '(isset($data->city ) && $data->city_id) ? $data->city->name : ""',
		'htmlOptions' => array(
			'style' => 'width: 150px;',
		),
		'filter' => ApartmentCity::getAllCity(),
		'sortable' => false,
	);
}

$columns[]=array(
	'name' => 'ownerEmail',
	'htmlOptions' => array(
		'style' => 'width: 150px;',
	),
	'type' => 'raw',
	'value' => '(isset($data->user) && $data->user->role != "admin") ? CHtml::link(CHtml::encode($data->user->email), array("/users/backend/main/view","id" => $data->user->id)) : tt("administrator", "common")',
);

$columns[] = array(
		'header' => tc('Name'),
		'name' => 'title_'.Yii::app()->language,
		'type' => 'raw',
		'value' => 'CHtml::link(CHtml::encode($data->{"title_".Yii::app()->language}), $data->getUrl())',
		'sortable' => false,
);
?>


<div class="form">
	<?php
		$this->widget('CustomHistoryGridView', array(
		'id' => 'export-grid',
		'dataProvider' => $model->searchExport(),
		'filter' => $model,
		'selectableRows' => 2,
		'selectionChanged'=>'js:selItemsSelected',
		'columns' => $columns,
	));
?>

	<?php
		$form = $this->beginWidget('CActiveForm', array(
			'id' => 'export-form',
			'method' => 'post',
			'action' => $this->createAbsoluteUrl('/iecsv/backend/main/export'),
			'enableClientValidation' => false,
			'htmlOptions' => array('class' => 'well form-disable-button-after-submit'),
		));

		echo $form->hiddenField($model,'itemsSelectedExport');
		echo $form->error($model,'itemsSelectedExport');
	?>

	<div class="rowold isZip">
		<?php echo $form->checkBox($model, 'isZip', array('style' => 'margin: 0;')); ?>
		<?php echo $form->label($model, 'isZip', array('class' => 'noblock')); ?>
		<?php echo $form->error($model, 'isZip'); ?>
	</div>

	<div class="rowold buttons">
		<?php $this->widget('bootstrap.widgets.TbButton',
			array('buttonType'=>'submit',
				'type'=>'primary',
				'icon'=>'ok white',
				'label'=> tt('Export'),
				'htmlOptions' => array(
					'class' => 'submit-button',
				),
				/*'htmlOptions' => array(
					'onclick' => 'setCheckedCookie(); $("#export-form").submit();',
				)*/
			));
		?>
	</div>

	<?php $this->endWidget(); ?>
</div><!-- form -->

<script>
	function selItemsSelected() {
		var arraySel = $("#export-grid").selGridView("getAllSelection");
		var stringSel = arraySel.join(',');
		$('#Iecsv_itemsSelectedExport').val(stringSel);
	}
</script>