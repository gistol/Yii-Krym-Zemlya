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

class MainController extends ModuleAdminController{
	public $modelName = 'MetroStations';
	
	public function init() {
		parent::init();

		if (!issetModule('metroStations')) {
			throw404();
		}
	}

	public function accessRules(){
		return array(
			array('allow',
				'expression'=> "Yii::app()->user->checkAccess('metro_stations_admin')",
			),
			array('deny',
				'users'=>array('*'),
			),
		);
	}

	public function actionAdmin(){
		$model = new MetroStations('search');
		$model->setRememberScenario('metroStations_remember');
		$this->rememberPage();

		$this->render('admin',array(
			'model'=>$model,
		));
	}

	public function actionCreate() {
		$model = new $this->modelName;		
		$this->performAjaxValidation($model);

		if(isset($_POST[$this->modelName])){
			$model->attributes=$_POST[$this->modelName];
			if($model->save()){
				$this->redirect(array('admin'));
			}
		}

		$this->render('create', array('model'=>$model));
	}
	

	public function actionUpdate($id){
		$model = $this->loadModel($id);
		
		if (issetModule('location') && $model->loc_city) {
			$locCityInfo = City::model()->findByPk($model->loc_city);
			
			$model->country = $locCityInfo->country_id;
			$model->region = $locCityInfo->region_id;
		}
		
		$this->performAjaxValidation($model);

		if(isset($_POST[$this->modelName])){
			$model->attributes=$_POST[$this->modelName];
			if($model->validate()){
				if($model->save(false)){
					$this->redirect(array('admin'));
				}
			}
		}

		$this->render('update', array('model'=>$model));
	}
	
	public function actionMove(){
		if(isset($_GET['id']) && isset($_GET['direction'])) {
			$attributeName = (issetModule('location')) ? 'loc_city' : 'city_id';

			$attributeVal = (int) Yii::app()->request->getQuery($attributeName, '');
			$direction = isset($_GET['direction']) ? $_GET['direction'] : '' ;
			$model = $this->loadModel($_GET['id']);
					
			if (!empty($attributeVal) && $attributeVal > 0) {
				$addWhere = ' AND '.$attributeName.' = "'.$attributeVal.'"';
				
				if($model && ($direction == 'up' || $direction == 'down' || $direction == 'fast_up' || $direction == 'fast_down') ){
					$sorter = $model->sorter;

					if($direction == 'up' || $direction == 'fast_up'){
						if($sorter > 1){
							if($direction == 'up') {
								$sql = 'UPDATE '.$model->tableName().' SET sorter="'.$sorter.'" WHERE sorter < "'.($sorter).'" '.$addWhere.' ORDER BY sorter DESC LIMIT 1';
								Yii::app()->db->createCommand($sql)->execute();
								$model->sorter--;
							} 
							else {
								$sql = 'UPDATE '.$model->tableName().' SET sorter=sorter+1 WHERE sorter < "'.($sorter).'" '.$addWhere;
								Yii::app()->db->createCommand($sql)->execute();
								$model->sorter=1;
							}

							$model->save(false);
						}
					}

					if($direction == 'down' || $direction == 'fast_down'){
						$maxSorter = Yii::app()->db->createCommand()
							->select('MAX(sorter) as maxSorter')
							->from($model->tableName())
							->where($attributeName.'=:cityid', array(':cityid'=>$attributeVal))
							->queryScalar();

						if($sorter < $maxSorter){
							if ($direction == 'down') {
								$sql = 'UPDATE '.$model->tableName().' SET sorter="'.$sorter.'" WHERE sorter > "'.($sorter).'" '.$addWhere.' ORDER BY sorter ASC LIMIT 1';
								Yii::app()->db->createCommand($sql)->execute();
								$model->sorter++;
							} else {
								$sql = 'UPDATE '.$model->tableName().' SET sorter=sorter-1 WHERE sorter > "'.($sorter).'" '.$addWhere;
								Yii::app()->db->createCommand($sql)->execute();
								$model->sorter=$maxSorter;
							}

							$model->save(false);
						}
					}
				}
			}
		}
		if(!Yii::app()->request->isAjaxRequest){
			$this->redirect(isset($_POST['returnUrl']) ? $_POST['returnUrl'] : array('admin'));
		}
	}
}