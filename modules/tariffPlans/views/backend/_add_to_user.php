<div id="add_tariff_plan_form">
	<?php
	$this->renderPartial('_add_to_form', array(
		'id' => $id,
		'model' => $model,
		'withDate' => $withDate,
		'tariffsArray' => $tariffsArray
	));
	?>
</div>

<script type="text/javascript">
	var addToUser = {

		apply: function(){
			$.ajax({
				url: '<?php echo Yii::app()->createUrl('/tariffPlans/backend/main/addPaid'); ?>',
				type: 'post',
				dataType: 'json',
				data: $('#addToUser-form').serialize(),
				success: function(data){
					if(data.status == 'ok'){
						message('<?php echo tc('Tariff plan successfully added'); ?>');
						$('#paid_row_el_'+data.userId).replaceWith(data.html);
						tempModal.close();
						tempModal.init();
					}else{
						$('#add_tariff_plan_form').html(data.html);
						addToUser.setDatepicker();
					}
				},
				error: function(){
					error('<?php echo tc('Error. Repeat attempt later'); ?>');
				}
			});
		},

		setDatepicker: function(){
			$('#AddToUserForm_date_end').datepicker(jQuery.extend(jQuery.datepicker.regional['<?php echo Yii::app()->controller->datePickerLang; ?>'],{
					showAnim: 'fold',
					dateFormat: 'yy-mm-dd',
					minDate: new Date(),
					changeYear: true
				}
			));
		}
	}


</script>

<?php if (Yii::app()->request->isAjaxRequest):?>
	<script>
		$(function(){
			addToUser.setDatepicker();
		});
	</script>
<?php endif;?>