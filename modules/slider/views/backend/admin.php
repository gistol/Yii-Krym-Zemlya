<?php
$this->pageTitle=Yii::app()->name . ' - ' . SliderModule::t('Manage slider');

$this->menu = array(
	array('label' => SliderModule::t('Add image'), 'url' => array('create')),
);
$this->adminTitle = SliderModule::t('Manage slider');
?>

<?php $this->widget('CustomGridView', array(
	'id'=>'slider-grid',
	'dataProvider'=>$model->search(),
	'afterAjaxUpdate' => 'function(){$("a[rel=\'tooltip\']").tooltip(); $("div.tooltip-arrow").remove(); $("div.tooltip-inner").remove(); $("a.fancy").fancybox(); reInstallSortable(); attachStickyTableHeader();}',
	'rowCssClassExpression'=>'"items[]_{$data->id}"',
	'rowHtmlOptionsExpression' => 'array("data-bid"=>"items[]_{$data->id}")',
	'filter'=>$model,
	'columns'=>array(
		array(
			'class'=>'CCheckBoxColumn',
			'id'=>'itemsSelected',
			'selectableRows' => '2',
			'htmlOptions' => array(
				'class'=>'center',
			),
		),
		array(
			'name' => 'active',
			'header' => tc('Status'),
			'type' => 'raw',
			'value' => 'Yii::app()->controller->returnStatusHtml($data, "slider-grid", 1)',
			'htmlOptions' => array('class'=>'slider_status_column'),
			'filter' => false,
			'sortable' => false,
		),
		array (
			'name' => 'img',
			'type' => 'raw',
			'value'=>'Yii::app()->controller->returnImageFancy($data, "slider-grid", 0, 150, 85)',
			'htmlOptions' => array('style' => 'height: 85px; width: 150px;'),
			'filter' => false,
			'sortable' => false,
		),
		array(
			'header' => tt('Image title', 'slider'),
			'name' => 'title_'.Yii::app()->language,
			'type'=>'raw',
			'sortable' => false,
		),
		array(
			'name'=>'url',
			'type'=>'raw',
			'value' => '$data->url?CHtml::link($data->url, $data->url, array("target" => "_blank")):""',
			'sortable' => false,
		),
		array(
			'class'=>'bootstrap.widgets.TbButtonColumn',
			'template'=>'{up} {down} {update} {delete}',
			'htmlOptions' => array('class'=>'infopages_buttons_column'),
			'buttons' => array(
				'up' => array(
					'label' => tc('Move an item up'),
					'imageUrl' => $url = Yii::app()->assetManager->publish(
						Yii::getPathOfAlias('zii.widgets.assets.gridview').'/up.gif'
					),
					'url'=>'Yii::app()->createUrl("/slider/backend/main/move", array("id"=>$data->id, "direction" => "up"))',
					'options' => array('class'=>'slider_arrow_image_up'),
					'visible' => '$data->sorter > "'.$minSorter.'"',
					'click' => "js: function() { ajaxMoveRequest($(this).attr('href'), 'slider-grid'); return false;}",
				),
				'down' => array(
					'label' => tc('Move an item down'),
					'imageUrl' => $url = Yii::app()->assetManager->publish(
						Yii::getPathOfAlias('zii.widgets.assets.gridview').'/down.gif'
					),
					'url'=>'Yii::app()->createUrl("/slider/backend/main/move", array("id"=>$data->id, "direction" => "down"))',
					'options' => array('class'=>'slider_arrow_image_down'),
					'visible' => '$data->sorter < "'.$maxSorter.'"',
					'click' => "js: function() { ajaxMoveRequest($(this).attr('href'), 'slider-grid'); return false;}",
				),
            ),
			'deleteConfirmation' => tc('Are you sure you want to delete this item?'),
		),
	),
)); ?>

<?php

$this->renderPartial('//site/admin-select-items', array(
	'url' => '/slider/backend/main/itemsSelected',
	'id' => 'slider-grid',
	'model' => $model,
	'options' => array(
		'activate' => Yii::t('common', 'Activate'),
		'deactivate' => Yii::t('common', 'Deactivate'),
		'delete' => Yii::t('common', 'Delete')
	),
));

$csrf_token_name = Yii::app()->request->csrfTokenName;
$csrf_token = Yii::app()->request->csrfToken;

$cs = Yii::app()->getClientScript();
$cs->registerCoreScript('jquery.ui');

$str_js = "
		var fixHelper = function(e, ui) {
			ui.children().each(function() {
				$(this).width($(this).width());
			});
			return ui;
		};

		function reInstallSortable(id, data) {
			installSortable();
		}

		function updateGrid() {
			$.fn.yiiGridView.update('slider-grid');
		}

		function installSortable() {
			$('#slider-grid table.items tbody').sortable({
				forcePlaceholderSize: true,
				forceHelperSize: true,
				items: 'tr',
				update : function () {
					serial = $('#slider-grid table.items tbody').sortable('serialize', {key: 'items[]', attribute: 'data-bid'}) + '&{$csrf_token_name}={$csrf_token}';
					$.ajax({
						'url': '" . $this->createUrl('/slider/backend/main/sortitems') . "',
						'type': 'post',
						'data': serial,
						'success': function(data){
							updateGrid();
						},
						'error': function(request, status, error){
							alert('We are unable to set the sort order at this time.  Please try again in a few minutes.');
						}
					});
				},
				helper: fixHelper
			}).disableSelection();
		}

		installSortable();
";

$cs->registerScript('sortable-project', $str_js);