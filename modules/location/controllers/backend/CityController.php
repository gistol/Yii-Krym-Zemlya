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

class CityController extends ModuleAdminController{
	public $modelName = 'City';

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
		Yii::app()->user->setState('menu_active', 'location.city');
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

	public function actionCreate(){
		$model=new $this->modelName;

		$region_id = Yii::app()->request->getParam('region_id');
		$country_id = Yii::app()->request->getParam('country_id');
		if($region_id && $country_id) {
			$model->region_id = $region_id;
			$model->country_id = $country_id;
		}

		$this->performAjaxValidation($model);

		if(isset($_POST[$this->modelName])){
			$model->attributes=$_POST[$this->modelName];
			if($model->save()){
				Yii::app()->user->setFlash('success', tt('The new city is successfully created.'));
				if(isset($_POST['addMore']) && $_POST['addMore'] == 1)
					$this->redirect(array('create','region_id'=>$model->region_id, 'country_id'=>$model->country_id));
				$this->redirect('admin');
			}
		}

		$this->render('create', array('model'=>$model));
	}

	public function actionUpdate($id){
		if($this->_model === null){
			$model = $this->loadModel($id);
		}
		else{
			$model = $this->_model;
		}

		$old_model = clone $model;

		$this->performAjaxValidation($model);

		if(isset($_POST[$this->modelName])){
			$model->attributes=$_POST[$this->modelName];
			if($model->validate()){
				if($model->save(false)){

					if ($old_model->country_id != $model->country_id || $old_model->region_id != $model->region_id) {
						$sql = 'UPDATE {{apartment}} SET loc_region=:region, loc_country=:country WHERE loc_city=:city';
						Yii::app()->db->createCommand($sql)->execute(array(':country' => $model->country_id, ':region' => $model->region_id, ':city' => $model->id));
					}

					if (!empty($this->redirectTo))
						$this->redirect($this->redirectTo);
					else
						$this->redirect(array('view','id'=>$model->id));
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

	public function actionAdmin(){

		$this->rememberPage();

		$model = new City('search');

		$model->setRememberScenario('city_remember');

		$this->render('admin',
			array_merge(array('model'=>$model), $this->params)
		);
	}
}
