<?php

$this->breadcrumbs=array(
    tt("Manage currency") => array('admin'),
);

$this->menu=array(
    array('label' => tt("Manage currency"), 'url'=>array('admin')),
);

$this->adminTitle = tt("Add currency");

?>

<?php echo $this->renderPartial('/backend/_form', array(
    'model'=>$model,
    'translate' => $translate,
)); ?>