<?php
$this->breadcrumbs=array(
	tt("Manage currency") => array('admin'),
	tt("Update currency"),
);

$this->menu=array(
	array('label' => tt("Manage currency"), 'url'=>array('admin')),
	array('label'=>tt("Add currency"), 'url'=>array('create')),
	array('label'=>tt('Delete currency'), 'url'=>'#',
		'linkOptions'=>array(
			'submit'=>array('delete','id'=>$model->id),
			'confirm'=>tc('Are you sure you want to delete this item?')
		)
	),

);

$this->adminTitle = tt("Update currency");

?>

<?php echo $this->renderPartial('/backend/_form', array(
    'model'=>$model,
    'translate' => $translate,
)); ?>