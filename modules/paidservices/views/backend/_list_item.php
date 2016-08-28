<div class="paidServiceRow <?php echo $data->getHtmlClassForAdmin();?>">
	<strong>
		<?php echo $data->name; ?>&nbsp;
		<?php echo $data->active == 0 ? '(' . tc('Inactive') . ')' : ''; ?>&nbsp;&nbsp;
		<?php echo $data->getEditHtml(); ?>
	</strong>

	<?php
	if($data->description){
		echo CHtml::tag('p', array(), $data->description);
	}

	if($data->id == PaidServices::ID_BOOKING_PAY){
		$dataModel = new PaidBooking();
		echo '<p>';
		echo '<ul>';
		echo '<li>'.$dataModel->getAttributeLabel('percent').': ' . $data->getFromJson('percent').' %'.'</li>';
		echo '<li>'.$dataModel->getAttributeLabel('pay_immediately').': ' . ( $data->getFromJson('pay_immediately') ? tc('Yes') : tc('No') ) . '</li>';
		echo '</ul>';
		echo '</p>';
	}

	if(isset($data->options)){
		echo '<ul>';
		foreach($data->options as $option){
			$in = tc('Cost of service'). ' ' . $option->getPriceAndCurrency() . ', ';
			$in .= tc('The service will be active') . ' ' . Yii::t('common', '{n} day', $option->duration_days);
			$in .= '&nbsp;&nbsp;&nbsp;' . $option->getEditHtml();
			echo CHtml::tag('li', array(), $in);
		}
		echo '</ul>';
	} else {
		//echo tt('');
	}
	?>
</div>