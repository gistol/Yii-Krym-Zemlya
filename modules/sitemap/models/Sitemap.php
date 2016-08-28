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

class Sitemap extends CActiveRecord {
	public static function model($className=__CLASS__) {
		return parent::model($className);
	}

	public function tableName() {
		return '{{apartment}}';
	}

	public static function publishAssets() {
		$assetsPath = Yii::getPathOfAlias('webroot.themes.'.Yii::app()->theme->name . '.views.modules.sitemap.assets');
		if (is_dir($assetsPath)) {
			$baseUrl = Yii::app()->assetManager->publish($assetsPath);
			Yii::app()->clientScript->registerCssFile($baseUrl . '/module_sitemap.css');
		}
	}
}