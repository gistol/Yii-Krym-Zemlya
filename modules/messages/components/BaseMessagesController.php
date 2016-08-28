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

class BaseMessagesController extends Controller {
	public $params = array();
	public $scenario = null;
	public $with = array();
	protected $_model = null;
	public $cityActive;

	public function init(){
		parent::init();
		
		if (!issetModule('messages'))
			throw404();
		
		$this->cityActive = SearchForm::cityInit();
	}

	public function filters(){
		return array(
			'accessControl', // perform access control for CRUD operations
		);
	}

	public function accessRules(){
		return array(
			array('allow',
				'expression'=> "Yii::app()->user->checkAccess('messages_admin')",
			),
			array('deny',
				'users'=>array('*'),
			),
		);
	}

	protected function performAjaxValidation($model){
		if(isset($_POST['ajax']) && $_POST['ajax']===$this->modelName.'-form'){
			echo CActiveForm::validate($model);
			Yii::app()->end();
		}
	}

	protected function rememberPage(){
		// persist page number
		$pageParam = $this->modelName . '_page';
		if (isset($_GET[$pageParam])) {
			$page = $_GET[$pageParam];
			Yii::app()->user->setState($this->id . '-page', (int) $page);
		} else {
			$page = Yii::app()->user->getState($this->id . '-page', 1);
			$_GET[$pageParam] = $page;
		}
	}

	public function returnHtmlMessageSenderName($data, $fromAdmin = false) {
		$return = '';

		if ($data) {
			if ($fromAdmin) {
				if ($data->phone)
					$return .= CHtml::encode($data->username . ' ('.$data->phone.')');
				else
					$return .= CHtml::encode($data->username);
			}
			else
				if ($data->phone)
					$return .= CHtml::encode($data->username . ' ('.$data->phone.')');
				else
					$return .= CHtml::encode($data->username);
		}
		return $return;
	}

	public function actionDoSend() {
		$this->modelName = 'Messages';

		$id = (int) Yii::app()->request->getParam('id');
		$apId = (int) Yii::app()->request->getParam('apId');

		if (!$id)
			throw404();

		// сами себе пытаемся отправить сообщение
		if ($id == Yii::app()->user->id)
			throw404();

		$user = User::model()->findByPk($id);
		$model = new $this->modelName;

		$this->performAjaxValidation($model);

		if(isset($_POST[$this->modelName])){
			$model->attributes = $_POST[$this->modelName];

			if ($model->validate()) {
				$model->id_userFrom = Yii::app()->user->id;
				$model->id_userTo = $id;
				$model->from_listing_id = $apId;
				$model->is_read = Messages::STATUS_UNREAD_USER;
				$model->allowHtml = (Yii::app()->user->checkAccess('backend_access')) ? 1 : 0;

				########################################################################
				// check file errors
				$fileErrors = array();
				$files = CUploadedFile::getInstancesByName('files');

				if (isset($files) && count($files) > 0) {
					foreach ($files as $file) {
						$fName = $file->name;
						$fSize = $file->size;

						// check file size
						if ($fSize > $model->fileMaxSize) {
							Yii::app()->user->setFlash('error',
								Yii::t('module_messages', 'Size {fName} exceeds the allowed (specified in php.ini) size {fileMaxSize} bytes.', array('{fName}' => $fName, 'fileMaxSize' => $model->fileMaxSize))
							);

							$fileErrors[] = 1;
						}

						// check file extension
						$pathInfo = pathinfo($fName);
						$fileName = $pathInfo['filename'];
						$fileExt = strtolower($pathInfo['extension']);

						$supportExtArr = explode(',', $model->supportExt);
						$supportExtArr = array_map('trim',$supportExtArr);
						if (!in_array($fileExt, $supportExtArr)) {
							Yii::app()->user->setFlash('error',
								Yii::t('module_messages', 'File extension: {fName} is not valid.', array('{fName}' => $fName))
							);

							$fileErrors[] = 2;
						}
					}
				}

				if (count($fileErrors)) {
					$this->redirect(array('read', 'id'=> $id));
				}
				########################################################################

				if ($model->save(false)) {
					// get files
					$files = CUploadedFile::getInstancesByName('files');
					$m = 1;
					if (isset($files) && count($files) > 0) {
						foreach ($files as $file) {
							$m++;
							$fName = $file->name;

							// check file extension
							$pathInfo = pathinfo($fName);
							$fileName = $pathInfo['filename'];
							$fileExt = strtolower($pathInfo['extension']);

							// save file
							$fullFileName = $model->id.'_'.md5(uniqid()).'.'.$fileExt;
							$file->saveAs($model->uploadPath.'/'.$fullFileName);

							// save to DB
							$messageFile = new MessagesFiles();

							$messageFile->file_id = Yii::app()->user->id.$model->id.$m;
							$messageFile->id_message = $model->id;
							$messageFile->file_path = $fullFileName;
							$messageFile->orig_file_path = $fileName.'.'.$fileExt;
							$messageFile->save();
						}
					}

					$model->unsetAttributes();
					Yii::app()->user->setFlash('success', tt('Message sent to the user', 'messages'));

					if (Yii::app()->user->checkAccess('backend_access')) {
						$this->redirect(array('/messages/backend/main/read', 'id' => $id));
					}
					else {
						$this->redirect(array('/messages/main/read', 'id' => $id));
					}
				}
			}
		}
	}

	public function actionDelete() {
		throw404();

		/*$id = Yii::app()->request->getParam('id');

		$this->setActiveMenu('my_mailbox');

		if (!$id)
			throw404();


		$criteria = new CDbCriteria();
		$criteria->addCondition('id_userFrom = :id_userFrom');
		$criteria->addCondition('id_userTo = :id_userTo');
		$criteria->params[':id_userFrom'] = Yii::app()->user->id;
		$criteria->params[':id_userTo'] = $id;

		Messages::model()->updateAll(array('is_deleted' => Messages::MESSAGE_DELETED), $criteria);

		$criteria = new CDbCriteria();
		$criteria->addCondition('id_userFrom = :id_userFrom');
		$criteria->addCondition('id_userTo = :id_userTo');
		$criteria->params[':id_userFrom'] = $id;
		$criteria->params[':id_userTo'] = Yii::app()->user->id;

		Messages::model()->updateAll(array('is_deleted' => Messages::MESSAGE_DELETED), $criteria);

		Yii::app()->user->setFlash('success', tt('success_delete_message', 'messages'));
		$this->redirect(array('index'));*/
	}

	public function actionDownloadFile($fileId = null) {
		$this->setActiveMenu('my_mailbox');

		if ($fileId) {
			$sql = 'SELECT id_message, orig_file_path, file_path FROM {{messages_files}} WHERE file_id = "'.(int) $fileId.'"';
			$result = Yii::app()->db->createCommand($sql)->queryRow();

			if ($result) {
				if (!Yii::app()->user->checkAccess('backend_access')) {
					$sqlOwner = 'SELECT id_userTo, id_userFrom FROM {{messages}} WHERE id = "'.$result['id_message'].'"';
					$resultOwner = Yii::app()->db->createCommand($sqlOwner)->queryRow();

					if ($resultOwner['id_userTo'] != Yii::app()->user->id && $resultOwner['id_userFrom'] != Yii::app()->user->id)
						throw404();
				}

				$message = new Messages();
				Controller::disableProfiler();
				Yii::app()->request->sendFile($result['orig_file_path'], file_get_contents($message->uploadPath.'/'.$result['file_path']));
			}
			else
				throw404();
		}
	}
}