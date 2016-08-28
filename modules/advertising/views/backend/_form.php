<div class="form">
    <?php
		$types = Advert::getAvailableTypes();
		$positions = Advert::getAvailablePositions();
		$areas = Advert::getAvailableAreas();

		$typesJs = CJavaScript::encode(array_keys($types));
		Yii::app()->clientScript->registerScript('typesJs', "var typesJs = ".$typesJs.";", CClientScript::POS_END);

		$form=$this->beginWidget('CustomForm', array(
				'id'=>$this->modelName.'-form',
				'enableAjaxValidation'=>false,
				'htmlOptions' => array('enctype' => 'multipart/form-data', 'class' => 'well form-disable-button-after-submit'),
		));
	?>

    <p class="note"><?php echo Yii::t('common', 'Fields with <span class="required">*</span> are required.'); ?></p>

    <?php echo $form->errorSummary($model); ?>

	<div class="rowold">
		<?php echo $form->labelEx($model, 'type'); ?>
		<?php echo $form->dropDownList($model, 'type', $types,
					array(
						'onchange' => 'changeType(this.value)',
						'class' => 'width150',
						'id' => 'type',
					)
		); ?>
		<?php echo $form->error($model, 'type'); ?>
	</div>

	<div class="rowold">
		<?php echo $form->labelEx($model, 'position'); ?>
		<?php echo CCHtml::radioButtonList("{$this->modelName}[position]", $model->position, $positions,
				array(
					'separator' => '',
					'template' => '<div class="advert-postiion">{input} {label}<div><img src='.Yii::app()->theme->baseUrl.'/images/advertpos/small{imageposition}.jpg></div></div>',
					'labelOptions' => array('class' => 'noblock'),
				)
		); ?>
		<?php echo $form->error($model, 'position'); ?>
	</div>

	<?php
		if (!$model->isNewRecord)
			$model->areas = $model->getAreas();
	?>

	<div class="clear"></div>

	<br />
	<div class="rowold">
		<?php echo $form->labelEx($model, 'areas'); ?>
		<?php echo tt('(press and hold SHIFT button for multiply select)'); ?><br />
		<?php
			echo $form->listBox($model, 'areas', $areas, array('class'=>'width300', 'size' => 20, 'multiple'=>'multiple'));
		?>
		<?php echo $form->error($model, 'areas'); ?>
		<div class="padding-bottom10">
			<span class="label label-info">
				<?php echo '*** '. tt('Apart from the pages that open in a modal window (e.g. in fancybox and others)');?>
			</span>
		</div>
		<br />
	</div>

	<div class="clear"></div>

	<div class="hidden rowold" id="file">
		<?php echo $form->labelEx($model,'file_path'); ?>
		<div class="advert-support-file">
			<?php echo $form->fileField($model, 'file_path'); ?>
			<?php echo $form->error($model, 'file_path'); ?>
		</div>
		<?php echo '<div><span class="label label-info">'.Yii::t('module_advertising', 'Supported file: {supportExt}.', array('{supportExt}' => $model->supportExt)).'</span></div>';?>
		<br />

		<div class="rowold">
			<?php echo $form->labelEx($model,'url'); ?>
			<?php echo $form->textField($model,'url', array('class' => 'width300')); ?>
			<?php echo $form->error($model,'url'); ?>
		</div>

		<div class="rowold">
			<?php echo $form->labelEx($model,'alt_text'); ?>
			<?php echo $form->textField($model,'alt_text', array('class' => 'width300')); ?>
			<?php echo $form->error($model,'alt_text'); ?>
		</div>
	</div>

	<div class="hidden" id="html">
		<div class="rowold">
			<?php
			$this->widget('application.modules.lang.components.langFieldWidget', array(
					'model' => $model,
					'field' => 'html',
					'type' => 'text-editor',
				));
			?>
		</div>
	</div>

	<div class="hidden" id="js">
		<div class="rowold">
			<?php
				$this->widget('application.modules.lang.components.langFieldWidget', array(
					'model' => $model,
					'field' => 'js',
					'type' => 'text',
					'useTranslate' => false,
				));
			?>
		</div>
	</div>

	<div class="rowold">
		<?php echo $form->checkBox($model, 'active'); ?>
		<?php echo $form->labelEx($model,'active', array('class' => 'noblock')); ?>
		<?php echo $form->error($model,'active'); ?>
	</div>

	<div class="clear">&nbsp;</div>
	<div id="submit" class="rowold buttons">
		<?php $this->widget('bootstrap.widgets.TbButton',
			array('buttonType'=>'submit',
				'type'=>'primary',
				'icon'=>'ok white',
				'label'=> $model->isNewRecord ? tc('Add') : tc('Save'),
				'htmlOptions' => array(
					'class' => 'submit-button',
				),
			));
		?>
	</div>

	<?php $this->endWidget(); ?>
</div><!-- form -->

<?php
	Yii::app()->clientScript->registerScript('changeType', '
		function changeType(value) {
			var typesJsSize = typesJs.length;

			var showSubmit = 0;
			for (i=0; i<typesJsSize; i++) {
				var name = typesJs[i];
				if (typesJs[i] == value) {
					$("div#"+name).removeClass("hidden").show();
					showSubmit = 1;
				} else {
					$("div#"+name).addClass("hidden").hide();
				}
			}
			$("#submit").hide();
			if (showSubmit) {
				$("#submit").show();
			}
		}
	', CClientScript::POS_END);

	Yii::app()->clientScript->registerScript('readyChangeType', '
		changeType($("#type").val());
	', CClientScript::POS_READY);
?>