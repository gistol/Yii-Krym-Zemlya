<div class="seo-page-div">
    <h2><?php echo tt('Management of compliance of URL, title, description and keywords'); ?></h2>
	<?php
	$this->widget('bootstrap.widgets.TbMenu', array(
		'type'=>'pills', // '', 'tabs', 'pills' (or 'list')
		'stacked'=>false, // whether this is a stacked menu
		'items'=>array(
			array('label'=>tt('Add compliance'), 'url'=>array('/seo/backend/page/create')),
		)
	));

	$this->widget('CustomGridView', array(
		'id'=>'seo-page-grid',
		'dataProvider'=>$modelPage->search(),
		'filter' => $modelPage,
		'afterAjaxUpdate' => 'function(){$("a[rel=\'tooltip\']").tooltip(); $("div.tooltip-arrow").remove(); $("div.tooltip-inner").remove(); attachStickyTableHeader();}',
		'columns'=>array(
			array(
				'name' => 'url',
				'sortable' => false,
				'type' => 'raw',
				'value' => 'CHtml::encode($data->url)',
				//'filter' => false,
			),
			array(
				'header' => tt('Title'),
				'name' => 'title_'.Yii::app()->language,
				'sortable' => false,
				'type' => 'raw',
				'value' => 'CHtml::encode($data->title)',
			),
			array(
				'header' => tt('Description'),
				'name' => 'description_'.Yii::app()->language,
				'sortable' => false,
				'type' => 'raw',
				'value' => 'CHtml::encode($data->description)',
			),
			array(
				'header' => tt('Keywords'),
				'name' => 'keywords_'.Yii::app()->language,
				'sortable' => false,
				'type' => 'raw',
				'value' => 'CHtml::encode($data->keywords)',
			),
			array(
				'class'=>'bootstrap.widgets.TbButtonColumn',
				'template'=>'{update} {delete}',
				'deleteConfirmation' => tt('Are you sure you want to delete this compliance?'),
				'deleteButtonUrl' => "Yii::app()->createUrl('/seo/backend/page/delete', array('id' => \$data->id))",
				'updateButtonUrl' => "Yii::app()->createUrl('/seo/backend/page/update', array('id' => \$data->id))",
			),
		)
	));
	?>
</div>