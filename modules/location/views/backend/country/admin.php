<?php

$this->menu=array(
	array('label'=>tt('Add country'), 'url'=>array('/location/backend/country/create')),
);

$this->adminTitle = tt('Manage countries');

?>

<?php $this->widget('CustomGridView', array(
	'id'=>'country-grid',
	'dataProvider'=>$model->search(),
	'afterAjaxUpdate' => 'function(){$("a[rel=\'tooltip\']").tooltip(); $("div.tooltip-arrow").remove(); $("div.tooltip-inner").remove(); reInstallSortable(); attachStickyTableHeader();}',
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
			'name'=>'active',
			'header' => tc('Status'),
			'type' => 'raw',
			'value' => 'Yii::app()->controller->returnStatusHtml($data, "country-grid", 1)',
			'htmlOptions' => array(
				'style' => 'width: 100px;',
			),
			'sortable' => false,
			'filter'=> array('0'=>tc('Inactive'), '1'=>tc('Active'))
		),
		array(
			'class' => 'editable.EditableColumn',
			'header' => tc('Name'),
			'name' => 'name_'.Yii::app()->language,
			'value' => '$data->getStrByLang("name")',
			'editable' => array(
				'url' => Yii::app()->controller->createUrl('/location/backend/country/ajaxEditColumn', array('model' => 'Country', 'field' => 'name_'.Yii::app()->language)),
				'placement' => 'right',
				'emptytext' => '',
				'savenochange' => 'true',
				'title' => tc('Name'),
				'options' => array(
					'ajaxOptions' => array('dataType' => 'json')
				),
				'success' => 'js: function(response, newValue) {
					if (response.msg == "ok") {
						message("'.tc("Success").'");
					}
					else if (response.msg == "save_error") {
						var newValField = "'.tt("Error. Repeat attempt later", 'blockIp').'";

						return newValField;
					}
					else if (response.msg == "no_value") {
						var newValField = "'.tt("Enter the required value", 'configuration').'";

						return newValField;
					}
				}',
			),
			'sortable' => false,
		),
		array(
			'class'=>'bootstrap.widgets.TbButtonColumn',
			'template'=>'{up} {down} {fast_up} {fast_down} {update} {delete}',
			'deleteConfirmation' => tc('Are you sure you want to delete this item?'),
			'htmlOptions' => array('class'=>'infopages_buttons_column', 'style'=>'width:160px;'),
			'buttons' => array(
				'up' => array(
					'label' => tc('Move an item up'),
					'imageUrl' => $url = Yii::app()->assetManager->publish(
						Yii::getPathOfAlias('zii.widgets.assets.gridview').'/up.gif'
					),
					'url'=>'Yii::app()->createUrl("/location/backend/country/move", array("id"=>$data->id, "direction" => "up"))',
					'options' => array('class'=>'infopages_arrow_image_up'),
					'visible' => '$data->sorter > "'.$minSorter.'"',
					'click' => "js: function() { ajaxMoveRequest($(this).attr('href'), 'country-grid'); return false;}",
				),
				'down' => array(
					'label' => tc('Move an item down'),
					'imageUrl' => $url = Yii::app()->assetManager->publish(
						Yii::getPathOfAlias('zii.widgets.assets.gridview').'/down.gif'
					),
					'url'=>'Yii::app()->createUrl("/location/backend/country/move", array("id"=>$data->id, "direction" => "down"))',
					'options' => array('class'=>'infopages_arrow_image_down'),
					'visible' => '$data->sorter < "'.$maxSorter.'"',
					'click' => "js: function() { ajaxMoveRequest($(this).attr('href'), 'country-grid'); return false;}",
				),
				'fast_up' => array(
					'label' => tc('Move to the beginning of the list'),
					'imageUrl' => Yii::app()->theme->baseUrl.'/images/default/fast_top_arrow.gif',
					'url'=>'Yii::app()->createUrl("/location/backend/country/move", array("id"=>$data->id, "direction" => "fast_up"))',
					'options' => array('class'=>'infopages_arrow_image_fast_up'),
					'visible' => '$data->sorter > "'.$minSorter.'"',
					'click' => "js: function() { ajaxMoveRequest($(this).attr('href'), 'country-grid'); return false;}",
				),
				'fast_down' => array(
					'label' => tc('Move to end of list'),
					'imageUrl' => Yii::app()->theme->baseUrl.'/images/default/fast_bottom_arrow.gif',
					'url'=>'Yii::app()->createUrl("/location/backend/country/move", array("id"=>$data->id, "direction" => "fast_down"))',
					'options' => array('class'=>'infopages_arrow_image_fast_down'),
					'visible' => '$data->sorter < "'.$maxSorter.'"',
					'click' => "js: function() { ajaxMoveRequest($(this).attr('href'), 'country-grid'); return false;}",
				),
			),
		),
	),
));

$this->renderPartial('//site/admin-select-items', array(
	'url' => '/location/backend/country/itemsSelected',
	'id' => 'country-grid',
	'model' => $model,
	'options' => array(
		'activate' => Yii::t('common', 'Activate'),
		'deactivate' => Yii::t('common', 'Deactivate'),
		'delete' => Yii::t('common', 'Delete')
	),
));
?>


<?php

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
			$.fn.yiiGridView.update('country-grid');
		}

		function installSortable() {
			$('#country-grid table.items tbody').sortable({
				forcePlaceholderSize: true,
				forceHelperSize: true,
				items: 'tr',
				update : function () {
					serial = $('#country-grid table.items tbody').sortable('serialize', {key: 'items[]', attribute: 'data-bid'}) + '&{$csrf_token_name}={$csrf_token}';
					$.ajax({
						'url': '" . $this->createUrl('/location/backend/country/sortitems') . "',
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