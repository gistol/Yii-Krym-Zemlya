<div class="rowold">
	<?php echo CHtml::activeLabelEx($model,'merchant_id'); ?>
	<?php echo CHtml::activeTextField($model,'merchant_id',array('size'=>60,'maxlength'=>255)); ?>
	<?php echo CHtml::error($model,'merchant_id'); ?>
</div>

<div class="rowold">
	<?php echo CHtml::activeLabelEx($model,'secret_key'); ?>
	<?php echo CHtml::activeTextField($model,'secret_key',array('size'=>60,'maxlength'=>255)); ?>
	<?php echo CHtml::error($model,'secret_key'); ?>
</div>

<div class="rowold">
	<?php echo CHtml::activeLabelEx($model,'mode'); ?>
	<?php echo CHtml::activeDropDownList($model,'mode',$this->getModeOptions()); ?>
	<?php echo CHtml::error($model,'mode'); ?>
</div>
