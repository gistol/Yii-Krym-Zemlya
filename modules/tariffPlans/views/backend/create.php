<?php
$this->breadcrumbs=array(
	tt('Manage tariff plans')=>array('admin'),
	tt('Add tariff plan'),
);


$this->menu=array(
    array('label'=>tt('Manage tariff plans'), 'url'=>array('admin')),
	//array('label'=>tt('Add value'), 'url'=>array('create')),
);

$this->adminTitle = tt('Add tariff plan');
?>

<?php echo $this->renderPartial('_form', array('model'=>$model)); ?>