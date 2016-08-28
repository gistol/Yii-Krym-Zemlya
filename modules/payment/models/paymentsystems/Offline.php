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

class Offline extends PaymentSystem {

    public function init(){
        $this->name = 'offline';
        return parent::init();
    }

	public function rules(){
		return array(
		);
	}

	public function attributeLabels(){
		return array(
		);
	}

	public function processPayment(Payments $payment){
		$payment->status = Payments::STATUS_WAITOFFLINE;
		$payment->update(array('status'));

		try {
			$notifier = new Notifier;
			$notifier->raiseEvent('onOfflinePayment', $payment);
		} catch(CHttpException $e){}

		return array(
			'status' => Paysystem::RESULT_OK,
			'message' => tt('Thank you! Notification of your payment sent to the administrator.', 'payment'),
		);
	}
}