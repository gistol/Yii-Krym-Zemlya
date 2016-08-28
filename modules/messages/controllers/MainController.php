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
	public $layout='//layouts/usercpanel';
	public $htmlPageId = 'messages';
	public $modelName = 'Messages';
	public $defaultAction = 'index';
	public $showSearchForm = false;

	public function init() {
		parent::init();
		
		/*if (Yii::app()->user->isGuest)
			throw404();*/
	}

	public function getViewPath($checkTheme=true){
		if($checkTheme && ($theme=Yii::app()->getTheme())!==null){
			if (is_dir($theme->getViewPath().DIRECTORY_SEPARATOR.'modules'.DIRECTORY_SEPARATOR.$this->getModule($this->id)->getName().DIRECTORY_SEPARATOR.'views'))
				return $theme->getViewPath().DIRECTORY_SEPARATOR.'modules'.DIRECTORY_SEPARATOR.$this->getModule($this->id)->getName().DIRECTORY_SEPARATOR.'views';
		}
		return Yii::getPathOfAlias('application.modules.'.$this->getModule($this->id)->getName().'.views');
	}

	public function beginWidget($className,$properties=array()){
		if($className == 'CustomForm'){
			$className = 'CActiveForm';
		}
		if($className == 'CustomGridView'){
			$className = 'CGridView';
		}
		return parent::beginWidget($className,$properties);
	}

	public function widget($className,$properties=array(),$captureOutput=false){
		if($className == 'bootstrap.widgets.TbButton'){
			if(isset($properties['htmlOptions'])){
				return CHtml::submitButton($properties['label'], $properties['htmlOptions']);
			} else {
				return CHtml::submitButton($properties['label']);
			}
		}

		return parent::widget($className,$properties,$captureOutput);
	}

	public function filters(){
		return array(
			'accessControl', // perform access control for CRUD operations
			array(
				'ESetReturnUrlFilter + index, view, create, update, bookingform, complain, mainform, add, edit',
			),
		);
	}

	public function accessRules(){
		return array(
			array('allow',
				'roles'=>array('registered'),
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
		
		$allUsers = $pages = null;
		$itemsProvider = new CArrayDataProvider(array());

		$this->setActiveMenu('my_mailbox');

		$return = Messages::getAllContactUsers(Yii::app()->user->id);

		if ($return) {
			$allUsers = $return['allUsers'];
			$pages = $return['pages'];

			if (count($allUsers)) {
				$itemsProvider = new CArrayDataProvider(
					$allUsers,
					array(
						'pagination' => array(
							'pageSize' => param('userPaginationPageSize', 20),
						),
					)
				);
			}
		}

		$this->render('index', array('allUsers' => $allUsers, 'pages' => $pages, 'itemsProvider' => $itemsProvider));
	}

	public function actionRead() {
		// если админ - делаем редирект на просмотр в админку
		if(Yii::app()->user->checkAccess('backend_access')){
			$this->redirect($this->createAbsoluteUrl('/apartments/backend/main/admin'));
		}
		
		$id = (int) Yii::app()->request->getParam('id');
		$apId = (int) Yii::app()->request->getParam('apId');

		$this->setActiveMenu('my_mailbox');

		if (!$id)
			throw404();

		// сами себе пытаемся отправить сообщение
		if ($id == Yii::app()->user->id)
			throw404();

		$user = User::model()->findByPk($id);
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
			$this->redirect(array('index'));*/

		//Yii::app()->user->setFlash('notice', tt('messages_user_help', 'messages'));

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
