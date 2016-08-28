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
	public $modelName = 'Currency';

	public function accessRules(){
		return array(
			array('allow',
				'expression'=> "Yii::app()->user->checkAccess('all_lang_and_currency_admin')",
			),
			array('deny',
				'users'=>array('*'),
			),
		);
	}

	public function actionAdmin(){
		Yii::app()->user->setFlash('warning', Yii::t('module_currency','moduleAdminHelp',
				array('{link}'=>CHtml::link(tc('Languages'), array('/lang/backend/main/admin'))))
		);

		parent::actionAdmin();
	}

    public function actionIndex(){
        Currency::model()->parseExchangeRates();

        deb('ok');
    }

    public function actionSetDefault(){
        $id = (int) Yii::app()->request->getPost('id');

        $model = Currency::model()->findByPk($id);
        $model->convert_data = (int) Yii::app()->request->getPost('convert_data');
		if($model->parseExchangeRates()) {
            echo 1;
            $model->setDefault();
            $model->parseExchangeRates();
        }

        Yii::app()->end();
    }

    public function actionUpdateCurrency() {
        if (Currency::model()->parseExchangeRates())
            Yii::app()->user->setFlash('success', tt('Currency rate update complete'));
        else
            Yii::app()->user->setFlash('error', tt('Currency rate update error'));

        $this->redirect(Yii::app()->createUrl('/currency/backend/main/admin'));
    }

    public function actionSetCurrencySource() {
        $id = (int) Yii::app()->request->getPost('id');
        ConfigurationModel::updateValue('currencySource', $id);
    }

    public function actionActivate(){
		if(demo()){
            throw new CException(tc('Sorry, this action is not allowed on the demo server.'));
        }

        $id = (int) $_GET['id'];
        $action = $_GET['action'];
        if($id){
            $model = Currency::model()->findByPk($id);
            if($model->is_default == 1 && $action != 'activate'){
                Yii::app()->end();
            }
        }
        parent::actionActivate();
    }

    public function actionCreate(){
        $model = new Currency();
        $translate = new TranslateMessage();

        if(isset($_POST['Currency'])){
            $model->attributes = $_POST['Currency'];

            if($model->validate()){
                $translate->attributes = $_POST['TranslateMessage'];
                $translate->category = 'module_currency';
                $translate->message = $model->char_code."_translate";
                if($translate->save()){
                    $model->save();
                    Yii::app()->cache->flush();
                    Yii::app()->user->setFlash('success', tc('Success'));
                    $this->redirect(Yii::app()->createUrl('/currency/backend/main/admin'));
                }
            }
        }

        $this->render('create', array('model' => $model, 'translate' => $translate));
    }


    public function actionUpdate($id){
        $model = $this->loadModel($id);

        //$model->scenario = 'advanced';
        $this->performAjaxValidation($model);
        $translate = $model->getTranslateModel();

        if(isset($_POST[$this->modelName])){
            $model->attributes=$_POST[$this->modelName];
            if($model->validate()){
                $translate->attributes = $_POST['TranslateMessage'];
                if($translate->save()){
                    if($model->save(false)){
                        Yii::app()->user->setFlash('success', tc('Success'));
                        $this->redirect(Yii::app()->createUrl('/currency/backend/main/admin'));
                    }
                }
            }
        }

        $this->render('update', array(
            'model'=>$model,
            'translate' => $translate,
        ));
    }

    public function actionDelete($id){
		if(demo()){
            throw new CException(tc('Sorry, this action is not allowed on the demo server.'));
        }

        if(Yii::app()->request->isPostRequest){
            $model = $this->loadModel($id);

            if($model->active || $model->is_default){
                throw new CHttpException(400,tt('You can not delete an active currency!'));
            }

            // we only allow deletion via POST request
            $model->delete();
            // if AJAX request (triggered by deletion via admin grid view), we should not redirect the browser
            if(!isset($_GET['ajax']))
                $this->redirect(isset($_POST['returnUrl']) ? $_POST['returnUrl'] : array('admin'));
        }
        else
            throw new CHttpException(400,'Invalid request. Please do not repeat this request again.');
    }

    public function actionView($id){
        $this->redirect(array('admin'));
    }
}
