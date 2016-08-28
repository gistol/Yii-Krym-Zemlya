<div class="form">

    <?php
    $translate = isset($translate) ? $translate : $model->getTranslateModel();

    $form=$this->beginWidget('CustomForm', array(
        'id'=>$this->modelName.'-form',
        'enableAjaxValidation'=>false,
		'htmlOptions' => array('class' => 'well form-disable-button-after-submit'),
    )); ?>

    <p class="note"><?php echo Yii::t('common', 'Fields with <span class="required">*</span> are required.');?></p>

    <?php
    echo $form->errorSummary(array($model, $translate));

    if($model->type != FormDesigner::TYPE_DEFAULT){
        if($model->isNewRecord){
            echo $form->dropDownListRow($model, 'type', FormDesigner::getTypesList(), array('class' => 'span3'));
            ?>

            <div id="selReferenceBox" style="display: none;">
                <?php
                $references = HFormEditor::getReferencesList(true);

				echo $form->dropDownListRow($model, 'reference_id', $references, array('class' => 'span3'));
                ?>
            </div>
        <?php
        } else {
			echo '<div class="rowold">';
            echo '<br/>';
            echo '<b>'.tt('The name of a field in a table', 'formeditor').'</b>: '.$model->field.'';
            echo '<br/>';
            echo '<b>'.$model->getAttributeLabel('type').'</b>: '.$model->getTypeName().'';
            echo '</div><br />';
        }

        $this->widget('application.modules.lang.components.langFieldWidget', array(
            'model' => $model,
            'field' => 'label',
            'type' => 'string',
        ));
    }

    echo $form->dropDownListRow($model, 'view_in', FormDesigner::getViewInList(), array('class' => 'span3'));

	if($model->isNewRecord){
		echo $form->dropDownListRow($model, 'rules', FormDesigner::getRulesList(), array('class' => 'span3'));
	}
	else {
		echo CHtml::hiddenField('FormDesigner[rules]', $model->rules);
		echo '<div class="rowold">';
		echo '<br /><b>'.$model->getAttributeLabel('rules').'</b>: '.FormDesigner::getRulesList($model->rules).'';
		echo '</div><br />';
	}

    if($model->not_hide == 0) {
        echo $form->dropDownListRow($model, 'visible', FormDesigner::getVisibleList(), array('class' => 'span3'));

        echo $form->checkBoxListRow($model, 'apTypesArray', HApartment::getTypesArray());
        echo $form->checkBoxListRow($model, 'objTypesArray', ApartmentObjType::getList());
    }

    $withoutTip = FormDesigner::getFieldsWithoutTip();

    if(!in_array($model->field, $withoutTip)){
        $this->widget('application.modules.lang.components.langFieldWidget', array(
            'model' => $model,
            'field' => 'tip',
            'type' => 'string',
        ));
    }

    echo '<div class="fields_for_search">';
    echo '<h5>'.tt('For search').'</h5>';

    $this->widget('application.modules.lang.components.langFieldWidget', array(
        'model' => $translate,
        'field' => 'translation',
        'type' => 'string',
    ));
	if($model->isNewRecord){
		echo $form->dropDownListRow($model, 'compare_type', FormDesigner::getCompareList(), array('class' => 'span3'));
	}
	else {
		echo CHtml::hiddenField('FormDesigner[compare_type]', $model->compare_type);
		echo '<div class="rowold">';
		echo '<br /><b>'.$model->getAttributeLabel('compare_type').'</b>: '.FormDesigner::getCompareList($model->compare_type).'';
		echo '</div><br />';
	}

    echo '</div>';
    ?>

    <div id="selMeasureUnitBox" style="display: none;">
        <?php echo $form->textFieldRow($model, 'measure_unit'); ?>
    </div>

    <br/>

    <div class="rowold buttons">
        <?php $this->widget('bootstrap.widgets.TbButton',
            array('buttonType'=>'submit',
                'type'=>'primary',
                'icon'=>'ok white',
                'label'=> tc('Save'),
				'htmlOptions' => array(
					'class' => 'submit-button',
				),
            )); ?>
    </div>

    <?php $this->endWidget(); ?>

</div><!-- form -->

<script type="text/javascript">
    $(function(){
        formEditor.checkType();
        //formEditor.checkRules();

        $('#FormDesigner_type').on('change', function(){
            formEditor.checkType();
        });

//        $('#FormDesigner_rules').on('change', function(){
//            formEditor.checkRules();
//        });
    });

    var formEditor = {
        checkType: function(){
            var type = $('#FormDesigner_type').val();
            if(type == <?php echo CJavaScript::encode(FormDesigner::TYPE_REFERENCE);?> || type == <?php echo CJavaScript::encode(FormDesigner::TYPE_MULTY);?>){
                $('#selReferenceBox').show();
            } else {
                $('#selReferenceBox').hide();
            }

            if(type == <?php echo CJavaScript::encode(FormDesigner::TYPE_INT);?>){
                $('#selMeasureUnitBox').show();
            } else {
                $('#FormDesigner_measure_unit').val('');
                $('#selMeasureUnitBox').hide();
            }
        }

    }
</script>