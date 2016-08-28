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

class MetroStations extends ParentModel {
	public $country;
	public $region;
	public $minSorter = 0;
	public $maxSorter = 0;
	public $multy;
		
	const STATUS_INACTIVE = 0;
	const STATUS_ACTIVE = 1;

	public static function model($className=__CLASS__) {
		return parent::model($className);
	}

	public function behaviors() {
		return array(
			'ERememberFiltersBehavior' => array(
				'class' => 'application.components.behaviors.ERememberFiltersBehavior',
				'defaults'=>array(),
				'defaultStickOnClear'=>false
			),
			'AutoTimestampBehavior' => array(
				'class' => 'zii.behaviors.CTimestampBehavior',
				'createAttribute' => 'date_created',
				'updateAttribute' => 'date_updated',
				'setUpdateOnCreate' => false,
			),
		);
	}

	public function tableName() {
		return '{{metro_stations}}';
	}

	public function rules() {
		return array(
			array((issetModule('location') ? 'loc_city' : 'city_id'), 'required'),
			array('loc_city, city_id', 'numerical', 'min' => 1, 'tooSmall' => Yii::t('common','{attribute} cannot be blank.')),
			array('name', 'i18nLength', 'max'=>255),
			array('name', 'i18nRequired', 'except'=>'multiply'),
			array('multy', 'required', 'on'=>'multiply'),
			array($this->getI18nFieldSafe(), 'safe'),
			array('active, sorter, country, region, loc_city, city_id', 'numerical', 'integerOnly'=>true)
		);
	}

	public function i18nFields(){
		return array(
			'name' => 'varchar(255) not null',
		);
	}
	
	public function relations(){
		$relations['city'] = array(self::BELONGS_TO, 'ApartmentCity', 'city_id');
		
		if (issetModule('location'))
			$relations['city'] = array(self::BELONGS_TO, 'City', 'loc_city');
		
		return $relations;
	}

	public function attributeLabels() {
		return array(
			'id' => 'ID',
			'name' => tc('Name'),
			'multy' => tc('Name'),
			'loc_city' => tc('City'),
			'city_id' => tc('City'),
		);
	}

	public function search() {
		$attributeName = (issetModule('location')) ? 'loc_city' : 'city_id';
		
		$criteria=new CDbCriteria;
		$tmp = 'name_'.Yii::app()->language;
		$criteria->compare($tmp, $this->$tmp, true);	
		$criteria->compare("{$attributeName}", $this->{$attributeName});
		$criteria->compare("active", $this->active);
		$criteria->order = 'sorter ASC';
		
		if ($this->city_id || $this->loc_city) {			
			$this->minSorter = Yii::app()->db->createCommand()
				->select('MIN(sorter) as minSorter')
				->from($this->tableName())
				->where($attributeName.'=:id', array(':id'=>$this->{$attributeName}))
				->queryScalar();
			$this->maxSorter = Yii::app()->db->createCommand()
				->select('MAX(sorter) as maxSorter')
				->from($this->tableName())
				->where($attributeName.'=:id', array(':id'=>$this->{$attributeName}))
				->queryScalar();
		}

		return new CustomActiveDataProvider($this, array(
			'criteria' => $criteria,
			'pagination' => array(
				'pageSize' => param('adminPaginationPageSize', 20),
			),
		));
	}

