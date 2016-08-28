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
	public $modelName = 'MetroStations';

	public function init() {
		parent::init();

		if (!issetModule('metroStations')) {
			throw404();
		}
	}
	
	public function actionGetMetroStations() {
		$city = Yii::app()->request->getQuery('city', 0);
		$type = Yii::app()->request->getQuery('type', 0);
		$onlyWithAds = Yii::app()->request->getQuery('onlyWithAds', 0);
				
		$res = MetroStations::getMetrosArray($city, 0, 0, $onlyWithAds);
		if ($res) {
			switch ($type) {
				case 1:
					$metros = CArray::merge(array(0 => ''), $res);
					break;
				case 2:
					$metros = CArray::merge(array(0 => tt('Select metro stations', 'metroStations')), $res);
					break;
				case 3:
					$metros = CArray::merge(array(0 =>  tt('Not selected', 'metroStations')), $res);
					break;
				default :
					$metros = $res;
			}

			$dropdownMetro = '';
			foreach($metros as $value=>$name) {
				$dropdownMetro .= CHtml::tag('option', array('value'=>$value),CHtml::encode($name),true);
			}

			echo CJSON::encode(array(  
				'dropdownMetro'=>$dropdownMetro  
			));  
			Yii::app()->end();
		}
		
		echo CJSON::encode(array(  
			'no'=>true  
		));  
		Yii::app()->end(); 
	}
}