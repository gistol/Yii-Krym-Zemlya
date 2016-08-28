<?php
$this->breadcrumbs=array(
	tt('Manage currency'),
);

$this->adminTitle = tt('Manage currency');

$this->menu=array(
    array('label'=>tt("Add currency"), 'url'=>array('create')),
	array('label'=>tt('Update currency now'), 'url'=>array('updateCurrency'))
);
echo '<div class="currency_source">';
echo '<h4>'.tt('Curency source').'</h4>';
echo CHtml::radioButtonList('currency_source', param('currencySource',1), Currency::getCurrencySourceList(), array('onclick'=>'changeCurrencySource($(this))'));
echo '</div>';

$this->widget('CustomGridView', array(
	'id'=>'currency-grid',
	'dataProvider'=>$model->search(),
	'filter'=>$model,
	'afterAjaxUpdate' => 'function(){$("a[rel=\'tooltip\']").tooltip(); $("div.tooltip-arrow").remove(); $("div.tooltip-inner").remove(); attachStickyTableHeader();}',
	'columns'=>array(
		array(
			'header' => tc('Status'),
			'type' => 'raw',
			'value' => 'Yii::app()->controller->returnStatusHtml($data, "currency-grid", 1, Currency::getUsedCurrenciesIds())',
			'htmlOptions' => array(
				'class'=>'width50',
			),
		),
		array(
			'name' => 'is_default',
			'type' => 'raw',
			'value' => '$data->getIsDefaultHtml()',
			'filter' => false,
			'sortable' => false,
			'htmlOptions' => array(
				'class'=>'width100 center',
			),
		),
		'char_code',
		array(
			'header' => tc('Translate'),
			'value' => 'tt($data->char_code."_translate")'
		),
		'value',
		array(
			'class'=>'bootstrap.widgets.TbButtonColumn',
			'template' => '{update} {delete}',
		),
	),
)); ?>

<?php $this->beginWidget('bootstrap.widgets.TbModal', array('id'=>'myModal')); ?>

<div id="form_default">
    <div class="modal-header">
        <a class="close" data-dismiss="modal">&times;</a>
        <h3><?php echo tt('Set the default currency') . ' - <span id="set_char_code"></span>'; ?></h3>
    </div>

    <div class="modal-body">
        <input type="hidden" name="currency_id" id="currency_id" value="">

        <div class="rowold">
            <?php echo CHtml::checkBox('convert_data', true); ?>
            <?php echo CHtml::label(tt('Convert the data in this currency?'), 'convert_data', array('class'=>'noblock')); ?>
        </div>

    </div>

    <div class="modal-footer">
        <a href="#" class="btn btn-primary" onclick="saveChanges(); return false;"><?php echo tt('Set default'); ?></a>

        <?php $this->widget('bootstrap.widgets.TbButton', array(
            'label'=>tc('Close'),
            'url'=>'#',
            'htmlOptions'=>array('data-dismiss'=>'modal'),
        )); ?>
    </div>
</div>

<?php $this->endWidget();

Yii::app()->clientScript->registerScript('setDefCur', "
    function saveChanges(){

        var id = $('#currency_id').val();

        var convert_data = $('#convert_data:checked').val();
        $.ajax({
            type: 'POST',
            url: '".Yii::app()->request->baseUrl."/currency/backend/main/setDefault',
            data: { 'id' : id, 'convert_data' : convert_data },
			success: function(msg){
				$('#currency-grid').yiiGridView.update('currency-grid');
				$('#myModal').modal('hide');
        }
        });
        return;
    }",
	CClientScript::POS_END);

Yii::app()->clientScript->registerScript('setCurSource', "
	function changeCurrencySource(item) {
		var id = item.val();
		$.ajax({
            type: 'POST',
            url: '".Yii::app()->request->baseUrl."/currency/backend/main/setCurrencySource',
            data: { 'id' : id }
        });
	}
", CClientScript::POS_END);

?>