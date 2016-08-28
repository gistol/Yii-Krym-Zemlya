<div class="rowold">
    <?php echo CHtml::activeLabelEx($model,'email'); ?>
    <?php echo CHtml::activeTextField($model,'email',array('size'=>60,'maxlength'=>255)); ?>
    <?php echo CHtml::error($model,'email'); ?>
</div>

<div class="rowold">
    <?php echo CHtml::activeLabelEx($model,'mode'); ?>
    <?php echo CHtml::activeDropDownList($model,'mode',$this->getModeOptions()); ?>
    <?php echo CHtml::error($model,'mode'); ?>
</div>
