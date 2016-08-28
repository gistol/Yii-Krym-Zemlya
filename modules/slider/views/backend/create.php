<?php
$this->adminTitle = tt('Add image');

$this->menu = array(
	array('label' => SliderModule::t('Manage slider'), 'url' => array('admin')),
);

echo $this->renderPartial('/backend/_form', array('model'=>$model, 'isCreate'=>true));
?>