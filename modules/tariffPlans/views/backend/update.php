<?php
$this->breadcrumbs=array(
	tt('Manage tariff plans')=>array('admin'),
	tt('Edit tariff plan'),
);

$this->menu=array(
    array('label'=>tt('Manage tariff plans'), 'url'=>array('admin')),
    array('label'=>tt('Add tariff plan'), 'url'=>array('/tariffPlans/backend/main/create')),
);

$this->adminTitle = tt('Edit tariff plan'). ': '.CHtml::encode($model->name);
?>

<?php echo $this->renderPartial('_form', array('model'=>$model)); ?>