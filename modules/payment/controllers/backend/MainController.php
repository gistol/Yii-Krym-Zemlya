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

class MainController extends ModuleAdminController {
	public $modelName = 'Payments';
	public $defaultAction='admin';

	public function accessRules(){
		return array(
			array('allow',
				'expression'=> "Yii::app()->user->checkAccess('payment_admin')",
			),
			array('deny',
				'users'=>array('*'),
			),
		);
	}

	public function actionView($id){
		$this->redirect(array('admin'));
	}

    public function actionTest(){
        echo serialize(array('email'=>'', 'mode'=>Paysystem::MODE_REAL));
    }

    public function actionConfirm($id){
        $payment = Payments::model()->findByPk($id);

        if($payment){
			$payment->complete();
        }

		if(!Yii::app()->request->isAjaxRequest){
			$this->redirect(array('admin'));
		}
		Yii::app()->end();
    }
}
