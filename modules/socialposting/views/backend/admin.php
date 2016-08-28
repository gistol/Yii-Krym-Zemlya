<?php
$this->pageTitle=Yii::app()->name . ' - ' . tt('Configure the services of automatic posting');
$this->breadcrumbs=array(
	tt('Configure the services of automatic posting'),
);
$this->menu = array(
	array(),
);
$this->adminTitle = tt('Configure the services of automatic posting');


$info = $infoHelp = '';
//if (!SocialpostingModel::getSocialParamValue('useTwitter'))
	$info .= Yii::t('module_socialposting', 'Go to link for register Twitter application - {link}', array('{link}' => CHtml::link('https://dev.twitter.com/apps/new', 'https://dev.twitter.com/apps/new',  array('target' => '_blank')))).'<br />';

//if (!SocialpostingModel::getSocialParamValue('useVkontakte')) {
	$info .= Yii::t('module_socialposting', 'Go to link for register VK.com application - {link}', array('{link}' => CHtml::link('http://vk.com/editapp?act=create&site=1', 'http://vk.com/editapp?act=create&site=1',  array('target' => '_blank')))).'<br />';

	$vkApId = SocialpostingModel::getSocialParamValue('vkontakteApplicationId') ? SocialpostingModel::getSocialParamValue('vkontakteApplicationId') : 'YOUR_APPLICATION_ID';
	$infoHelp .= Yii::t('module_socialposting', 'Get a Token for VK.com - {link}', array('{link}' => CHtml::link('https://oauth.vk.com/authorize?client_id='.$vkApId.'&scope=groups,wall,offline,photos&redirect_uri=https://oauth.vk.com/blank.html&response_type=token', 'https://oauth.vk.com/authorize?client_id='.$vkApId.'&scope=groups,wall,offline,photos&redirect_uri=https://oauth.vk.com/blank.html&response_type=token',  array('target' => '_blank'))));
//}

if ($info)
	Yii::app()->user->setFlash('info', $info);

if ($infoHelp)
	Yii::app()->user->setFlash('warning', $infoHelp);

$this->widget('CustomGridView', array(
	'dataProvider'=>$model->search(),
	'filter'=>$model,
	'afterAjaxUpdate' => 'function(){$("a[rel=\'tooltip\']").tooltip(); $("div.tooltip-arrow").remove(); $("div.tooltip-inner").remove(); attachStickyTableHeader();}',
    'id'=>'socialposting-table',
	'columns'=>array(
        array(
            'header'=>tt('Section'),
			'name' => 'section',
            'value' => 'tt($data->section)',
            'filter' => $this->getSections(false),
        ),
		array(
            'header' => tt('Setting'),
			'value'=>'$data->title',
			'type'=>'raw',
			'htmlOptions' => array('class' => 'width250'),
		),
		array(
			'name'=>'value',
            'type'=>'raw',
			'value' => 'SocialpostingModel::getAdminValue($data)',
			'htmlOptions' => array('class' => 'width150'),
            'filter' => false,
            'sortable' => false,
		),
		array(
			'class'=>'bootstrap.widgets.TbButtonColumn',
			'template' => '{update}',
			'buttons' => array(
				'update' => array(
					'visible' => 'SocialpostingModel::getVisible($data->type)',
					//'options' => array('data-toggle' => 'modal'),
					'click' => 'js: function() { updateConfig($(this).attr("href")); return false; }'
					)
				)
		),
	),
)); ?>

<?php $this->beginWidget('bootstrap.widgets.TbModal', array('id'=>'myModal')); ?>

<div id="form_param"></div>

<div class="modal-footer">
    <a href="#" class="btn btn-primary" onclick="saveChanges(); return false;"><?php echo tc('Save'); ?></a>

    <?php $this->widget('bootstrap.widgets.TbButton', array(
        'label'=>tc('Close'),
        'url'=>'#',
        'htmlOptions'=>array('data-dismiss'=>'modal'),
    )); ?>
</div>

<?php $this->endWidget(); ?>

<script type="text/javascript">
    function updateConfig(href){
        $('#myModal').modal('show');
        $('#form_param').html('<img src="<?php echo Yii::app()->theme->baseUrl."/images/pages/indicator.gif"; ?>" alt="<?php echo tc('Content is loading ...'); ?>" style="position:absolute;margin: 10px;">');
        $('#form_param').load(href + '&ajax=1');
    }

    function saveChanges(){
        var val = $('#config_value').val();

        if(!val) {
            alert('<?php echo tt('Enter the required value');?>');
            return false;
        }

        var id = $('#config_id').val();
        $.ajax({
            type: "POST",
			url: "<?php echo Yii::app()->controller->createUrl('/socialposting/backend/main/updateAjax'); ?>",
            data: { "id": id, "val": val },
			success: function(msg){
				$('#socialposting-table').yiiGridView.update('socialposting-table');
				$('#myModal').modal('hide');
				if (msg == 'error_save') {
					document.location.href = '<?php echo Yii::app()->createUrl("/socialposting/backend/main/admin"); ?>';
				}
			}
        });
        return;
    }

</script>