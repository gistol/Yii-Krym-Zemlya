<?php
/* * ********************************************************************************************
 *								Open Real Estate
 *								----------------
 * 	version				:	V1.17.1
 * 	copyright			:	(c) 2015 Monoray
 * 							http://monoray.net
 *							http://monoray.ru
 *
 * 	website				:	http://open-real-estate.info/en
 *
 * 	contact us			:	http://open-real-estate.info/en/contact-us
 *
 * 	license:			:	http://open-real-estate.info/en/license
 * 							http://open-real-estate.info/ru/license
 *
 * This file is part of Open Real Estate
 *
 * ********************************************************************************************* */

class SeoWidget extends CWidget{
	public $showBodyTextField = false;
	public $model;
	public $prefixUrl;
    public $canUseDirectUrl = false;

	public function getViewPath($checkTheme=true){
		if($checkTheme && ($theme=Yii::app()->getTheme())!==null){
			if (is_dir($theme->getViewPath().DIRECTORY_SEPARATOR.'modules'.DIRECTORY_SEPARATOR.'seo'.DIRECTORY_SEPARATOR.'views'))
				return $theme->getViewPath().DIRECTORY_SEPARATOR.'modules'.DIRECTORY_SEPARATOR.'seo'.DIRECTORY_SEPARATOR.'views';
		}
		return Yii::getPathOfAlias('application.modules.seo.views');
	}

	public function run(){
		if(!param('genFirendlyUrl')){
			return '';
		}

		if(!$this->model){
			return NULL;
		}

		$friendlyUrl = SeoFriendlyUrl::getAndCreateForModel($this->model);

		$this->render('seoWidget', array(
			'model' => $this->model,
			'friendlyUrl' => $friendlyUrl,
			'showBodyTextField' => $this->showBodyTextField,
		));
	}
}
