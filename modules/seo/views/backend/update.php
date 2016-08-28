<?php
$this->pageTitle=Yii::app()->name . ' - ' . tt('Edit value');

$this->menu = array(
    array('label' => tt('Manage SEO settings'), 'url' => array('admin')),
);

$this->adminTitle = tt('Edit value');
?>

<?php echo $this->renderPartial('/backend/_form', array('model'=>$model)); ?>