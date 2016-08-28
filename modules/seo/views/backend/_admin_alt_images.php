<div class="clear">&nbsp;</div>

<?php
$this->widget('CustomGridView', array(
    'dataProvider'=>$model->search(),
    'filter'=>$model,
    'afterAjaxUpdate' => 'function(){$("a[rel=\'tooltip\']").tooltip(); $("div.tooltip-arrow").remove(); $("div.tooltip-inner").remove(); attachStickyTableHeader();}',
    'columns'=>array(
        array(
            'name'=>'model_name',
            'type'=>'raw',
            'filter'=>SeoFriendlyUrl::getModelImagesNameList(),
            'value' => '$data->getModelImageName()',
        ),
        array(
            'header' => tt('ALT'),
            'name'=>'alt_'.Yii::app()->language,
            'type'=>'raw',
            'value' => 'CHtml::encode($data->getStrByLang("alt"))',
        ),
		array(
            'header' => tc('ID'),
            'type'=>'raw',
            'value' => '$data->getImageUrlForParent()',
			'sortable' => false,
			'filter' => false,
        ),
        array(
            'class'=>'bootstrap.widgets.TbButtonColumn',
            'template' => '{update}',
			'buttons'=>array (
				'update' => array (
					'url'=>"Yii::app()->createUrl('/seo/backend/main/updateseoimage', array('id' => \$data->id))",
				),
			),
        ),
    ),
));
?>

<div class="clear">&nbsp;</div>
