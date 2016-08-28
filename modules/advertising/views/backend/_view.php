<li>
    <ul class="rkl-blocks-item">
        <li>
            <?php
            echo CHtml::link(tt('Edit'), array('update', 'id'=>$data->id));
            echo ' | ';
			echo CHtml::link(
				tc('Delete'),
				array('/advertising/backend/advert/delete', 'id' => $data->id, 'fr' => 'adm'),
				array(
					'confirm' => tt('You really want to remove the chosen block?'),
					'csrf' => true,
				)
			);
            ?>
        </li>
		<li>
			<strong><?php echo CHtml::encode($data->getAttributeLabel('type')); ?>:</strong>
			<?php echo CHtml::encode(Advert::getCurrentTypeName($data->type)); ?>
		</li>
		<li>
			<strong><?php echo CHtml::encode($data->getAttributeLabel('position')); ?>:</strong>
			<div>
				<?php echo CHtml::image(Yii::app()->theme->baseUrl."/images/advertpos/small{$data->position}.jpg"); ?>
			</div>
		</li>
		<li>
			<strong><?php echo CHtml::encode($data->getAttributeLabel('areas')); ?>:</strong>
			<?php
			$area = array();
			$data->areas = $data->getAreas();

			foreach ($data->areas as $item) {
				$areaName = $data->getCurrentAreasName($item);

				if($areaName)
					$area[] = $areaName;
			}
			echo implode(', ', $area);
			?>

		</li>

		<?php if($data->type == 'file'): ?>
			<li>
				<strong><?php echo CHtml::encode(Advert::getCurrentTypeName($data->type)); ?>:</strong>
				<div>
					<?php echo CHtml::image(Yii::app()->getBaseUrl(false)."/uploads/rkl/{$data->file_path}", $data->alt_text); ?>
				</div>
			</li>
			<?php if($data->url): ?>
				<li>
					<strong><?php echo CHtml::encode($data->getAttributeLabel('url')); ?>:</strong>
					<?php echo CHtml::encode($data->url); ?>
				</li>
			<?php endif; ?>

			<?php if($data->alt_text ): ?>
				<li>
					<strong><?php echo CHtml::encode($data->getAttributeLabel('alt_text')); ?>:</strong>
					<?php echo CHtml::encode($data->alt_text); ?>
				</li>
			<?php endif; ?>
		<?php elseif($data->type == 'html'): ?>
			<li>
				<strong><?php echo CHtml::encode($data->getAttributeLabel('html')); ?>:</strong>
				<div>
					<?php echo $data->getHtml(); ?>
				</div>
			</li>
		<?php elseif($data->type == 'js'): ?>
			<li>
				<strong><?php echo CHtml::encode($data->getAttributeLabel('js')); ?>:</strong>
				<div>
					<?php echo CHtml::encode($data->getJs()); ?>
				</div>
			</li>
		<?php endif;?>

		<li>
			<strong><?php echo CHtml::encode($data->getAttributeLabel('active')); ?>:</strong>
			<?php echo ($data->active) ? tc('Yes') : tc('No'); ?>
		</li>

		<li>
			<strong><?php echo CHtml::encode($data->getAttributeLabel('views')); ?>:</strong>
			<?php echo CHtml::encode($data->views); ?>
		</li>

		<?php if ($data->url) : ?>
			<li>
				<strong><?php echo CHtml::encode($data->getAttributeLabel('clicks')); ?>:</strong>
				<?php echo CHtml::encode($data->clicks); ?>
			</li>
		<?php endif;?>
    </ul>
</li>
<hr />