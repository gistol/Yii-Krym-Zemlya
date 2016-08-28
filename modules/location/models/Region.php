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

class Region extends ParentModel{
	public $minSorter = 0;
	public $maxSorter = 0;

	public static function model($className=__CLASS__){
		return parent::model($className);
	}

	public function tableName(){
		return '{{location_region}}';
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
			array('country_id', 'required'),
			array('country_id', 'numerical', 'integerOnly'=>true),
			array('name', 'i18nRequired'),
			array('name', 'i18nLength', 'max'=>128),
			array($this->getI18nFieldSafe(), 'safe'),
			array('active', 'safe', 'on'=>'search'),
			array('sorter', 'numerical', 'integerOnly'=>true)
		);
	}

	public function relations(){
		//Yii::app()->getModule('city');
		return array(
			'country' => array(self::BELONGS_TO, 'Country', 'country_id'),
			'cities' => array(self::HAS_MANY, 'City', 'region_id'),
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
			'country_id' => tc('Country'),
			'country' => tc('Country'),
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
		$criteria->compare('t.'.$tmp, $this->$tmp, true);
		$criteria->compare('t.active', $this->active);
		$criteria->compare('t.country_id', $this->country_id);
		$criteria->with = array('country');
		//$criteria->order = 'country.sorter ASC, t.sorter ASC';

		if ($this->country_id) {
			$this->minSorter = Yii::app()->db->createCommand()
				->select('MIN(sorter) as minSorter')
				->from($this->tableName())
				->where('country_id=:id', array(':id'=>$this->country_id))
				->queryScalar();
			$this->maxSorter = Yii::app()->db->createCommand()
				->select('MAX(sorter) as maxSorter')
				->from($this->tableName())
				->where('country_id=:id', array(':id'=>$this->country_id))
				->queryScalar();
		}

		return new CustomActiveDataProvider($this, array(
			'criteria'=>$criteria,
			'sort'=>array('defaultOrder'=>'country.sorter ASC, t.sorter ASC'),
			'pagination'=>array(
				'pageSize'=>param('adminPaginationPageSize', 20),
			),
		));
	}


	public function afterDelete(){
		$sql = 'DELETE FROM {{location_city}} WHERE region_id="'.$this->id.'";';
		Yii::app()->db->createCommand($sql)->execute();

		$sql = 'UPDATE {{apartment}} SET loc_region=0, loc_city=0 WHERE loc_region="'.$this->id.'"';
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

	public static function getRegionsArray($country, $type=0, $all=0, $onlyWithAds = false){

		$join = '';
		$active_str = ($all) ? '' : ' AND lr.active = 1 ';

		if ($onlyWithAds) {
			$join .= ' INNER JOIN {{apartment}} ap on lr.id = ap.loc_region';
			$ownerActiveCond = (param('useUserads')) ? ' AND ap.owner_active = '.Apartment::STATUS_ACTIVE : '';
			
			$addWhere = ' ap.price_type IN ('.implode(',', array_keys(HApartment::getPriceArray(Apartment::PRICE_SALE, true))).') AND ap.active = '.Apartment::STATUS_ACTIVE.' '.$ownerActiveCond.'';
			if ($type != 4) {
				$active_str = ' AND '.$addWhere;
			}
			else {
				$active_str = ($active_str) ? ' AND '.$addWhere : ' WHERE '.$addWhere;
			}
		}
		
		if ($type != 4) {
			$sql = 'SELECT lr.id, lr.name_'.Yii::app()->language.' AS name FROM {{location_region}} lr '.$join.' WHERE lr.country_id = :country '.$active_str.' ORDER BY lr.sorter ASC';
			$res = Yii::app()->db->createCommand($sql)->queryAll(true, array(':country' => $country));
		} 
		else {
			$sql = 'SELECT lr.id, lr.name_'.Yii::app()->language.' AS name FROM {{location_region}} lr '.$join.' '.$active_str.' ORDER BY lr.sorter ASC';
			$res = Yii::app()->db->createCommand($sql)->queryAll();
		}

		$res = CHtml::listData($res, 'id', 'name');

		switch ($type) {
			case 1:
			case 4:
				$regions = CArray::merge(array(0 => ''), $res);
				break;
			case 2:
				$regions = CArray::merge(array(0 => tc('select region')), $res);
				break;
			case 3:
				$regions = CArray::merge(array(0 => tc('Not specified_m')), $res);
				break;
			default :
				$regions = $res;
		}


        return $regions;
    }


}