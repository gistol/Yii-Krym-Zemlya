<?php
$this->pageTitle = Yii::app()->name . ' - ' . tt('Manage paid services');

$this->menu = array(
	array('label'=>tt('Add a paid service'), 'url'=>'create')
);

$this->adminTitle = tt('Manage paid services');
?>

<?php $this->widget('zii.widgets.CListView', array(
	'dataProvider' => $dataProvider,
	'itemView'=>'_list_item',
));
?>

