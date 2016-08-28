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

class SeoImageWidget extends CWidget{
	public $model;
	public $showLink = true;
	public $showForm = true;
	public $showJS = true;
	public $afterRefresh = false;

	public function getViewPath($checkTheme=true){
		if($checkTheme && ($theme=Yii::app()->getTheme())!==null){
			if (is_dir($theme->getViewPath().DIRECTORY_SEPARATOR.'modules'.DIRECTORY_SEPARATOR.'seo'.DIRECTORY_SEPARATOR.'views'))
				return $theme->getViewPath().DIRECTORY_SEPARATOR.'modules'.DIRECTORY_SEPARATOR.'seo'.DIRECTORY_SEPARATOR.'views';
		}
		return Yii::getPathOfAlias('application.modules.seo.views');
	}

	public function run(){
		if(!$this->model){
			return NULL;
		}

		$friendlyUrl = SeoFriendlyUrl::getAndCreateForModel($this->model, false, 'image');
		
		$this->render('seoImageWidget', array(
			'model' => $this->model,
			'friendlyUrl' => $friendlyUrl,
			'showLink' => $this->showLink,
			'showForm' => $this->showForm,
			'showJS' => $this->showJS,
			'afterRefresh' => $this->afterRefresh,
		));
	}
}
