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


class MailingController extends BaseMessagesController {
	public $layout='//layouts/admin';
	public $redirectTo = null;
	public $modelName = 'Mailing';
	public $defaultAction = 'admin';

	function init(){

		Yii::app()->bootstrap;
		Yii::app()->params['useBootstrap'] = true;

		parent::init();
		$this->menuTitle = Yii::t('common', 'Operations');
	}

	public function getViewPath($checkTheme=false){
		return Yii::getPathOfAlias('application.modules.messages.views.backend.mailing');
	}


	public function actionAdmin() {
		Yii::app()->user->setState('menu_active', 'messages.mailing');
		$this->params['messageModel'] = new Messages();

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
		$this->render('admin',
			array_merge(array('model'=>$model), $this->params)
		);
	}

	public function actionView($id) {
		$this->redirect(array('admin'));
	}

	public function actionSendMessages() {
		$itemsSelected = Yii::app()->request->getParam('itemsSelected');

		$errorsSend = array();
		$messageModel = new Messages();

		$this->performAjaxValidation($messageModel);

		if(isset($_POST['Messages'])){
			$messageModel->attributes = $_POST['Messages'];

			if ($messageModel->validate()) {
				if (is_array($itemsSelected) && count($itemsSelected)) {
					########################################################################
					// check file errors
					$fileErrors = array();

					if (count($itemsSelected) > Mailing::MAILING_USERS_LIMIT) {
						Yii::app()->user->setFlash('error',
							Yii::t('module_messages', 'max_newsletter_limit', array('{n}' => Mailing::MAILING_USERS_LIMIT))
						);

						$fileErrors[] = 3;
					}

					$files = CUploadedFile::getInstancesByName('files');

					if (isset($files) && count($files) > 0) {
						foreach ($files as $file) {
							$fName = $file->name;
							$fSize = $file->size;

							// check file size
							if ($fSize > $messageModel->fileMaxSize) {
								Yii::app()->user->setFlash('error',
									Yii::t('module_messages', 'Size {fName} exceeds the allowed (specified in php.ini) size {fileMaxSize} bytes.', array('{fName}' => $fName, 'fileMaxSize' => $messageModel->fileMaxSize))
								);

								$fileErrors[] = 1;
							}

							// check file extension
							$pathInfo = pathinfo($fName);
							$fileName = $pathInfo['filename'];
							$fileExt = strtolower($pathInfo['extension']);

							$supportExtArr = explode(',', $messageModel->supportExt);
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
						$this->redirect(array('admin'));
						Yii::app()->end();
					}
					########################################################################


					// pre files
					$filesPre = array();
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
							$fullFileName = '_'.md5(uniqid()).'.'.$fileExt;

							$file->saveAs($messageModel->uploadPath.'/'.$fullFileName);

							$filesPre[] = array(
								'file_id' => $m,
								'file_path' => $fullFileName,
								'orig_file_path' => $fileName.'.'.$fileExt
							);

						}
					}

					foreach ($itemsSelected as $item) {
						$userModel = User::model()->findByPk($item);
						if ($userModel) {

							$messageModel = new Messages();
							$messageModel->attributes = $_POST['Messages'];

							$messageModel->message = str_replace('{username}', $userModel->username, $messageModel->message);

							$messageModel->id_userFrom = Yii::app()->user->id;
							$messageModel->id_userTo = $item;
							$messageModel->is_read = Messages::STATUS_UNREAD_USER;
							$messageModel->allowHtml = 1;

							if ($messageModel->save(false)) {
								// save file
								if ($filesPre && count($filesPre)) {
									foreach ($filesPre as $fileOne) {
										$messageFile = new MessagesFiles();

										$messageFile->file_id = Yii::app()->user->id.$messageModel->id.$fileOne['file_id'];
										$messageFile->id_message = $messageModel->id;
										$messageFile->file_path = $fileOne['file_path'];
										$messageFile->orig_file_path = $fileOne['orig_file_path'];
										$messageFile->save();
									}
								}
							}
						}
					}

					$messageModel->unsetAttributes();
					if (!count($errorsSend)) {
						Yii::app()->user->setFlash('success', tt('Message sent to the users', 'messages'));
						$this->redirect(array('admin'));
					}
					else {
						Yii::app()->user->setFlash('error', tt('Message not sent to the users: ', 'messages').' '.implode(', ', $errorsSend));
						$this->redirect(array('admin'));
					}
				}
				else {
					Yii::app()->user->setFlash('error', tt('check_users_send', 'messages'));
					$this->redirect(array('admin'));
				}
			}
			else {
				if($messageModel->hasErrors()){
					Yii::app()->user->setFlash('error', CHtml::errorSummary($messageModel, null, null, array('class' => '')));
				}
				$this->redirect(array('admin'));
			}
		}
	}
}
