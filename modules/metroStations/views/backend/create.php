<?php
$this->breadcrumbs=array(
	tt('Manage subway stations', 'metroStations')=>array('admin'),
	tt('Add station', 'metroStations'),
);


$this->menu=array(
    array('label'=>tt('Manage subway stations', 'metroStations'), 'url'=>array('admin')),
);

$this->adminTitle = tt('Add station', 'metroStations');
?>

<?php echo $this->renderPartial('_form', array('model'=>$model)); ?>