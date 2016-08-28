<?php

$this->menu=array(
	array('label'=>tt('Manage countries'), 'url'=>array('/location/backend/country/admin')),
	array('label'=>tt('Add country'), 'url'=>array('/location/backend/country/create')),

);
$this->adminTitle = tt('Edit country').': <i>'.$model->getStrByLang('name').'</i>';
?>

<?php echo $this->renderPartial('_form', array('model'=>$model)); ?>