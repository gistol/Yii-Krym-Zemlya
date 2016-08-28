<?php
$this->breadcrumbs=array(
    tt('Management of advertizing blocks') => array('admin'),
    tt('Edit block'),
);

$this->menu=array(
	array('label' => tt('Management of advertizing blocks'), 'url'=>array('admin')),
);

$this->adminTitle = tt('Edit block');

echo $this->renderPartial('_form', array('model'=>$model));

?>