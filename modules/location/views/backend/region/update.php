<?php


$this->menu=array(
	array('label'=>tt('Manage regions'), 'url'=>array('/location/backend/region/admin')),
	array('label'=>tt('Add region'), 'url'=>array('/location/backend/region/create')),
);

$this->adminTitle = tt('Edit region').': <i>'.$model->getStrByLang('name').'</i>';
?>

<?php echo $this->renderPartial('_form', array('model'=>$model)); ?>