<?php
$this->menu = array(
	array(),
);

$this->adminTitle = tt('Messages', 'messages');
?>

<?php
$columns[]=array(
	'header' => '',
	'type' => 'raw',
	'value'=>function($data, $row){
			return (Messages::getCountUnreadFromUser($data->id)) ? CHtml::image(Yii::app()->theme->baseUrl.'/images/new_message.png', tt('New message', 'messages'), array('title' => tt('New message', 'messages'))) : '';
		},
	'htmlOptions' => array(
		'style' => 'width: 20px;',
	),
	'sortable' => false,
	'filter' => false,
);

$columns[]=array(
	'header' => tt('User', 'messages'),
	'value' => 'Yii::app()->controller->returnHtmlMessageSenderName($data, true)',
	'sortable' => false,
	'filter' => false,
);

$columns[] = array(
	'class'=>'bootstrap.widgets.TbButtonColumn',
	'template'=>'{view}',
	//'deleteConfirmation' => tt('Are you sure?', 'messages'),
	//'deleteButtonUrl' => "Yii::app()->createUrl('/messages/backend/main/delete', array('id' => \$data->id))",
	'viewButtonUrl' => "Yii::app()->createUrl('/messages/backend/main/read', array('id' => \$data->id))",
);

$this->widget('CustomGridView', array(
	'id'=>'messages-users-grid',
	'afterAjaxUpdate' => 'function(){attachStickyTableHeader();}',
	'dataProvider'=>$itemsProvider,
	'columns'=>$columns,
	'emptyText' => tt('no_messages', 'messages'),
	//'enablePagination'=> true,
	//'template' => '{summary}{items}',
));
?>