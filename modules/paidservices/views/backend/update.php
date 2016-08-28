<?php
$this->breadcrumbs=array(
    tt('Manage paid services')=>array('admin'),
);

$this->menu=array(
	array('label' => tt('Manage paid services'), 'url'=>array('/paidservices/backend/main/admin')),
);

$this->adminTitle = tt("Update paid service");

?>

<?php echo $this->renderPartial('/backend/_form', array('model'=>$model, 'dataModel' => $dataModel)); ?>