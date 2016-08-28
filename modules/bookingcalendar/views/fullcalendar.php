<div id="calendar-booking-property">
	<h2><?php echo tc('Сalendar for booking a property');?></h2>
	
	<hr />
	
	<div id='script-warning-full-calendar'></div>
	<div id='loading-full-calendar'><?php echo tc('Loading ...');?></div>
	
	<div class="clear"></div>
	<div class="calendarDescription">
		<div class="calendarDescriptionReserved"></div>
		<div class="calendarDescriptionText"> - <?php echo tt('Reserved', 'bookingcalendar');?></div>
	</div>
	<div class="clear"></div>
	
	<div id='calendar'></div>
	
	<script>
		$(document).ready(function() {
			$('#calendar').fullCalendar({
				header: {
					left: 'prev,next today',
					center: 'title',
					right: 'prev,next today'
				},
				defaultDate: new Date(),
				lang: '<?php echo mb_strtolower(Yii::app()->controller->datePickerLang);?>',
				businessHours: false,
				editable: false,
				eventLimit: 4,
				contentHeight: 700,
				events: {
					url: '<?php echo Yii::app()->controller->createAbsoluteUrl('/bookingcalendar/main/getJsonDataFullCalendar');?>',
					error: function() {
						$('#script-warning-full-calendar').show();
					}
				},
				loading: function(bool) {
					$('#loading-full-calendar').toggle(bool);
				}
			});
		});
	</script>
</div>