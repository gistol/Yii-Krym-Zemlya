<?php
$this->breadcrumbs=array(
    tt('Management of advertizing blocks') => array('/advertising/backend/advert/admin')
);

$this->menu=array(
    array('label'=>tt('Add block'), 'url'=>array('/advertising/backend/advert/create')),
);

$this->adminTitle = tt('Management of advertizing blocks');
?>

<div class="well">
	<div id="advert-admin">
		<?php
		if($dataProvider->itemCount){
			$this->widget('CustomListView', array(
				'dataProvider' => $dataProvider,
				'itemView' => '_view',
				'itemsTagName' => 'ol',
				'itemsCssClass' => 'rkl-blocks',
				'sortableAttributes' => array(
					'position',
					'type',
					'active',
				),
			));
		}
		else {
			echo Yii::t('zii','No results found.');
		}
		?>
	</div>
</div>