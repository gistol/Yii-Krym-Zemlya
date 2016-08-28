<?php


$this->menu=array(
	array('label'=>tt('Manage cities'), 'url'=>array('/location/backend/city/admin')),
	array('label'=>tt('Add multiple cities'), 'url'=>array('/location/backend/city/createMulty')),
);
$this->adminTitle = tt('Add city');
?>

<?php echo $this->renderPartial('_form', array('model'=>$model)); ?>