	public function beforeDelete(){
		$sql = 'DELETE FROM {{apartment_metro_stations}} WHERE metro_id="' . $this->id . '"';
		Yii::app()->db->createCommand($sql)->execute();
		
		return parent::beforeDelete();
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

	public function getName(){
		return $this->getStrByLang('name');
	}

	public function setName($value){
		$this->setStrByLang('name', $value);
	}
	
	public function getCityNameValue() {		
		return (isset($this->city)) ? $this->city->name : "";
	}
	
	public static function getCitiesList() {
		if (issetModule('location')) {
			$sql = 'SELECT lc.id, lc.name_'.Yii::app()->language.' as name FROM {{location_city}} lc '
				. ' INNER JOIN {{metro_stations}} ms ON ms.loc_city = lc.id '
				. ' GROUP BY ms.loc_city';
		}
		else {
			$sql = 'SELECT ac.id, ac.name_'.Yii::app()->language.' as name FROM {{apartment_city}} ac '
				. ' INNER JOIN {{metro_stations}} ms ON ms.city_id = ac.id '
				. ' GROUP BY ms.city_id';
		}
		
		$res = Yii::app()->db->createCommand($sql)->queryAll();
		if ($res)
			return CHtml::listData ($res, 'id', 'name');
		
		return null;	
	}
	
	public static function getMetrosArray($city = 0, $type=0, $all=0, $onlyWithAds = false){
		if (is_array($city)) {
			if (!count($city))
				$city = 0;
			elseif (isset($city[0]))
				$city = $city[0];
		}
		
		$join = '';
		$activeStr = ($all) ? '' : 'AND ms.active = 1 ';
		$groupByStr = '';

		if ($onlyWithAds) {
			if (issetModule('location')) {
				$join .= ' INNER JOIN {{apartment}} ap on ms.loc_city = ap.loc_city ';
				$join .= ' INNER JOIN {{apartment_metro_stations}} ams on ams.metro_id = ms.id ';
			}
			else {
				$join .= ' INNER JOIN {{apartment}} ap on ms.city_id = ap.city_id ';
				$join .= ' INNER JOIN {{apartment_metro_stations}} ams on ams.metro_id = ms.id ';
			}
			
			$ownerActiveCond = (param('useUserads')) ? ' AND ap.owner_active = '.Apartment::STATUS_ACTIVE : '';

			$addWhere = ' ap.price_type IN ('.implode(',', array_keys(HApartment::getPriceArray(Apartment::PRICE_SALE, true))).') AND ap.active = '.Apartment::STATUS_ACTIVE.' '.$ownerActiveCond.'';
			$activeStr = ($activeStr) ? ' AND '.$addWhere : ' WHERE '.$addWhere;
			$groupByStr = ' GROUP BY ms.id ';
		}
		
		if (issetModule('location'))
			$sql = 'SELECT ms.id, ms.name_'.Yii::app()->language.' AS name FROM {{metro_stations}} ms '.$join.' WHERE ms.loc_city = :cityid '.$activeStr.' '.$groupByStr.' ORDER BY ms.sorter ASC';
		else
			$sql = 'SELECT ms.id, ms.name_'.Yii::app()->language.' AS name FROM {{metro_stations}} ms '.$join.' WHERE ms.city_id = :cityid '.$activeStr.' '.$groupByStr.' ORDER BY ms.sorter ASC';
				
		$res = Yii::app()->db->createCommand($sql)->queryAll(true, array(':cityid' => $city));
		$res = CHtml::listData($res, 'id', 'name');

		switch ($type) {
			case 1:
				$metros = CArray::merge(array(0 => ''), $res);
				break;
			case 2:
				$metros = CArray::merge(array(0 => tt('Select metro stations', 'metroStations')), $res);
				break;
			case 3:
				$metros = CArray::merge(array(0 =>  tt('Not selected', 'metroStations')), $res);
				break;
			default :
				$metros = $res;
		}

        return $metros;
    }
	
	public static function getMetroStations($apId = null){
		if ($apId) {
			$sql = 'SELECT metro_id FROM {{apartment_metro_stations}} WHERE apartment_id="'.$apId.'"';
			return Yii::app()->db->createCommand($sql)->queryColumn();
		}
		return null;
	}
	
	public static function setMetroStations($apId = null, $stations = null) {		
		if ($apId) {
			$sql = 'SELECT metro_id FROM {{apartment_metro_stations}} WHERE apartment_id="'.$apId.'"';
			$existsMetros = Yii::app()->db->createCommand($sql)->queryAll();
			if ($existsMetros)
				$existsMetros = CHtml::listData($existsMetros, 'metro_id', 'metro_id');
			if (!is_array($existsMetros)) $existsMetros = array();
		
			$sql = 'DELETE FROM {{apartment_metro_stations}} WHERE apartment_id="'.$apId.'"';
			Yii::app()->db->createCommand($sql)->execute();
		
			$newMetros = array();
			if(is_array($stations) && $stations){
				$values = array();
				foreach ($stations as $station) {
					$values[] = '(' . $station . ', ' . $apId . ')';
					
					$newMetros[$station] = $station;
				}

				if (count($values)) {
					$sql = 'INSERT INTO {{apartment_metro_stations}} (metro_id, apartment_id) VALUES ' . implode(',', $values);
					Yii::app()->db->createCommand($sql)->execute();
				}
			}
			
			if (issetModule('historyChanges')) {
				$diffArr = array_merge(array_diff_assoc($existsMetros, $newMetros),array_diff_assoc($newMetros, $existsMetros));				
				if (count($diffArr)) {
					HistoryChanges::addApartmentInfoToHistory('update_metro_stations', $apId, 'update', implode(',', $existsMetros), implode(',', $newMetros));
				}
			}
		}
	}
	
	public static function getApartmentsListByMetro($metro = null) {
		if (!$metro) return null;
		
		if (is_numeric($metro))
			$metro = array($metro);
		
		$apartmentIds = array();
		if($metro){
			$metro = array_map("intval", $metro);
			$sql = 'SELECT DISTINCT apartment_id FROM {{apartment_metro_stations}} WHERE metro_id IN ('.implode(',', $metro).')';

			$dependency = new CDbCacheDependency('
				SELECT MAX(val) FROM
					(SELECT MAX(date_updated) as val FROM {{apartment}}
					UNION
					SELECT MAX(date_updated) as val FROM {{apartment_metro_stations}}) as t
			');

			$apartmentIds = Yii::app()->db->cache(param('cachingTime', 1209600), $dependency)->createCommand($sql)->queryColumn();
		}
		return $apartmentIds;
	}
	
	public static function getApartmentStationsTitle($apId = null) {
		$return = null;
		if($apId){	
			$sql = 'SELECT ms.name_'.Yii::app()->language.' as title, ms.id as id
				FROM {{apartment_metro_stations}} ams, {{metro_stations}} ms
				WHERE ams.metro_id = ms.id AND ams.apartment_id = '.$apId;
			$dependency = new CDbCacheDependency('SELECT MAX(date_updated) FROM {{apartment_metro_stations}} WHERE apartment_id='.$apId);			
			$result = Yii::app()->db->cache(param('cachingTime', 1209600), $dependency)->createCommand($sql)->queryColumn();			
			
			if($result)
				$return = implode(', ', $result);
		}

		return $return;
	}

	public static function deleteApartmentStations($apId = null) {
		if ($apId) {
			$sql = 'DELETE FROM {{apartment_metro_stations}} WHERE apartment_id="' . $apId . '"';
			Yii::app()->db->createCommand($sql)->execute();
		}
	}

	public static function getDependency(){
		return new CDbCacheDependency('SELECT MAX(date_updated) FROM {{metro_stations}}');
	}
}