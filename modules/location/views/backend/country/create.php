<?php

$this->menu=array(
	array('label'=>tt('Manage countries'), 'url'=>array('/location/backend/country/admin')),
);

$this->adminTitle = tt('Add country');
?>

<?php echo $this->renderPartial('_form', array('model'=>$model)); ?>