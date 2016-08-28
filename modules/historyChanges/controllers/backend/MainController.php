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
	public $modelName = 'HistoryChanges';
	
	public function init() {
		parent::init();

		if (!issetModule('historyChanges')) {
			throw404();
		}
	}

	public function accessRules(){
		return array(
			array('allow',
				'expression'=> "Yii::app()->user->checkAccess('historyChanges_admin')",
			),
			array('deny',
				'users'=>array('*'),
			),
		);
	}
	
	public function actionAdmin(){
		$model = new HistoryChanges('search');
		$model->setRememberScenario('historyChanges_remember');
		$this->rememberPage();

		$this->render('admin',array(
			'model'=>$model,
		));
	}
}