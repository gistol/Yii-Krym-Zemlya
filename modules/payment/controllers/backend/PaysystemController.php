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

class PaysystemController extends ModuleAdminController{
	public $modelName = 'Paysystem';

	public function accessRules(){
		return array(
			array('allow',
				'expression'=> "Yii::app()->user->checkAccess('payment_admin')",
			),
			array('deny',
				'users'=>array('*'),
			),
		);
	}

	public function actionAdmin(){
		Yii::app()->user->setState('menu_active', 'payment.paysystem');

		$this->getMaxSorter();
		$this->getMinSorter();

		$model = new $this->modelName('search');
		$model->resetScope();

		if($this->scenario){
			$model->scenario = $this->scenario;
		}

		if($this->with){
			$model = $model->with($this->with);
		}

		$model->unsetAttributes();  // clear any default values
		if(isset($_GET[$this->modelName])){
			$model->attributes=$_GET[$this->modelName];
		}
		$this->render('admin-paysystem',
				array_merge(array('model'=>$model), $this->params)
		);
    }

	public function actionConfigure($id){
		Yii::app()->user->setState('menu_active', 'payment.paysystem');

		$model = $this->loadModel($id);

		if(isset($_POST['Paysystem'])) {
			$model->attributes = $_POST['Paysystem'];

			if(isset($_POST[$model->payModelName])){
				$model->payModel->attributes = $_POST[$model->payModelName];

				if($model->payModel->validate()){
					$payModelValid = true;
				} else {
					$payModelValid = false;
				}
			} else {
				$payModelValid = true;
			}

			if($payModelValid && $model->save()){
				Yii::app()->user->setFlash('success', tt('Payment System Settings saved successfully.'));
				$this->redirect('admin');
			}
		}

		$this->render('settings',array(
			'model'=>$model,
		));
	}

	public function getCurrencyOptions(){
		return array(
			'rur'=>'RUR',
			'usd'=>'USD',
		);
	}

	public function getModeOptions(){
		return array(
			Paysystem::MODE_REAL=>tt('Real mode', 'payment'),
			Paysystem::MODE_TEST=>tt('Test mode', 'payment'),
		);
	}

	public function getStatusOptions(){
		return array(
			Paysystem::STATUS_ACTIVE=>tt('Active', 'payment'),
			Paysystem::STATUS_INACTIVE=>tt('Inactive', 'payment'),
		);
	}
}
