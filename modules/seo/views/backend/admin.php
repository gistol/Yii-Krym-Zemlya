<?php
    $this->pageTitle=Yii::app()->name . ' - ' . tt('Manage SEO settings');
    $this->adminTitle = tt('Manage SEO settings');

$this->widget(
    'bootstrap.widgets.TbTabs',
    array(
        'type' => 'tabs', // 'tabs' or 'pills'
        'tabs' => array(
            array(
                'label' => tc('Friendly URL and SEO settings'),
                'content' => $this->renderPartial('_admin_url', array('model' => $model), true),
                'active' => $validGen == true && $valid == true,
            ),
			array(
                'label' => tc('Image SEO: alt tag'),
                'content' => $this->renderPartial('_admin_alt_images', array('model' => $altImages), true),
                'active' => $validGen == false,
            ),
            array(
                'label' => tt('Settings'),
                'content' => $this->renderPartial('_admin_settings', array('model' => $settings, 'settingsForm' => $settingsForm), true),
                'active' => $validGen == true && $valid == false,
            ),
            array(
                'label' => tt('Generation SEO'),
                'content' => $this->renderPartial('_admin_gen', array('model' => $gen), true),
                'active' => $validGen == false,
            ),
        ),
    )
);
?>




