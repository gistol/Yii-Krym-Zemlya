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


class MainController extends BaseMessagesController {
	public $modelName = 'Messages';
	public $defaultAction = 'admin';
	public $layout='//layouts/admin';

	function init(){

		Yii::app()->bootstrap;
		Yii::app()->params['useBootstrap'] = true;

		parent::init();
		$this->menuTitle = Yii::t('common', 'Operations');
	}

	public function getViewPath($checkTheme=false){
		if($checkTheme && ($theme=Yii::app()->getTheme())!==null){
			if (is_dir($theme->getViewPath().DIRECTORY_SEPARATOR.'modules'.DIRECTORY_SEPARATOR.$this->getModule($this->id)->getName().DIRECTORY_SEPARATOR.'views'.DIRECTORY_SEPARATOR.'backend'))
				return $theme->getViewPath().DIRECTORY_SEPARATOR.'modules'.DIRECTORY_SEPARATOR.$this->getModule($this->id)->getName().DIRECTORY_SEPARATOR.'views'.DIRECTORY_SEPARATOR.'backend';
		}
		return Yii::getPathOfAlias('application.modules.'.$this->getModule($this->id)->getName().'.views.backend');
	}

	public function actionIndex(){
		$this->redirect(array('admin'));
	}

	public function actionAdmin() {
		$allUsers = $pages = null;
		$itemsProvider = new CArrayDataProvider(array());

		$return = Messages::getAllContactUsers(Yii::app()->user->id);

		if ($return) {
			$allUsers = $return['allUsers'];
			$pages = $return['pages'];

			if (count($allUsers)) {
				$itemsProvider = new CArrayDataProvider(
					$allUsers,
					array(
						'pagination' => array(
							'pageSize' => param('adminPaginationPageSize', 20),
						),
					)
				);
			}
		}

		$this->render('admin', array('allUsers' => $allUsers, 'pages' => $pages, 'itemsProvider' => $itemsProvider));
	}

	public function actionRead() {
		$id = Yii::app()->request->getParam('id');
		$apId = (int) Yii::app()->request->getParam('apId');

		if (!$id)
			throw404();

		$user = User::model()->findByPk($id);

		Yii::app()->user->setState('menu_active', 'messages.read');

		$model = new $this->modelName;

		// выставляем флаг о прочитанности
		$unRealMessages = Messages::model()->unReadUser()->criteriaUser($id, Yii::app()->user->id)->findAll(array('select' => 'id'));

		$idArr = array();
		foreach ($unRealMessages as $item) {
			$idArr[] = (int) $item->id;
		}

		if (count($idArr) > 0)
			Messages::model()->updateByPk($idArr, array('is_read' => Messages::STATUS_READ_USER, 'date_read' => new CDbExpression('NOW()')));

		$allMessages = $pages = null;

		$return = Messages::getAllMessagesUser($id);
		if ($return) {
			$allMessages = $return['allMessages'];
			$pages = $return['pages'];
		}

		# если нет сообщений от выбранного пользователя
		/*if (!$allMessages)
			throw404();*/

		$this->render('read', array(
				'allMessages' => $allMessages,
				'pages' => $pages,
				'senderInfo' => $user,
				'model' => $model,
				'uid' => $id,
				'apId' => $apId,
			)
		);
	}
}
