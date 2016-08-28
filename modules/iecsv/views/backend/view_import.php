<?php
$this->pageTitle=Yii::app()->name . ' - ' . IecsvModule::t('Import / Export');

$this->breadcrumbs = array(
	tt('Import'),
);

$this->menu = array(
    array('label' => tc('Import / Export'), 'url' => array('admin')),
	array('label' => tt('Export'), 'url' => array('viewExport')),
);

$this->adminTitle = tt('Import');
?>

<div class="form">
    <p><?php echo tt('You can populate your database importing a csv file with properties. You can also import listings with photos: create an archive of .zip structure described for ‘Export‘ operation.'); ?></p>
    <p><?php echo tt('Import from *.csv, *.zip:'); ?></p>
    <p><?php echo tt('Supported file *.csv encoding is UTF-8 without BOM.'); ?></p>
</div>

<div class="form">
	<?php
		echo CHtml::form($this->createAbsoluteUrl('/iecsv/backend/main/importUpload'), 'post', array('enctype' => 'multipart/form-data', 'id' => 'import-form', 'name' => 'import-form', 'class' => 'well'));
		echo CHtml::activeFileField($model, 'import');

	?>
		<div class="rowold buttons">
			<?php $this->widget('bootstrap.widgets.TbButton',
				array('buttonType'=>'submit',
					'type'=>'primary',
					'icon'=>'ok white',
					'label'=> tt('Import'),
					/*'htmlOptions' => array(
						'onclick' => 'setCheckedCookie(); $("#export-form").submit();',
					)*/
				));
			?>
		</div>

	<?php echo CHtml::endForm(); ?>
</div><!-- form -->