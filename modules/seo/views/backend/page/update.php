<?php
    $this->menu=array(
        array('label'=> tt('Manage SEO settings'), 'url'=>array('/seo/backend/main/admin')),
        array('label'=> tt('Add compliance'), 'url'=>array('/seo/backend/page/create')),
    );
    $this->adminTitle = tt('Edit compliance');
?>

<?php echo $this->renderPartial('_form', array('model'=>$model)); ?>