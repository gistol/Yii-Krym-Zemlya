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

class City extends ParentModel{
	public $minSorter = 0;
	public $maxSorter = 0;
	public $multy;
	private static $_activeCity;

	const STATUS_INACTIVE = 0;
	const STATUS_ACTIVE = 1;
	const STATUS_MODERATION = 2;

	public static function model($className=__CLASS__){
		return parent::model($className);
	}

	public function tableName(){
		return '{{location_city}}';
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
			array('country_id, region_id', 'required'),
			array('country_id, region_id', 'numerical', 'integerOnly'=>true),
			array('name', 'i18nRequired', 'except'=>'multiply'),
			array('multy', 'required', 'on'=>'multiply'),
			array('name', 'i18nLength', 'max'=>128),
			array($this->getI18nFieldSafe(), 'safe'),
			array('active', 'safe'),
			array('sorter', 'numerical', 'integerOnly'=>true)
		);
	}

	public function relations(){
		//Yii::app()->getModule('city');
		return array(
			'country' => array(self::BELONGS_TO, 'Country', 'country_id'),
			'region' => array(self::BELONGS_TO, 'Region', 'region_id'),
		);
	}

	public function i18nFields(){
		return array(
			'name' => 'varchar(128) not null',
		);
	}
	
	public function seoFields() {
		return array(
			'fieldTitle' => 'name',
		);
	}

	public function attributeLabels(){
		return array(
			'id' => 'ID',
			'country_id' => tc('Country'),
			'country' => tc('Country'),
			'region_id' => tc('Region'),
			'region' => tc('Region'),
			'name' => tc('Name'),
			'multy' => tc('Name'),
			'active' => tc('Status'),
			'date_updated' => 'Date Updated',
		);
	}

	public function getName(){
		return $this->getStrByLang('name');
	}

	public function search(){
		if (!$this->country_id || !in_array($this->region_id, array_keys(Region::getRegionsArray($this->country_id, 0, 1))))
			$this->region_id = "";

		$criteria=new CDbCriteria;

		$tmp = 'name_'.Yii::app()->language;
		$criteria->compare('t.'.$tmp, $this->$tmp, true);
		$criteria->compare('t.active', $this->active);
		$criteria->compare('t.country_id', $this->country_id);
		$criteria->compare('t.region_id', $this->region_id);
		$criteria->with = array('country', 'region');
		//$criteria->order = 'country.sorter ASC, region.sorter ASC, t.sorter ASC';


		if ($this->region_id) {
			$this->minSorter = Yii::app()->db->createCommand()
				->select('MIN(sorter) as minSorter')
				->from($this->tableName())
				->where('region_id=:id', array(':id'=>$this->region_id))
				->queryScalar();
			$this->maxSorter = Yii::app()->db->createCommand()
				->select('MAX(sorter) as maxSorter')
				->from($this->tableName())
				->where('region_id=:id', array(':id'=>$this->region_id))
				->queryScalar();
		}


		return new CustomActiveDataProvider($this, array(
			'criteria'=>$criteria,
			'sort'=>array('defaultOrder'=>'country.sorter ASC, region.sorter ASC, t.sorter ASC'),
			'pagination'=>array(
				'pageSize'=>param('adminPaginationPageSize', 20),
			),
		));
	}

	public function afterDelete(){

		$sql = 'UPDATE {{apartment}} SET loc_city=0 WHERE loc_city="'.$this->id.'"';
		Yii::app()->db->createCommand($sql)->execute();
		
		if (issetModule('seo')) {
            $sql = 'DELETE FROM {{seo_friendly_url}} WHERE model_id="' . $this->id . '" AND model_name = "City"';
            Yii::app()->db->createCommand($sql)->execute();
        }

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

	public static function getCitiesArray($region, $type=0, $all=0, $onlyWithAds = false){
		// type 0 - без первой строки, 1 - пустая первая строка, 2 - любой, 3 - не указан
		// all 0 - active, 1 - all, 2 - active and moderation

		$join = '';

		switch ($all){
			case 2:
				$active_str = 'AND (active = '.City::STATUS_ACTIVE.' OR active = '.City::STATUS_MODERATION.') ';
				break;
			case 1:
				$active_str = "";
				break;
			default:
				$active_str = 'AND active = '.City::STATUS_ACTIVE.' ';
		}

		if ($onlyWithAds) {
			$join .= ' INNER JOIN {{apartment}} ap on lc.id = ap.loc_city';
			$ownerActiveCond = (param('useUserads')) ? ' AND ap.owner_active = '.Apartment::STATUS_ACTIVE : '';

			$addWhere = ' ap.price_type IN ('.implode(',', array_keys(HApartment::getPriceArray(Apartment::PRICE_SALE, true))).') AND ap.active = '.Apartment::STATUS_ACTIVE.' '.$ownerActiveCond.'';
			$active_str = ($active_str) ? ' AND '.$addWhere : ' WHERE '.$addWhere;
		}

		$name = ($all == City::STATUS_MODERATION) ? 'CONCAT(lc.name_'.Yii::app()->language.', CASE WHEN active = 2 THEN "'.' ('.tt('Awaiting moderation', 'common').')'.'" ELSE "" END )' :
			'lc.name_'.Yii::app()->language.'';

		$sql = 'SELECT lc.id, '.$name.' AS name FROM {{location_city}} lc '.$join.' WHERE lc.region_id = :region '.$active_str.' ORDER BY lc.sorter ASC';
		$res = Yii::app()->db->createCommand($sql)->queryAll(true, array(':region' => $region));

		$res = CHtml::listData($res, 'id', 'name');

		switch ($type) {
			case 1:
				$cities = CArray::merge(array(0 => ''), $res);
				break;
			case 2:
				$cities = CArray::merge(array(0 => tc('select city')), $res);
				break;
			case 3:
				$cities = CArray::merge(array(0 =>  tc('Not specified_m')), $res);
				break;
			default :
				$cities = $res;
		}


        return $cities;
    }

	public static function getModerationStatusArray($withAll = false)
	{
		$status = array();
		if ($withAll) {
			$status[''] = tt('All', 'common');
		}

		$status[self::STATUS_INACTIVE] = CHtml::encode(tt('Inactive', 'common'));
		$status[self::STATUS_ACTIVE] = CHtml::encode(tt('Active', 'common'));
		$status[self::STATUS_MODERATION] = CHtml::encode(tt('Awaiting moderation', 'common'));

		return $status;
	}

	public static function getAvalaibleStatusArray() {
		$statusesArr = self::getModerationStatusArray();
		if (!param('allowCustomCities', 0)) {
			if (array_key_exists(self::STATUS_MODERATION, $statusesArr))
				unset($statusesArr[self::STATUS_MODERATION]);
		}

		return $statusesArr;
	}

	public static function getCountModeration() {
		$sql = "SELECT COUNT(id) FROM {{location_city}} WHERE active=" . self::STATUS_MODERATION;
		return (int)Yii::app()->db->createCommand($sql)->queryScalar();
	}
	
	public static function getActiveCity(){
		if(self::$_activeCity === null){
			$ownerActiveCond = '';
			
			if (param('useUserads'))
				$ownerActiveCond = ' AND ap.owner_active = '.Apartment::STATUS_ACTIVE.' ';

			$sql = 'SELECT ac.name_'.Yii::app()->language.' AS name, ac.id AS id
					FROM {{apartment}} ap, {{location_city}} ac
					WHERE ac.id = ap.loc_city AND ac.active=1
					AND ap.price_type IN ('.implode(',', array_keys(HApartment::getPriceArray(Apartment::PRICE_SALE, true))).')
					AND ap.active = '.Apartment::STATUS_ACTIVE.' '.$ownerActiveCond.'
					ORDER BY ac.sorter';

			$results = Yii::app()->db->createCommand($sql)->queryAll();

			self::$_activeCity = CHtml::listData($results, 'id', 'name');
		}
		return self::$_activeCity;
	}
}