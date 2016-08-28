<?php
$this->pageTitle=Yii::app()->name . ' - ' . tt('History changes', 'historyChanges');
$this->menu=array(
	array()
);
$this->adminTitle = tt('History changes', 'historyChanges');

$this->widget('CustomGridView', array(
		'id'=>'history-changes-grid',
        'dataProvider'=>$model->search(),
        'filter'=>$model,
        'afterAjaxUpdate' => 'function(){$("a[rel=\'tooltip\']").tooltip(); $("div.tooltip-arrow").remove(); $("div.tooltip-inner").remove(); jQuery("#HistoryChanges_date_created").datepicker(jQuery.extend(jQuery.datepicker.regional["'.Yii::app()->controller->datePickerLang.'"],{"showAnim":"fold","dateFormat":"yy-mm-dd","changeMonth":"true","showButtonPanel":"true","changeYear":"true"})); attachStickyTableHeader();}',
        'columns'=>array(
    		array(
    			'name' => 'id',
    			'htmlOptions' => array(
    				'class'=>'span1',
    			),
    		),
            array(
				'name'=>'date_created',
				'value' => 'HSite::convertDateToDateWithTimeZone($data->date_created)',
				'type'=>'raw',
				'filter'=>$this->widget('zii.widgets.jui.CJuiDatePicker', array(
					'model'=>$model,
					'attribute'=>'date_created',
					'language' => Yii::app()->controller->datePickerLang,
					'options' => array(
						'showAnim'=>'fold',
						'dateFormat'=> 'yy-mm-dd',
						'changeMonth' => 'true',
						'changeYear'=>'true',
						'showButtonPanel' => 'true',
					),
				),true),
				'htmlOptions' => array('style' => 'width:130px;'),
			),
            array(
                'name' => 'description',
                'value' => '$data->getDescr()',
                'type' => 'html',
            ),
            array(
                'name' => 'field',
            ),
			array(
                'name' => 'model_name',
            ),
			array(
                'name' => 'model_id',
            ),
            array(
                'name' => 'old_value',
                //'value' => 'HSite::markdown($data->old_value)',
                //'type' => 'html',
				'value' => '$data->old_value',
            ),
            array(
                'name' => 'new_value',
                //'value' => 'HSite::markdown($data->new_value)',
                //'type' => 'html',
				'value' => '$data->new_value',
            ),
        ),
    )
);