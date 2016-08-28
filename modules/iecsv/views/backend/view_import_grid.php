<?php
$this->pageTitle=Yii::app()->name . ' - ' . IecsvModule::t('Import / Export');

$this->breadcrumbs = array(
	tt('Import'),
);

$this->adminTitle = tt('Import');
?>

<div>
	<p><?php echo tt('Please select ads for import.'); ?></p>
</div>

<?php
$this->widget('CustomHistoryGridView', array(
	'id' => 'import-grid',
	'dataProvider' => $itemsProvider,
	'enablePagination' => true,
	'selectableRows' => 2,
	'selectionChanged'=>'js:selItemsSelected',
	'columns' => array(
		array(
			'class' => 'CCheckBoxColumn',
			'header' => tt('Select'),
			'id' => 'itemsSelectedImport',
		),
		array(
			'header' => tt('Type', 'apartments'),
			'name' => 'type',
			'type' => 'raw',
			'value' => 'HApartment::getNameByType($data["type"])',
			'htmlOptions' => array(
				'style' => 'width: 100px;',
			),
			'sortable' => false,
			'filter' => false,
		),
		array(
			'header' => tt('City', 'apartments'),
			'name' => 'city_id',
			'value' => '$data["cityName"] ? $data["cityName"] : ""',
			'htmlOptions' => array(
				'style' => 'width: 150px;',
			),
			'filter' => ApartmentCity::getAllCity(),
			'sortable' => false,
		),
		array(
			'header' => tc('Name'),
			'name' => 'title_'.Yii::app()->language,
			'type' => 'raw',
			'value' => 'CHtml::encode($data["title_".Yii::app()->language])',
			'sortable' => false,
			'filter' => false,
		),
	),
));
?>

<div class="form">
	<?php
	$form = $this->beginWidget('CustomForm', array(
			'id' => 'import-form',
			'method' => 'post',
			'action' => $this->createAbsoluteUrl('/iecsv/backend/main/importProcess'),
			'enableClientValidation' => false,
			'htmlOptions' => array(
				'class' => 'well form-disable-button-after-submit',
			),
		));
		echo $form->hiddenField($model,'itemsSelectedImport');
		echo CHtml::hiddenField('is_submit', 0, array('id' => 'is_submit'));
		echo $form->hiddenField($model,'selectedImportUser');
	?>

	<div class="clear">&nbsp;</div>
	<div class="rowold">
		<?php
		echo $form->labelEx($model, 'selectedImportUser');

		if (!isset($areasSelected) || !$areasSelected)
			$areasSelected = array();

		$columns = array(
			array(
				'class'=>'CCheckBoxColumn',
			),
			array(
				'name' => 'type',
				'value' => '$data->getTypeName()',
				'filter' => User::getTypeList(),
			),
			array(
				'name' => 'role',
				'value' => '$data->getRoleName()',
				'filter' => User::$roles,
			),
			array(
				'name' => 'username',
				'header' => tt('User name'),
			),
			'phone',
			'email',
		);

		$this->widget('CustomHistoryGridView', array(
			'id'=>'users-grid',
			'dataProvider' => $modelUsers->search(),
			'filter'=> $modelUsers,
			'columns'=>$columns,
			'selectableRows' => 1,
			'selectionChanged'=>'js:selUsersSelected',
		));

		echo $form->error($model, 'selectedImportUser');
		?>
	</div>

	<div class="clear"></div>
	<div class="rowold buttons">
		<?php $this->widget('bootstrap.widgets.TbButton',
			array('buttonType'=>'button',
				'type'=>'primary',
				'icon'=>'ok white',
				'label'=> tt('Import'),
				/*'htmlOptions' => array(
					'onclick' => 'setCheckedCookie(); $("#import-form").submit();',
				)*/
				'htmlOptions' => array(
					'onclick' => '$("#is_submit").val("1"); $("#import-form").submit();',
					'class' => 'submit-button',
				),
			));
		?>
	</div>
	<?php $this->endWidget(); ?>
</div><!-- form -->

<script>
	function selItemsSelected() {
		var arraySel = $("#import-grid").selGridView("getAllSelection");
		var stringSel = arraySel.join(',');
		$('#Iecsv_itemsSelectedImport').val(stringSel);
	}

	function selUsersSelected() {
		var arrayUsersSel = $("#users-grid").selGridView("getAllSelection");
		var stringUsersSel = arrayUsersSel.join(',');
		$('#Iecsv_selectedImportUser').val(stringUsersSel);
	}
</script>