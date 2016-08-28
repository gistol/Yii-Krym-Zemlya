<?php
$this->adminTitle = tt('Update field', 'formeditor');

$this->renderPartial('_form', array(
    'model' => $model,
    'translate' => $translate,
));
?>