<?php
$this->breadcrumbs=array(
	tt('Manage subway stations', 'metroStations')=>array('admin'),
	tt('Edit station', 'metroStations'),
);

$this->menu=array(
    array('label'=>tt('Manage subway stations', 'metroStations'), 'url'=>array('admin')),
    array('label'=>tt('Add station', 'metroStations'), 'url'=>array('create')),
	array('label'=>tt('Add multiple stations', 'metroStations'), 'url'=>array('createMulty')),
);

$this->adminTitle = tt('Edit station', 'metroStations'). ': '.CHtml::encode($model->name);
?>

<?php echo $this->renderPartial('_form', array('model'=>$model)); ?>