<?php


$this->menu=array(
	array('label'=>tt('Add city'), 'url'=>array('/location/backend/city/create')),
	array('label'=>tt('Add multiple cities'), 'url'=>array('/location/backend/city/createMulty')),
);

$this->adminTitle = tt('Manage cities');

$this->widget('CustomGridView', array(
	'id'=>'city-grid',
	'dataProvider'=>$model->search(),
	'filter'=>$model,
	'afterAjaxUpdate' => 'function(id, data){$("a[rel=\'tooltip\']").tooltip(); $("div.tooltip-arrow").remove(); $("div.tooltip-inner").remove(); reInstallSortable(id, data); attachStickyTableHeader();}',
	'rowCssClassExpression'=>'"items[]_{$data->id}"',
	'rowHtmlOptionsExpression' => 'array("data-bid"=>"items[]_{$data->id}")',
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
			'class' => 'editable.EditableColumn',
			'name' => 'active',
			'type' => 'raw',
			'value' => 'Yii::app()->controller->returnCityActiveHtml($data)',
			'editable' => array(
				'url' => Yii::app()->controller->createUrl('/location/backend/city/ajaxEditColumn', array('model' => 'City', 'field' => 'active')),
				'placement' => 'right',
				'emptytext' => '',
				'savenochange' => 'true',
				'title' => tc('Status'),
				'type' => 'select',
				'source' => City::getAvalaibleStatusArray(),
				'placement' => 'top',
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
			'filter' => City::getModerationStatusArray(),
			'htmlOptions' => array(
				'style' => 'width: 150px;',
			),
		),
		array(
			'name' => 'country_id',
			'value' => '$data->country_id ? $data->country->name : ""',
			'htmlOptions' => array(
				'style' => 'width: 150px;',
			),
			'sortable' => false,
			'filter' => Country::getCountriesArray(0, 1),
		),
		array(
			'name' => 'region_id',
			'value' => '$data->region_id ? $data->region->name : ""',
			'htmlOptions' => array(
				'style' => 'width: 150px;',
			),
			'sortable' => false,
			'filter' => Region::getRegionsArray($model->country_id, 0, 1),
		),
		array(
			'class' => 'editable.EditableColumn',
			'header' => tc('Name'),
			'name' => 'name_'.Yii::app()->language,
			'value' => '$data->getStrByLang("name")',
			'editable' => array(
				'url' => Yii::app()->controller->createUrl('/location/backend/city/ajaxEditColumn', array('model' => 'City', 'field' => 'name_'.Yii::app()->language)),
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
					'url'=>'Yii::app()->createUrl("/location/backend/city/move", array("id"=>$data->id, "direction" => "up", "regionid"=>$data->region_id))',
					'options' => array('class'=>'infopages_arrow_image_up'),
					'visible' => '($data->sorter > "'.$model->minSorter.'") && '.intval($model->region_id),
					'click' => "js: function() { ajaxMoveRequest($(this).attr('href'), 'city-grid'); return false;}",
				),
				'down' => array(
					'label' => tc('Move an item down'),
					'imageUrl' => $url = Yii::app()->assetManager->publish(
						Yii::getPathOfAlias('zii.widgets.assets.gridview').'/down.gif'
					),
					'url'=>'Yii::app()->createUrl("/location/backend/city/move", array("id"=>$data->id, "direction" => "down", "regionid"=>$data->region_id))',
					'options' => array('class'=>'infopages_arrow_image_down'),
					'visible' => '($data->sorter < "'.$model->maxSorter.'") && '.intval($model->region_id),
					'click' => "js: function() { ajaxMoveRequest($(this).attr('href'), 'city-grid'); return false;}",
				),
				'fast_up' => array(
					'label' => tc('Move to the beginning of the list'),
					'imageUrl' => Yii::app()->theme->baseUrl.'/images/default/fast_top_arrow.gif',
					'url'=>'Yii::app()->createUrl("/location/backend/city/move", array("id"=>$data->id, "direction" => "fast_up", "regionid"=>$data->region_id))',
					'options' => array('class'=>'infopages_arrow_image_fast_up'),
					'visible' => '($data->sorter > "'.$model->minSorter.'") && '.intval($model->region_id),
					'click' => "js: function() { ajaxMoveRequest($(this).attr('href'), 'city-grid'); return false;}",
				),
				'fast_down' => array(
					'label' => tc('Move to end of list'),
					'imageUrl' => Yii::app()->theme->baseUrl.'/images/default/fast_bottom_arrow.gif',
					'url'=>'Yii::app()->createUrl("/location/backend/city/move", array("id"=>$data->id, "direction" => "fast_down", "regionid"=>$data->region_id))',
					'options' => array('class'=>'infopages_arrow_image_fast_down'),
					'visible' => '($data->sorter < "'.$model->maxSorter.'") && '.intval($model->region_id),
					'click' => "js: function() { ajaxMoveRequest($(this).attr('href'), 'city-grid'); return false;}",
				),
			),
		),
	),
));

$this->renderPartial('//site/admin-select-items', array(
	'url' => '/location/backend/city/itemsSelected',
	'id' => 'city-grid',
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
			installSortable($(data).find('select[name=\"City[region_id]\"] option:selected').val());
		}

		function updateGrid() {
			$.fn.yiiGridView.update('city-grid');
		}

		function installSortable(areaIdSel) {
			if (areaIdSel > 0) {
				$('#city-grid table.items tbody').sortable({
					forcePlaceholderSize: true,
					forceHelperSize: true,
					items: 'tr',
					update : function () {
						serial = $('#city-grid table.items tbody').sortable('serialize', {key: 'items[]', attribute: 'data-bid'}) + '&{$csrf_token_name}={$csrf_token}&area_id=' + areaIdSel;
						$.ajax({
							'url': '" . $this->createUrl('/location/backend/city/sortitems') . "',
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
				});
			}
		}

		installSortable('".intval($model->region_id)."');
";

$cs->registerScript('sortable-project', $str_js);