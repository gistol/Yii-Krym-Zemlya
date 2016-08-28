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


/**
 * This is the model class for table "{{paid_options}}".
 *
 * The followings are the available columns in table '{{paid_options}}':
 * @property integer $id
 * @property integer $active
 * @property integer $sorter
 * @property integer $paid_service_id
 * @property integer $price
 * @property integer $duration_days
 */
class PaidOptions extends CActiveRecord
{
	/**
	 * Returns the static model of the specified AR class.
	 * @param string $className active record class name.
	 * @return PaidOptions the static model class
	 */
	public static function model($className=__CLASS__)
	{
		return parent::model($className);
	}

	/**
	 * @return string the associated database table name
	 */
	public function tableName()
	{
		return '{{paid_options}}';
	}

	/**
	 * @return array validation rules for model attributes.
	 */
	public function rules()
	{
		// NOTE: you should only define rules for those attributes that
		// will receive user inputs.
		return array(
			array('paid_service_id, price, duration_days', 'required'),
			array('active, sorter, paid_service_id, price, duration_days', 'numerical', 'integerOnly'=>true),
			// The following rule is used by search().
			// Please remove those attributes that should not be searched.
			array('id, active, sorter, paid_service_id, price, duration_days', 'safe', 'on'=>'search'),
		);
	}

	/**
	 * @return array relational rules.
	 */
	public function relations()
	{
		// NOTE: you may need to adjust the relation name and the related
		// class name for the relations automatically generated below.
		return array(
			'paidService' => array(self::BELONGS_TO, 'PaidServices', 'paid_service_id'),
		);
	}

	public function behaviors() {
		$arr = array();
		if (issetModule('historyChanges')) {
			$arr['ArLogBehavior'] = array(
				'class' => 'application.modules.historyChanges.components.ArLogBehavior',
			);
		}

		return $arr;
	}
	
	/**
	 * @return array customized attribute labels (name=>label)
	 */
	public function attributeLabels()
	{
		return array(
			'id' => 'ID',
			'active' => 'Active',
			'sorter' => 'Sorter',
			'paid_service_id' => 'Paid Service',
			'duration_days' => tt('Duration of the day'),
			'price' => tt('Price'),
		);
	}

	/**
	 * Retrieves a list of models based on the current search/filter conditions.
	 * @return CActiveDataProvider the data provider that can return the models based on the search/filter conditions.
	 */
	public function search()
	{
		// Warning: Please modify the following code to remove attributes that
		// should not be searched.

		$criteria=new CDbCriteria;

		$criteria->compare('id',$this->id);
		$criteria->compare('active',$this->active);
		$criteria->compare('sorter',$this->sorter);
		$criteria->compare('paid_service_id',$this->paid_service_id);
		$criteria->compare('price',$this->price);
		$criteria->compare('duration_days',$this->duration_days);

		return new CActiveDataProvider($this, array(
			'criteria'=>$criteria,
		));
	}


	public function getPriceAndCurrency(){
		return $this->price . ' ' . Currency::getDefaultCurrencyName();
	}

	public function getEditHtml() {
		$edit = CHtml::link(tc('Edit'), Yii::app()->createUrl('/paidservices/backend/main/updateOption', array('id' => $this->id)));
		$delete = CHtml::link(tc('Delete'), Yii::app()->createUrl('/paidservices/backend/main/deleteOption', array('id' => $this->id)));

		return $edit . ' | ' . $delete;
	}

}