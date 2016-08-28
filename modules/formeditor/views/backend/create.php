
<div class="flash-notice"><?php echo tt('Add field help', 'formeditor');?></div>

<?php
$this->adminTitle = tt('Add field', 'formeditor');

$this->renderPartial('_form', array(
    'model' => $model,
    'translate' => $translate,
));
?>

