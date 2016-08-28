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

class Country extends ParentModel{

	public static function model($className=__CLASS__){
		return parent::model($className);
	}

	public function tableName(){
		return '{{location_country}}';
	}

	public function behaviors()
	{
		return array(
			'ERememberFiltersBehavior' => array(
				'class' => 'application.components.behaviors.ERememberFiltersBehavior',
				'defaults' => array(),
				'defaultStickOnClear' => false
			),
			/*'AutoTimestampBehavior' => array(
				'class' => 'zii.behaviors.CTimestampBehavior',
				'createAttribute' => 'date_updated',
				'updateAttribute' => 'date_updated',
			),*/
		);
	}

	public function rules(){
		return array(
			array('name', 'i18nRequired'),
			array('name', 'i18nLength', 'max'=>128),
			array($this->getI18nFieldSafe(), 'safe'),
			array('active', 'safe', 'on'=>'search'),
			array('sorter', 'numerical', 'integerOnly'=>true)
		);
	}

	public function relations(){
		//Yii::app()->getModule('Region');
		return array(
			'regions' => array(self::HAS_MANY, 'Region', 'country_id'),
			'cities' => array(self::HAS_MANY, 'City', 'country_id'),
		);
	}

	public function i18nFields(){
		return array(
			'name' => 'varchar(128) not null',
		);
	}

	public function attributeLabels(){
		return array(
			'id' => 'ID',
			'name' => tc('Name'),
			'date_updated' => 'Date Updated',
		);
	}

	public function getName(){
		return $this->getStrByLang('name');
	}

	public function search(){
		$criteria=new CDbCriteria;

		$tmp = 'name_'.Yii::app()->language;

		$criteria->compare('t.active', $this->active);
		$criteria->compare($tmp, $this->$tmp, true);
		//$criteria->order = 'sorter ASC';

		return new CustomActiveDataProvider($this, array(
			'criteria'=>$criteria,
			'sort'=>array('defaultOrder'=>'sorter ASC'),
			'pagination'=>array(
				'pageSize'=>param('adminPaginationPageSize', 20),
			),
		));
	}



	public function afterDelete(){
		$sql = 'DELETE FROM {{location_region}} WHERE country_id="'.$this->id.'";';
		Yii::app()->db->createCommand($sql)->execute();

		$sql = 'DELETE FROM {{location_city}} WHERE country_id="'.$this->id.'";';
		Yii::app()->db->createCommand($sql)->execute();

		$sql = 'UPDATE {{apartment}} SET loc_country=0, loc_region=0, loc_city=0 WHERE loc_country="'.$this->id.'"';
		Yii::app()->db->createCommand($sql)->execute();

		return parent::afterDelete();
	}

	public function beforeSave(){
		if($this->isNewRecord){
			$maxSorter = Yii::app()->db->createCommand()
				->select('MAX(sorter) as maxSorter')
				->from($this->tableName())
				->queryScalar();
			$this->sorter = $maxSorter+1;
		}

		return parent::beforeSave();
	}

	private static $cache;

	public static function getCountriesArray($type = 0, $all = 0, $onlyWithAds = false){
		// 0 - без первой строки, 1 - пустая первая строка, 2 - любой, 3 - не указан

		$cacheKey = md5($type.$all.intval($onlyWithAds));
		if(isset(self::$cache[$cacheKey])){
			return self::$cache[$cacheKey];
		}

		$join = '';
		$active_str = ($all) ? '' : ' WHERE lc.active = 1 ';
		
		if ($onlyWithAds) {
			$useIndex = 'FORCE INDEX (country_type_priceType_halfActive)';
			$ownerActiveCond = '';
			
			if (param('useUserads')) {
				$useIndex = 'FORCE INDEX (country_type_priceType_fullActive)';
				$ownerActiveCond = ' AND ap.owner_active = '.Apartment::STATUS_ACTIVE;
			}
			
			$join .= ' INNER JOIN {{apartment}} ap '.$useIndex.' ON lc.id = ap.loc_country';
			
			$addWhere = ' ap.price_type IN ('.implode(',', array_keys(HApartment::getPriceArray(Apartment::PRICE_SALE, true))).') AND ap.active = '.Apartment::STATUS_ACTIVE.' '.$ownerActiveCond.'';
			$active_str = ($active_str) ? ' AND '.$addWhere : ' WHERE '.$addWhere;
		}

		$sql = 'SELECT lc.id, lc.name_'.Yii::app()->language.' AS name FROM {{location_country}} lc FORCE INDEX ( sorter ) '.$join.' '.$active_str.' GROUP BY lc.id ORDER BY lc.sorter ASC';
		$res = Yii::app()->db->createCommand($sql)->queryAll();

		$res = CHtml::listData($res, 'id', 'name');
		
		switch ($type) {
			case 1:
				$countries = CArray::merge(array(0 => ''), $res);
				break;
			case 2:
				$countries = CArray::merge(array(0 => tc('select country')), $res);
				break;
			case 3:
				$countries = CArray::merge(array(0 => tc('Not specified_f')), $res);
				break;
			default :
				$countries = $res;
		}

		self::$cache[$cacheKey] = $countries;

		return $countries;
	}
}