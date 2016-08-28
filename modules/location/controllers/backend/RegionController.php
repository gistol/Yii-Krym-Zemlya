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

class RegionController extends ModuleAdminController{
	public $modelName = 'Region';

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
		Yii::app()->user->setState('menu_active', 'location.region');
	}

	public function getViewPath($checkTheme=true){
		if($checkTheme && ($theme=Yii::app()->getTheme())!==null){
			if (is_dir($theme->getViewPath().DIRECTORY_SEPARATOR.'modules'.DIRECTORY_SEPARATOR.$this->getModule($this->id)->getName().DIRECTORY_SEPARATOR.'views'.DIRECTORY_SEPARATOR.Yii::app()->controller->id))
				return $theme->getViewPath().DIRECTORY_SEPARATOR.'modules'.DIRECTORY_SEPARATOR.$this->getModule($this->id)->getName().DIRECTORY_SEPARATOR.'views'.DIRECTORY_SEPARATOR.Yii::app()->controller->id;
		}
		return Yii::getPathOfAlias('application.modules.'.$this->getModule($this->id)->getName().'.views.'.Yii::app()->controller->id);
	}

	public function actionAdmin(){

		$this->rememberPage();

		$model = new Region('search');

		$model->setRememberScenario('region_remember');

		$this->render('admin',
			array_merge(array('model'=>$model), $this->params)
		);
	}

	public function actionCreate(){
		$model=new $this->modelName;

		$this->performAjaxValidation($model);

		if(isset($_POST[$this->modelName])){
			$model->attributes=$_POST[$this->modelName];
			if($model->save()){
				if(isset($_POST['addValues']) && $_POST['addValues'] == 1){
					Yii::app()->user->setFlash('success', tt('The new region is successfully created.').' '.tt('Please add cities to the region now.'));
					$this->redirect(array('/location/backend/city/create','region_id'=>$model->id, 'country_id'=>$model->country_id));
				} else {
					Yii::app()->user->setFlash('success', tt('The new region is successfully created.'));
					$this->redirect(array('admin'));
				}
			}
		}

		$this->render('create',	array('model'=>$model));
	}

	public function actionUpdate($id){
		$model = $this->loadModel($id);

		$this->performAjaxValidation($model);
		
		$oldCountryId = $model->country_id;

		if(isset($_POST[$this->modelName])){
			$model->attributes=$_POST[$this->modelName];
			if($model->validate()){
				if($model->save(false)){
					if ($oldCountryId != $model->country_id) {
						$sql = 'UPDATE {{location_city}} SET country_id = '.$model->country_id.' WHERE country_id = '.$oldCountryId.' AND region_id = '.$model->id;
						Yii::app()->db->createCommand($sql)->execute();
					}
					
					if(isset($_POST['addValues'])){
						Yii::app()->user->setFlash('success', tc('Success').'. '.tt('Please add cities to the region now.'));
						$this->redirect(array('/location/backend/city/create','region_id'=>$model->id, 'country_id'=>$model->country_id));
					} else {
						Yii::app()->user->setFlash('Success', tc('Success'));
						$this->redirect(array('admin'));
					}
				}
			}
		}

		$this->render('update',
			array_merge(
				array('model'=>$model),
				$this->params
			)
		);
	}
}