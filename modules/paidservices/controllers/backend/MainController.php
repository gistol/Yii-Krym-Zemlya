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

class MainController extends ModuleAdminController
{
    public $modelName = 'PaidServices';

    public function accessRules()
    {
        return array(
            array('allow',
                'expression' => "Yii::app()->user->checkAccess('paidservices_admin')",
            ),
            array('deny',
                'users' => array('*'),
            ),
        );
    }

    public function actionAdmin()
    {

        $dataProvider = new CActiveDataProvider(PaidServices::model()->with('options'));

        $this->render('admin', array(
            'dataProvider' => $dataProvider
        ));
    }

    public function actionView($id)
    {
        $this->redirect('admin');
    }

    public function actionCreate()
    {
        $model = new PaidOptions();

        $this->performAjaxValidation($model);

        if (isset($_POST['PaidOptions'])) {
            $model->attributes = $_POST['PaidOptions'];
            if ($model->save()) {
                $this->redirect(array('admin'));
            }
        }

        $this->render('create_option', array('model' => $model));
    }

    public function actionUpdate($id)
    {
        $model = PaidServices::model()->findByPk($id);
        if (!$model) {
            throw404();
        }

        $data = array();
        $dataModel = null;
        $dataModelValidate = true;
		if ($model->json_data && $model->dataModel) {
            $dataModel = new $model->dataModel;
            $dataModel->attributes = CJSON::decode($model->json_data);
        }

		if ($model->id == PaidServices::ID_BOOKING_PAY)
            $model->scenario = 'bookingpay';

		$this->performAjaxValidation($model);

        if($dataModel && isset($_POST[$model->dataModel])){
            $dataModel->attributes = $_POST[$model->dataModel];
            $dataModelValidate = $dataModel->validate();
            if($dataModelValidate){
                $model->json_data = CJSON::encode($_POST[$model->dataModel]);
            }
        }

		if (isset($_POST['PaidServices'])) {
            $model->attributes = $_POST['PaidServices'];
            if ($dataModelValidate && $model->validate()) {
                $model->save();
                Yii::app()->user->setFlash('success', tc('Success'));
                $this->redirect(array('admin'));
            }
        }

		$this->render('update', array(
            'model' => $model,
            'dataModel' => $dataModel
        ));
	}

    public function actionUpdateOption($id)
    {
        $model = PaidOptions::model()->findByPk($id);
        if (!$model) {
            throw404();
        }

        $this->performAjaxValidation($model);

        if (isset($_POST['PaidOptions'])) {
            $model->attributes = $_POST['PaidOptions'];
            if ($model->save()) {
                $this->redirect(array('admin'));
            }
        }

        $this->render('update_option', array('model' => $model));
    }

    public function actionDeleteOption($id)
    {
        $model = PaidOptions::model()->findByPk($id);
        if (!$model) {
            throw404();
        }
        $model->delete();

        $this->redirect(array('admin'));
    }


    public function actionAddPaid($id = 0, $withDate = 0)
    {
        $model = new AddToAdForm();

        $paidServices = PaidServices::model()->findAll('type = :type AND active = 1', array(':type' => PaidServices::TYPE_FOR_AD));
        $paidServicesArray = CHtml::listData($paidServices, 'id', 'name');

        $request = Yii::app()->request;
        $data = $request->getPost('AddToAdForm');

        if ($data) {
            $apartmentId = $request->getPost('ad_id');
            $withDate = $request->getPost('withDate');

            $model->attributes = $data;
            if ($model->validate()) {
                $apartment = Apartment::model()->findByPk($apartmentId);
                $paidService = PaidServices::model()->findByPk($model->paid_id);

                if (!$paidService || !$apartment) {
                    throw new CException('Not valid data');
                }
                if (PaidServices::applyToApartment($apartmentId, $paidService->id, $model->date_end)) {
                    echo CJSON::encode(array(
                        'status' => 'ok',
                        'apartmentId' => $apartmentId,
                        'html' => HApartment::getPaidHtml($apartment, $withDate, true)
                    ));
                    Yii::app()->end();
                }
            } else {
                echo CJSON::encode(array(
                    'status' => 'err',
                    'html' => $this->renderPartial('_add_to_form', array(
                        'id' => $apartmentId,
                        'model' => $model,
                        'withDate' => $withDate,
                        'paidServicesArray' => $paidServicesArray
                    ), true)
                ));
                Yii::app()->end();
            }
        }

        $renderData = array(
            'id' => $id,
            'model' => $model,
            'withDate' => $withDate,
            'paidServicesArray' => $paidServicesArray
        );

        if (Yii::app()->request->isAjaxRequest) {
            $this->renderPartial('_add_to_ad', $renderData);
        } else {
            $this->render('_add_to_ad', $renderData);
        }
    }

    public function actionJsonForm()
    {
        $arr = array(
            'PaidBooking' => array(
                array(
                    'type' => 'text',
                    'name' => 'percent',
                    'value' => 10
                ),
                array(
                    'type' => 'checkbox',
                    'name' => 'pay_immediately',
                    'value' => 0
                ),
            )
        );

        echo CJSON::encode($arr);
    }

}
