<?php $form = $this->beginWidget('CustomForm',array(
	'action' => Yii::app()->createUrl($this->route),
	'method' => 'get',
	'id' => 'search-form'
)); ?>

<script type="text/javascript">
	function updateGrid() {
		$.fn.yiiGridView.update('message-mailing-grid', {
			data: $('#search-form').serialize()
		});
	}
	
	$(function () {
		$("#userType").on('change', function() {
			updateGrid();
		});

		$("#withListings").on('change', function() {
			updateGrid();
		});

		$("#countryListing").on('change', function() {
			updateGrid();
		});

		$("#regionListing").on('change', function() {
			updateGrid();
		});

		$("#cityListing").on('change', function() {
			updateGrid();
		});
	});
</script>

<?php
	echo CHtml::label($model->getAttributeLabel('userType'), 'userType');
	echo $form->dropDownList($model, 'type', CArray::merge(array(0 => tc('Please select')), User::getTypeList()), array('id' => 'userType'));
?>

<?php
	echo CHtml::label($model->getAttributeLabel('withListings'), 'withListings');
	echo $form->dropDownList($model, 'withListings', CArray::merge(array('' => tc('Please select')), array(0 => tc('No'), 1 => tc('Ok'))), array('id' => 'withListings'));
?>

<?php if(issetModule('location')){
	echo CHtml::label($model->getAttributeLabel('countryListing'), 'countryListing');
	echo CHtml::dropDownList(
		'Mailing[countryListing]',
		isset($this->selectedCountry)?$this->selectedCountry:'',
		Country::getCountriesArray(2, 0, true),
		array('class' => 'width285 searchField', 'id' => 'countryListing',
			'ajax' => array(
				'type'=>'GET', //request type
				'url'=>$this->createUrl('/location/main/getRegions'), //url to call.
				'data'=>'js:"country="+$("#countryListing").val()+"&type=2&onlyWithAds=1"',
				'success'=>'function(result){
					$("#regionListing").html(result);
					$("#regionListing").change();
				}'
			)
		)
	);

	echo CHtml::label($model->getAttributeLabel('regionListing'), 'regionListing');
	echo CHtml::dropDownList(
		'Mailing[regionListing]',
		isset($this->selectedRegion)?$this->selectedRegion:'',
		Region::getRegionsArray((isset($this->selectedCountry) ? $this->selectedCountry : 0), 2),
		array('class' => 'width285 searchField', 'id' => 'regionListing',
			'ajax' => array(
				'type'=>'GET', //request type
				'url'=>$this->createUrl('/location/main/getCities'), //url to call.
				'data'=>'js:"region="+$("#regionListing").val()+"&type=2&onlyWithAds=1"',
				'success'=>'function(result){
					$("#cityListing").html(result);
					$("#cityListing").change();
				}'
			)
		)
	);
}
?>

<?php
	echo CHtml::label($model->getAttributeLabel('cityListing'), 'cityListing');
	echo CHtml::dropDownList(
		'Mailing[cityListing]',
		isset($this->selectedCity)?$this->selectedCity:'',
		(issetModule('location')) ? (City::getCitiesArray((isset($this->selectedRegion) ? $this->selectedRegion : 0), 2)) : CArray::merge(array(0 => tc('select city')), ApartmentCity::getActiveCity()),
		array('class' => 'width285 searchField', 'id' => 'cityListing') //$fieldClass.
	);
?>

<?php $this->endWidget(); ?>