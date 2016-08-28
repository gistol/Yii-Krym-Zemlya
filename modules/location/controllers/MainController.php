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

class MainController extends ModuleUserController{
	public function actionGetRegions() {
		$country = Yii::app()->request->getQuery('country', 0);
		$type = Yii::app()->request->getQuery('type', 0);
		$all = Yii::app()->request->getQuery('all', 0);
		$onlyWithAds = Yii::app()->request->getQuery('onlyWithAds', 0);

		$regions=Region::getRegionsArray($country, $type, $all, $onlyWithAds);
		
		foreach($regions as $value=>$name) {
			echo CHtml::tag('option', array('value'=>$value),CHtml::encode($name),true);
		}
	}
	
	public function actionGetCities() {
		$region = Yii::app()->request->getQuery('region', 0);
		$type = Yii::app()->request->getQuery('type', 0);
		$onlyWithAds = Yii::app()->request->getQuery('onlyWithAds', 0);

		$cities = City::getCitiesArray($region, $type, 0, $onlyWithAds);
		
		foreach($cities as $value=>$name) {
			echo CHtml::tag('option', array('value'=>$value),CHtml::encode($name),true);
		}
	}
}