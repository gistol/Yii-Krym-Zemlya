<br>
<?php echo $form->textFieldRow($dataModel, 'percent'); ?>
<div class="flash-notice"><?php echo tt('Note for payment immediately') ?></div>
<?php
echo $form->checkBoxRow($dataModel, 'pay_immediately');
echo $form->dropDownListRow($dataModel, 'empty_flag', PaidBooking::getEmptyFlagDays(), array('class' => 'span5'));
echo $form->checkBoxRow($dataModel, 'consider_num_guest');
?>
<div class="flash-notice"><?php echo tt('Once the option is on, the fee is calculating by multiplying the number of guests by the calculated booking fee') ?></div>
<?php
echo $form->textFieldRow($dataModel, 'discount_guest');
?>

