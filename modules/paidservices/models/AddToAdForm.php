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

class AddToAdForm extends CFormModel {
	public $paid_id = PaidServices::ID_UP_IN_SEARCH;
	public $option_id;
	public $date_end;

	public function rules(){
		return array(
			array('paid_id, date_end', 'required'),
			array('paid_id, option_id', 'numerical', 'integerOnly' => true),
		);
	}

	public function attributeLabels() {
		return array(
			'date_end' => tc('is valid till')
		);
	}
}
