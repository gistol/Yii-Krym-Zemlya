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
	public $modelName = 'TariffPlans';
	public $showSearchForm = false;

	public function init() {
		parent::init();

		if (!issetModule('tariffPlans') || !issetModule('paidservices')) {
			throw404();
		}
	}

	public function accessRules(){
		return array(
			array(
				'allow',
				'expression' => 'issetModule("tariffPlans") && issetModule("paidservices") && !Yii::app()->user->isGuest',
			),
			array(
				'deny',
				'users'=>array('*'),
			),
		);
	}

	public function actionIndex() {
		// если админ - делаем редирект на просмотр в админку
		if(Yii::app()->user->checkAccess('backend_access')){
			$this->redirect($this->createAbsoluteUrl('/apartments/backend/main/admin'));
		}
		
		$this->layout='//layouts/usercpanel';
		$this->setActiveMenu('tariff_plans');

		$tariffPlanModel = new TariffPlans();

		//$tariffPlans = TariffPlans::getAllTariffPlans(true, true);
		$tariffPlans = TariffPlans::getAllTariffPlans(true, true, true); // только активные тарифы, без тарифа по-умолчанию и только со стоимостью больше 0.

		$tariffsArray = CHtml::listData($tariffPlans, 'id', 'name');

		/*if (!count($tariffsArray))
			throw404();*/
		
		$isFancy = (Yii::app()->request->isAjaxRequest) ? true : false;

		$renderData = array(
			'model' => $tariffPlanModel,
			'tariffsArray' => $tariffsArray,
			'tariffPlans' => $tariffPlans,
			'isFancy' => $isFancy,
		);

		if(Yii::app()->request->isAjaxRequest) {
			$this->renderPartial('index', $renderData);
		}
		else {
			$this->render('index', $renderData);
		}
	}

	public function actionBuyTariffPlan() {
		// если админ - делаем редирект на просмотр в админку
		if(Yii::app()->user->checkAccess('backend_access')){
			$this->redirect($this->createAbsoluteUrl('/apartments/backend/main/admin'));
		}
		
		$this->layout='//layouts/usercpanel';

		$user = HUser::getModel();
		$tariffId = Yii::app()->request->getParam('tariffid');

		if (!$user || !$tariffId)
			throw404();

		$currentTariffModel = TariffPlans::model()->findByPk($tariffId);

		if (!$currentTariffModel || $currentTariffModel->active != TariffPlans::STATUS_ACTIVE)
			throw404();

		// check current user tariff plan
		$currentTariffPlanInfo = TariffPlans::getTariffInfoByUserId($user->id);
		if ($currentTariffPlanInfo['issetTariff'] && $currentTariffPlanInfo['tariffDuration']) {
			if (!$currentTariffPlanInfo['activeTariff']) {
				Yii::app()->user->setFlash('error', Yii::t("module_tariffPlans", "You can only extend the tariff plan {name}", array("{name}" => $currentTariffPlanInfo['tariffName'])));
				$this->redirect(array('choosetariffplans'));
				Yii::app()->end();
			}
		}

		// check balance
		if ($currentTariffModel->price) { # платный тариф
			if ($currentTariffModel->price > $user->balance) {
				Yii::app()->user->setFlash('error', tt('On your balance is not enough money to buy the chosen tariff plan', 'tariffPlans'));
				$this->redirect(array('choosetariffplans'));
				Yii::app()->end();
			}
		}

		// check object count
		if ($currentTariffModel->limit_objects) {
			$usersObjects = TariffPlans::getCountUserObjects($user->id);

			if ($usersObjects > $currentTariffModel->limit_objects) {
				Yii::app()->user->setFlash('error', tt('The number of added ads exceeds the limit of the tariff. Remove its not relevant your ads and try again.', 'tariffPlans'));
				$this->redirect(array('choosetariffplans'));
				Yii::app()->end();
			}
		}

		// apply action
		$interval = 'INTERVAL '.$currentTariffModel->duration.' DAY';
		$dateEnd = new CDbExpression('NOW() + ' . $interval);

		if (TariffPlans::applyToUser($user->id, $tariffId, $dateEnd, $interval)) {
			if ($currentTariffModel->price) { # платный тариф
				$user->deductBalance($currentTariffModel->price);
			}

			Yii::app()->user->setFlash('success', tt('Tariff plan has been successfully applied', 'tariffPlans'));
			$this->redirect(array('tariffhistory'));
		}
		else {
			Yii::app()->user->setFlash('error', tc('Error. Repeat attempt later'));
			$this->redirect(array('choosetariffplans'));
			Yii::app()->end();
		}
	}
}