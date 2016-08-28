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

class SliderWidget extends CWidget {
	public $imgCount = 0;
	public $images;

	public function getViewPath($checkTheme=true){
		if($checkTheme && ($theme=Yii::app()->getTheme())!==null){
			if (is_dir($theme->getViewPath().DIRECTORY_SEPARATOR.'modules'.DIRECTORY_SEPARATOR.'slider'.DIRECTORY_SEPARATOR.'views'))
				return $theme->getViewPath().DIRECTORY_SEPARATOR.'modules'.DIRECTORY_SEPARATOR.'slider'.DIRECTORY_SEPARATOR.'views';
		}
		return Yii::getPathOfAlias('application.modules.slider.views');
	}

	public function run() {
		if (!$this->images) {
			$slider = new Slider;
			$this->images = $slider->getActiveImages();
		}

		$this->render('widgetSlider_list', array(
			'images' => $this->images,
			'imgCount' => $this->imgCount
		));
	}
}