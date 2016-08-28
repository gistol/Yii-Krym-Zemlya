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

class MainController extends CController {

	/*public function init(){
		if (!Yii::app()->request->isAjaxRequest)
			return false;

		setLang();
		parent::init();
	}*/

	public function actionBannerActivate() {
		if (!Yii::app()->request->isAjaxRequest)
			return false;

		$id = (int) Yii::app()->request->getParam('id');

		if ($id) {
			Advert::model()->updateCounters(
				array('clicks'=>1),
				"id = :id",
				array(':id' => $id)
			);
		}
	}
}