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

class FullCalendarWidget extends CWidget {
	public function getViewPath($checkTheme=true){
		return Yii::getPathOfAlias('application.modules.bookingcalendar.views');
	}
	
	public static function publishAssetsFullCalendar() {
		$assetsPath = Yii::getPathOfAlias('application.modules.bookingcalendar.extensions.fullcalendar');
		if (is_dir($assetsPath)) {
			$baseUrl = Yii::app()->assetManager->publish($assetsPath);
						
			Yii::app()->clientScript->registerCssFile($baseUrl . '/fullcalendar.css');
			Yii::app()->clientScript->registerCssFile($baseUrl . '/fullcalendar.print.css', 'print');
			
			Yii::app()->clientScript->registerScriptFile($baseUrl . '/lib/moment.min.js');
			Yii::app()->clientScript->registerScriptFile($baseUrl . '/fullcalendar.min.js');
			Yii::app()->clientScript->registerScriptFile($baseUrl . '/lang-all.js');
		}
	}
	
	public function run() {
		FullCalendarWidget::publishAssetsFullCalendar();
		
		$this->render('fullcalendar', array());
	}
}