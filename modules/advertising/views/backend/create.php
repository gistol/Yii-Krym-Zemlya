<?php
$this->breadcrumbs=array(
	tt('Management of advertizing blocks') => array('admin'),
	tt('Add block'),
);

$this->menu=array(
	array('label' => tt('Management of advertizing blocks'), 'url'=>array('admin')),
);

$this->adminTitle = tt('Add block');

$this->renderPartial('_form',array(
		'model'=>$model,
	));
?>