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
	public $modelName = 'TariffPlans';

	public function accessRules(){
		return array(
			array('allow',
				'expression'=> "Yii::app()->user->checkAccess('tariff_plans_admin')",
			),
			array('deny',
				'users'=>array('*'),
			),
		);
	}

	public function actionIndex(){
		$this->redirect(array('admin'));
	}

	public function actionView($id){
		$this->redirect(array('admin'));
	}

	public function actionCreate() {
		$model = new $this->modelName;

		$model->show_address = $model->show_phones = true;

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

		if ($model->id == TariffPlans::DEFAULT_TARIFF_PLAN_ID)
			$model->scenario = 'default_tariff_plan_edit';

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

	public function actionAdmin(){
		$this->rememberPage();
		$model = new TariffPlans('search');
		$model->setRememberScenario('tariffPlans_remember');

		$this->render('admin',array(
			'model'=>$model,
		));
	}

	public function actionAddPaid($id = 0, $withDate = 0){
		$model = new AddToUserForm();

		$tariffs = TariffPlans::getAllTariffPlans(true, true);
		$tariffsArray = CHtml::listData($tariffs, 'id', 'name');

		$request = Yii::app()->request;
		$data = $request->getPost('AddToUserForm');

		if($data){
			$userId = $request->getPost('user_id');
			$withDate = $request->getPost('withDate');

			$model->attributes = $data;
			if($model->validate()){
				$user = User::model()->findByPk($userId);
				$tariff = TariffPlans::getFullTariffInfoById($model->tariff_id);

				if(!$tariff || !$user){
					throw new CException('Not valid data');
				}

				if(TariffPlans::applyToUser($userId, $tariff['id'], $model->date_end, null, true)) {
					echo CJSON::encode(array(
						'status' => 'ok',
						'userId' => $userId,
						'html' => TariffPlans::getTariffPlansHtml($withDate, true, $user)
					));
					Yii::app()->end();
				}
			}
			else {
				echo CJSON::encode(array(
					'status' => 'err',
					'html' => $this->renderPartial('_add_to_user', array(
							'id' => $userId,
							'model' => $model,
							'withDate' => $withDate,
							'tariffsArray' => $tariffsArray
						), true)
				));
				Yii::app()->end();
			}
		}

		$renderData = array(
			'id' => $id,
			'model' => $model,
			'withDate' => $withDate,
			'tariffsArray' => $tariffsArray
		);

		if(Yii::app()->request->isAjaxRequest) {
			$this->renderPartial('_add_to_user', $renderData);
		}
		else {
			$this->render('_add_to_user', $renderData);
		}
	}
}