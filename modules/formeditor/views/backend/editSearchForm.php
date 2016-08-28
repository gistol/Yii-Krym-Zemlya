<?php
$objTypes = ApartmentObjType::getList();
$objTypes = CMap::mergeArray(array(0 => tc('Default search')), $objTypes);

$cs = Yii::app()->clientScript;
$cs->registerCoreScript('jquery.ui');
$cs->registerCssFile(Yii::app()->clientScript->getCoreScriptUrl(). '/jui/css/base/jquery-ui.css');

$cs->registerScriptFile(Yii::app()->request->baseUrl . '/common/js/jquery.ui.touch-punch.min.js');

$this->adminTitle = tt('Edit search form', 'formeditor');

$this->menu = array(
    array('label'=>tc('The forms designer'), 'url'=>array('/formdesigner/backend/main/admin')),
);
?>

<div class="well edit-search-form-admin">

	<div class="flash-notice"><?php echo tt('Edit search form help', 'formeditor');?></div>

	<?php echo CHtml::dropDownList('obj_type_id', '', $objTypes); ?>

	<div class="row-fluid">
		<div class="span5">
			<ul id="sortable1" class="connectedSortable well sortBlue">
			</ul>
		</div>
		<div class="span5">
			<ul id="sortable2" class="connectedSortable well">
			</ul>
		</div>
	</div>

    <br/>

    <a href="javascript:;" class="btn btn-primary" onclick="saveSort();"><?php echo tc('Save');?></a>
</div>

<script>
    var tmpSort = [];

    $(function() {
        loadElements();

        $('#obj_type_id').on('change', function(){
           loadElements();
        });
    });

    function saveSort(sort){
        var sort = $('#sortable1').sortable('toArray', { attribute: 'key' });

        if(tmpSort == sort){
            message(<?php echo CJavaScript::encode(tc('Success'));?>);
            return false;
        }

        $.ajax({
            url: <?php echo CJavaScript::encode(Yii::app()->createUrl('/formeditor/backend/search/saveSort'));?>,
            dataType: 'json',
            type: 'get',
            data: {
                sort: sort,
                id: $('#obj_type_id').val()
            },
            success: function(data){
                if(data.status == 'ok'){
                    message(data.msg);
                    tmpSort = sort;
                }else{
                    error(data.msg);
                }
            }
        });
    }

    function loadElements(){
        $.ajax({
            url: <?php echo CJavaScript::encode(Yii::app()->createUrl('/formeditor/backend/search/loadElement'));?>,
            dataType: 'json',
            type: 'get',
            data: { id: $('#obj_type_id').val() },
            success: function(data){
                $('#sortable1').html(data.inForm);
                $('#sortable2').html(data.elements);

                $( "#sortable1, #sortable2" ).sortable({
                    connectWith: ".connectedSortable",
                    placeholder: "ui-state-highlight",
                    items: "li:not(.ui-state-disabled)"
//                    update: function( event, ui ) {
//                        if (this === ui.item.parent()[0]) {
//                            var sort = $('#sortable1').sortable('toArray', { attribute: 'key' });
//
//                            if(tmpSort != sort){
//                                saveSort(sort);
//                                tmpSort = sort;
//                            }
//                        }
//                    }
                });/*.disableSelection();*/
            }
        });
    }
</script>

