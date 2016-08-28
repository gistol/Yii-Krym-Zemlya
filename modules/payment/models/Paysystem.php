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

class Paysystem extends ParentModel {

	const STATUS_ACTIVE=1;
	const STATUS_INACTIVE=0;
	const MODE_REAL=1;
	const MODE_TEST=0;

	const ID_BALANCE = 4;

	const RESULT_ERROR = 1;
	const RESULT_OK = 2;
	const RESULT_NOTICE = 3;
	const RESULT_HTML = 4;

	public $payModel = null;
	public $payModelName = null;
	public $viewName = null;

	public static function model($className=__CLASS__){
		return parent::model($className);
	}

	public function tableName(){
		return '{{paysystem}}';
	}

	public function rules(){
		return array(
			array('name', 'i18nRequired'),
			array('active', 'required'),
			array($this->getI18nFieldSafe(), 'safe'),
        );
	}

	public function i18nFields(){
		return array(
			'name' => 'varchar(255) not null',
			'description' => 'text not null',
		);
	}

	public function scopes(){
		return array(
			'active' => array('condition' => 'active='.self::STATUS_ACTIVE)
		);
	}

	protected function afterFind(){
		// создаем зависимые модели
		$this->createPayModel();

		return parent::afterFind();
	}

	public function beforeSave(){
		$settings = array();
		foreach($this->payModel->attributes as $key => $value) {
			$settings[$key] = $value;
		}
		// Сохраняем аттрибуты зависимой модели (настройки платежки)
		$this->settings = CJSON::encode($settings);

		return parent::beforeSave();
	}

	public function attributeLabels(){
		return array(
			'active' => tt('Status', 'payment'),
			'name' => tt('Name', 'payment'),
			'description' => tt('Description', 'payment'),
		);
	}

	public function createPayModel(){
		if($this->model_name && !$this->payModel){
			$this->payModelName = ucfirst($this->model_name);
			$this->payModel = new $this->payModelName;

			$this->viewName = $this->model_name;
			$this->payModel->attributes = CJSON::decode($this->settings, true);
		}
		return $this->payModel;
	}

	public static function getPaysystems($all = null){
		if($all){
			$models = Paysystem::model()->findAll(array('order' => 'sorter'));
		} else {
			$models = Paysystem::model()->findAll(array('order' => 'sorter', 'condition' => 'active = '.Paysystem::STATUS_ACTIVE));
		}

		return $models;
	}

	public static function getPaysystemsWithoutBalance() {
		return Paysystem::model()->findAll(array('order' => 'sorter', 'condition' => 'id != '.self::ID_BALANCE.' AND active = '.self::STATUS_ACTIVE));
	}

	public function getName(){
		return $this->getStrByLang('name');
	}

	public function getDescription(){
		return $this->getStrByLang('description');
	}

	public function search() {
		$criteria = new CDbCriteria;

		$criteria->compare($this->getTableAlias().'.id', $this->id);

		$tmp = 'name_'.Yii::app()->language;
		$criteria->compare($this->getTableAlias().'.'.$tmp, $this->$tmp, true);

		$criteria->order = 'sorter ASC';

		return new CActiveDataProvider($this, array(
			'criteria' => $criteria,
			'pagination' => array(
				'pageSize' => param('adminPaginationPageSize', 20),
			),
		));
	}
}