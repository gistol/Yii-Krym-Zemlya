<div class="rowold">
    <?php echo CHtml::activeLabelEx($model,'merchant_id'); ?>
    <?php echo CHtml::activeTextField($model,'merchant_id',array('size'=>60,'maxlength'=>255)); ?>
    <?php echo CHtml::error($model,'merchant_id'); ?>
</div>

<div class="rowold">
    <?php echo CHtml::activeLabelEx($model,'merchant_sig'); ?>
    <?php echo CHtml::activeTextField($model,'merchant_sig',array('size'=>60,'maxlength'=>255)); ?>
    <?php echo CHtml::error($model,'merchant_sig'); ?>
</div>
