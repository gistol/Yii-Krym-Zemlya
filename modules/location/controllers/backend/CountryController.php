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

class CountryController extends ModuleAdminController{
	public $modelName = 'Country';

	public function accessRules(){
		return array(
			array('allow',
				'expression'=> "Yii::app()->user->checkAccess('all_reference_admin')",
			),
			array('deny',
				'users'=>array('*'),
			),
		);
	}

	public function init() {
		parent::init();
		Yii::app()->user->setState('menu_active', 'location.country');
	}

	public function getViewPath($checkTheme=true){
		if($checkTheme && ($theme=Yii::app()->getTheme())!==null){
			if (is_dir($theme->getViewPath().DIRECTORY_SEPARATOR.'modules'.DIRECTORY_SEPARATOR.$this->getModule($this->id)->getName().DIRECTORY_SEPARATOR.'views'.DIRECTORY_SEPARATOR.Yii::app()->controller->id))
				return $theme->getViewPath().DIRECTORY_SEPARATOR.'modules'.DIRECTORY_SEPARATOR.$this->getModule($this->id)->getName().DIRECTORY_SEPARATOR.'views'.DIRECTORY_SEPARATOR.Yii::app()->controller->id;
		}
		return Yii::getPathOfAlias('application.modules.'.$this->getModule($this->id)->getName().'.views.'.Yii::app()->controller->id);
	}

	public function actionView($id){
		$this->redirect(array('admin'));
	}
	public function actionIndex(){
		$this->redirect(array('admin'));
	}

	public function actionAdmin(){
		$this->getMaxSorter();
		$this->getMinSorter();

		$this->rememberPage();

		$model = new Country('search');

		$model->setRememberScenario('country_remember');

		$this->render('admin',
			array_merge(array('model'=>$model), $this->params)
		);
	}

	public function actionSetSorters () {
		$sql = 'SELECT id, name_'.Yii::app()->language.' AS name FROM {{location_country}} ORDER BY name_'.Yii::app()->language.' ASC';
		$res = Yii::app()->db->createCommand($sql)->queryAll();

		$countries = CHtml::listData($res, 'id', 'name');

		$co=1;
		foreach ($countries as $coid=>$coname) {
			$sql = 'UPDATE {{location_country}} SET sorter="'.$co.'" WHERE id = '.($coid).' LIMIT 1';
			Yii::app()->db->createCommand($sql)->execute();


			$sql = 'SELECT id, name_'.Yii::app()->language.' AS name FROM {{location_region}} WHERE country_id = :country ORDER BY name_'.Yii::app()->language.' ASC';
			$res = Yii::app()->db->createCommand($sql)->queryAll(true, array(':country' => $coid));
			$regions =  CHtml::listData($res, 'id', 'name');

			$r=1;
			foreach ($regions as $rid=>$rname) {
				$sql = 'UPDATE {{location_region}} SET sorter="'.$r.'" WHERE id = '.($rid).' LIMIT 1';
				Yii::app()->db->createCommand($sql)->execute();

				$sql = 'SELECT id, name_'.Yii::app()->language.' AS name FROM {{location_city}} WHERE region_id = :region ORDER BY name_'.Yii::app()->language.' ASC';
				$res = Yii::app()->db->createCommand($sql)->queryAll(true, array(':region' => $rid));
				$cities = CHtml::listData($res, 'id', 'name');

				$c=1;
				foreach ($cities as $cid=>$cname) {
					$sql = 'UPDATE {{location_city}} SET sorter="'.$c.'" WHERE id = '.($cid).' LIMIT 1';
					Yii::app()->db->createCommand($sql)->execute();

					$c++;
				}

				$r++;
			}


			$co++;
		}
		echo 'ok';
	}


}
