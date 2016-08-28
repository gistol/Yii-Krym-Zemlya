<?php


$this->menu=array(
	array('label'=>tt('Manage regions'), 'url'=>array('/location/backend/region/admin')),
);
$this->adminTitle = tt('Add region');
?>

<?php echo $this->renderPartial('_form', array('model'=>$model)); ?>