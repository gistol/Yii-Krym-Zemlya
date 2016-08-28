<?php
$this->pageTitle=Yii::app()->name . ' - ' . SliderModule::t('Edit image');

$this->menu = array(
	array('label' => SliderModule::t('Manage slider'), 'url' => array('admin')),
	array('label' => SliderModule::t('Add image'), 'url' => array('create')),
	array('label' => SliderModule::t('Delete image'), 'url' => '#',
		'url'=>'#',
		'linkOptions'=>array(
			'submit'=>array('delete','id'=>$model->id),
			'confirm'=> tc('Are you sure you want to delete this item?')
		),
	)
);
$this->adminTitle = SliderModule::t('Edit image');
?>

<?php echo $this->renderPartial('/backend/_form', array('model'=>$model, 'isCreate'=>false)); ?>