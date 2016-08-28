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

class AdvertController extends ModuleAdminController{
	public $modelName = 'Advert';

	public function accessRules(){
		return array(
			array('allow',
				'expression'=> "Yii::app()->user->checkAccess('all_modules_admin')",
			),
			array('deny',
				'users'=>array('*'),
			),
		);
	}

	public function init(){
		Yii::import('application.modules.advertising.models.AdvertArea');

		$advertModel = new Advert;
		$advertModel->publishAssets();
		parent::init();
	}

	public function actionAdmin(){
		$dataProvider = new CActiveDataProvider('Advert',
			array(
				'pagination'=>array(
					'pageSize'=> 3 //param('adminPaginationPageSize', 20),
				),
				'sort' => array(
					'defaultOrder' => 'id DESC',
				)
			)
		);
		$this->render('admin',array('dataProvider' => $dataProvider));
	}

	public function actionCreate(){
		$model=new $this->modelName;
		//$this->performAjaxValidation($model);

		if(isset($_POST[$this->modelName])){
			$model->attributes=$_POST[$this->modelName];
			$model->scenario = $model->type;

			if (in_array($model->type, array_flip(Advert::getAvailableTypes()))) {
				$isFile = $isHtml = false;
				if ($model->type == 'file')
					$isFile = true;
				if ($model->type == 'html')
					$isHtml = true;

				if($model->validate()){
					if ($isFile) {
						$activeLangs = Lang::getActiveLangs();
						if ($activeLangs && is_array($activeLangs)) {
							foreach ($activeLangs as $key => $val) {
								$model->setAttribute('js_'.$key, '');
								$model->setAttribute('html_'.$key, '');
							}
						}
						$model->file = CUploadedFile::getInstance($model, 'file_path');
						if ($model->file) {
							$model->file_path = md5(uniqid()).'.'.$model->file->extensionName;
						}
					}
					else {
						$activeLangs = Lang::getActiveLangs();
						if ($activeLangs && is_array($activeLangs)) {
							foreach ($activeLangs as $key => $val) {
								if($isHtml)
									$model->setAttribute('js_'.$key, '');
								else { # js
									$model->setAttribute('html_'.$key, '');
								}
							}
						}
						$model->file_path = $model->url = $model->alt_text = '';
					}

					if($model->save(false)){
						if ($model->file) {
							$model->file->saveAs(Yii::getPathOfAlias('webroot').'/uploads/rkl/'.$model->file_path);
						}
						$this->redirect(array('admin'));
					}
				}
			}
		}

		$this->render('create', array('model'=>$model));
	}

	public function actionUpdate($id){
		$model = $this->loadModel($id);
		//$this->performAjaxValidation($model);

		if(isset($_POST[$this->modelName])){
			$model->attributes=$_POST[$this->modelName];
			$model->scenario = $model->type;
			$oldFile = '';

			if (in_array($model->type, array_flip(Advert::getAvailableTypes()))) {
				$isFile = $isHtml = false;
				if ($model->type == 'file') {
					$isFile = true;
					$oldFile = $model->file_path;
				}
				if ($model->type == 'html')
					$isHtml = true;

				if($model->validate()){
					if ($isFile) {
						$activeLangs = Lang::getActiveLangs();
						if ($activeLangs && is_array($activeLangs)) {
							foreach ($activeLangs as $key => $val) {
								$model->setAttribute('js_'.$key, '');
								$model->setAttribute('html_'.$key, '');
							}
						}
						$model->file = CUploadedFile::getInstance($model, 'file_path');						
						if (!empty($model->file)) {
							$model->file_path = md5(uniqid()).'.'.$model->file->extensionName;
						}
						elseif ($oldFile) {
							$model->file_path = $oldFile;
						}
					}
					else {
						$activeLangs = Lang::getActiveLangs();
						if ($activeLangs && is_array($activeLangs)) {
							foreach ($activeLangs as $key => $val) {
								if($isHtml) {
									$model->setAttribute('js_'.$key, '');
								}
								else { # js
									$model->setAttribute('html_'.$key, '');
								}
							}
						}
						$model->file_path = $model->url = $model->alt_text = '';
					}
					
					if($model->save(false)){
						if (!empty($model->file)) {
							$model->file->saveAs(Yii::getPathOfAlias('webroot').'/uploads/rkl/'.$model->file_path);
							@unlink(Yii::getPathOfAlias('webroot').'/uploads/rkl/'.$oldFile);
						}
						$this->redirect(array('admin'));
					}
				}
			}
		}

		$this->render('update',	array('model'=> $model));
	}

	public function actionDelete($id){
		$fr = Yii::app()->request->getParam('fr');
		if($fr && $fr == 'adm'){
			// we only allow deletion via POST request
			$this->loadModel($id)->delete();
			// if AJAX request (triggered by deletion via admin grid view), we should not redirect the browser
			if(!isset($_GET['ajax']))
				$this->redirect(isset($_POST['returnUrl']) ? $_POST['returnUrl'] : array('admin'));
		}
		else
			throw new CHttpException(400,'Invalid request. Please do not repeat this request again.');
	}
}
