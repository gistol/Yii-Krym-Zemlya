<div class="rowold">
	<?php echo CHtml::activeLabelEx($model,'MERCHANT_ID'); ?>
	<?php echo CHtml::activeTextField($model,'MERCHANT_ID',array('size'=>60,'maxlength'=>128)); ?>
	<?php echo CHtml::error($model,'MERCHANT_ID'); ?>
</div>

<div class="rowold">
	<?php echo CHtml::activeLabelEx($model,'SECRET_KEY'); ?>
	<?php echo CHtml::activeTextField($model,'SECRET_KEY',array('size'=>60,'maxlength'=>255)); ?>
	<?php echo CHtml::error($model,'SECRET_KEY'); ?>
</div>
