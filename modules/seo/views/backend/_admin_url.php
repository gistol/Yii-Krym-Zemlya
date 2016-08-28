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
            'filter'=>SeoFriendlyUrl::getModelNameList(),
            'value' => '$data->getModelName()'
        ),
        array(
            'header' => tt('URL'),
            'name'=>'url_'.Yii::app()->language,
            'type'=>'raw',
            'value' => '$data->getUrlForTable()'
        ),
        array(
            'header' => $model->getAttributeLabel('title'),
            'name'=>'title_'.Yii::app()->language,
            'type'=>'raw',
        ),
        array(
            'class'=>'bootstrap.widgets.TbButtonColumn',
            'template' => '{update}',
        ),
    ),
));
?>

<div class="clear">&nbsp;</div>
