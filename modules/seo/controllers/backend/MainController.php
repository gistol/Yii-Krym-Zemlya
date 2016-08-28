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
	public $modelName = 'SeoFriendlyUrl';

	public function accessRules(){
		return array(
			array('allow',
				'expression'=> "Yii::app()->user->checkAccess('all_settings_admin')",
			),
			array('deny',
				'users'=>array('*'),
			),
		);
	}

    public function actionAdmin(){
        $model = new $this->modelName('search');
        $model->resetScope();
		$model->notImages();
		if (issetModule('location')) {
			$model->locationModuleCity();
		}
		else {
			$model->notLocationModuleCity();
		}
        $model->unsetAttributes();  // clear any default values
        if(isset($_GET[$this->modelName])){
            $model->attributes = $_GET[$this->modelName];
        }

		$altImages = new $this->modelName('search');
        $altImages->resetScope();
		$altImages->onlyImages()->withNotEmptyAlt();
        $altImages->unsetAttributes();  // clear any default values
        if(isset($_GET[$this->modelName])){
            $altImages->attributes = $_GET[$this->modelName];
        }
		
        $gen = new SeoGenerateForm();
        $validGen = true;

        $settings = new SeoSettings();
        $settingsForm = new SeoSettingsForm();
        $valid = true;
        if(isset($_POST['TranslateMessage'])){
            foreach($settings->models as $key => $set){
                if(!isset($_POST['TranslateMessage'][$key])){
                    continue;
                }
                $set->attributes = $_POST['TranslateMessage'][$key];
                if(!$set->save()){
                    $valid = false;
                }
            }

            if(isset($_POST['SeoSettingsForm'])){
                $settingsForm->attributes = $_POST['SeoSettingsForm'];
                $valid = $settingsForm->validate() && $settingsForm->save();
            }

            if($valid){
                Yii::app()->user->setFlash('success', tt('Success save'));
            }
        }

        if(isset($_POST['SeoGenerateForm'])){
            $gen->attributes = $_POST['SeoGenerateForm'];
            if($gen->validate()){
                Yii::app()->user->setFlash('success', tt('Successful SEO Generation'));
            } else {
                $validGen = false;
            }
        }

        $this->render('admin', array(
            'model'=>$model,
            'settings' => $settings,
            'settingsForm' => $settingsForm,
            'valid' => $valid,
            'gen' => $gen,
			'altImages' => $altImages,
            'validGen' => $validGen,
        ));
    }

	public function actionUpdate($id) {
		$this->redirectTo = array('admin');

        $model = $this->loadModel($id);

        $this->performAjaxValidation($model);

        if(isset($_POST[$this->modelName])){
            $model->attributes=$_POST[$this->modelName];
            if($model->validate()){
                if($model->save(false)){
                    if (!empty($this->redirectTo))
                        $this->redirect($this->redirectTo);
                    else
                        $this->redirect(array('view','id'=>$model->id));
                }
            }
        }

        $this->render('update', array(
            'model' => $model,
        ));
	}
	
	public function actionUpdateSeoImage($id) {
		$this->redirectTo = array('admin');

        $model = $this->loadModel($id);
		$model->scenario = 'image';

        $this->performAjaxValidation($model);

        if(isset($_POST[$this->modelName])){
            $model->attributes=$_POST[$this->modelName];
            if($model->validate()){
                if($model->save(false)){
                    if (!empty($this->redirectTo))
                        $this->redirect($this->redirectTo);
                    else
                        $this->redirect(array('view','id'=>$model->id));
                }
            }
        }

        $this->render('update_seo_image', array(
            'model' => $model,
        ));
	}

    public function actionGenerate()
    {
        $gen = new SeoGenerateForm();


        $this->redirect(array('admin'));
    }

    public function actionRegenSeo(){

        $modelsAll = SeoFriendlyUrl::model()->findAll();
        $activeLangs = Lang::getActiveLangs();

        foreach($modelsAll as $model){
            foreach($activeLangs as $lang){
                $field = 'url_' . $lang;
                $model->$field = translit($model->$field);
            }

            $model->save();
        }

        echo 'end';
    }
}