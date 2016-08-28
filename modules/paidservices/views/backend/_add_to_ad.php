
<div id="add_paid_form">
<?php
$this->renderPartial('_add_to_form', array(
	'id' => $id,
	'model' => $model,
	'withDate' => $withDate,
	'paidServicesArray' => $paidServicesArray
));
?>
</div>

<script type="text/javascript">
	var addTo = {

		apply: function(){
			$.ajax({
				url: '<?php echo Yii::app()->createUrl('/paidservices/backend/main/addPaid'); ?>',
				type: 'post',
				dataType: 'json',
				data: $('#addToAd-form').serialize(),
				success: function(data){
					if(data.status == 'ok'){
						message('<?php echo tc('Paid service successfully added'); ?>');
						$('#paid_row_el_'+data.apartmentId).replaceWith(data.html);
						tempModal.close();
						tempModal.init();
					}else{
						$('#add_paid_form').html(data.html);
						addTo.setDatepicker();
					}
				},
				error: function(){
					error('<?php echo tc('Error. Repeat attempt later'); ?>');
				}
			});
		},

		setDatepicker: function(){
			$('#AddToAdForm_date_end').datepicker(jQuery.extend(jQuery.datepicker.regional['<?php echo Yii::app()->controller->datePickerLang; ?>'],{
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
			addTo.setDatepicker();
		});
	</script>
<?php endif;?>