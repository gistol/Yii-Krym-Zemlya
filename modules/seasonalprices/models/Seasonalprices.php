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

class Seasonalprices extends ParentModel {
	public $in_currency;

	public $date_start_formatting;
	public $date_end_formatting;

	public $dateStart;
	public $dateEnd;
	
	public $min_rental_period_with_type;

	public $priceWithType;
	
	public static function model($className=__CLASS__) {
		return parent::model($className);
	}

	public function tableName() {
		return '{{seasonal_prices}}';
	}

	public function currencyFields(){
		return array('price');
	}

	public function rules() {
		return array(
			array('price, date_start, month_start, date_end, month_end, price_type', 'required'),
			array('date_start_formatting, date_end_formatting', 'date', 'format'=>'d-m', 'allowEmpty'=>false, 'message' => tt('Wrong format of field {attribute}', 'seasonalprices')),
			array('name', 'i18nRequired'),
			array('name', 'i18nLength', 'max' => 255),
			array('apartment_id', 'required', 'on'=>'insert'),
			array('price_type', 'numerical', 'min' => 1),
			array('min_rental_period, sorter', 'numerical'),
			array($this->getI18nFieldSafe(), 'safe'),
			array('dateStart, dateEnd', 'safe'),
			array('date_end', 'validDate'),
			array('id, date_start, date_end, min_rental_period, apartment_id', 'safe', 'on' => 'search'),
		);
	}

	public function i18nFields() {
		return array(
			'name' => 'varchar(255) not null',
		);
	}

	public function behaviors(){
		return array(
			'AutoTimestampBehavior' => array(
				'class' => 'zii.behaviors.CTimestampBehavior',
				'createAttribute' => 'date_created',
				'updateAttribute' => 'date_updated',
			),
		);
	}

	public function attributeLabels() {
		return array(
			'id' => 'Id',
			'name' => tt('Name', 'seasonalprices'),
			'price' => tt('Price', 'seasonalprices'),
			'price_type' => tt('Price_type', 'seasonalprices'),
			'date_start' => tt('From', 'seasonalprices'),
			'date_end' => tt('To', 'seasonalprices'),
			'min_rental_period' => tt('Min_rental_period', 'seasonalprices'),
			'dateStart' => tt('From', 'seasonalprices'),
			'dateEnd' => tt('To', 'seasonalprices'),
			'date_start_formatting' => tt('From', 'seasonalprices'),
			'date_end_formatting' => tt('To', 'seasonalprices'),
		);
	}

	/**
	 * Find overlap days in seasons
	 */
	public function validDate($attribute, $params) {				
		$year = 2015;
		$seasonStart = DateTime::createFromFormat('d-m-Y', $this->date_start.'-'.$this->month_start.'-'.$year);
		$seasonEnd = DateTime::createFromFormat('d-m-Y', $this->date_end.'-'.$this->month_end.'-'.$year);

		$addWhere = '';
		if(!$this->isNewRecord){
			$addWhere = ' AND id != '.$this->id.' ';
		}
		
		$priceRows = Yii::app()->db
			->createCommand("SELECT name_".Yii::app()->language." AS name, month_start, date_start, month_end, date_end, price FROM {{seasonal_prices}}
					WHERE price_type = :t AND apartment_id=:id {$addWhere} ORDER BY price ASC")
			->queryAll(true, array(
				':t' => $this->price_type,
				':id' => $this->apartment_id,
			));	
					
		if (!empty($priceRows)) {
			foreach($priceRows as $row){
				$seasonStart2 = DateTime::createFromFormat('d-m-Y', $row['date_start'].'-'.$row['month_start'].'-'.$year);
				$seasonEnd2 = DateTime::createFromFormat('d-m-Y', $row['date_end'].'-'.$row['month_end'].'-'.$year);

				$daysOverlap = HBooking::datesOverlap($seasonStart2, $seasonEnd2, $seasonStart, $seasonEnd, 0);
				if($daysOverlap){
					$this->addError('date_end', tt('For these days, the season has already exhibited price', 'seasonalprices'));
					break;
				}
			}
		}
	}

	public function beforeSave() {
		if (issetModule('currency') && isset($this->in_currency) && $this->in_currency) {
			$defaultCurrencyCharCode = Currency::getDefaultCurrencyModel()->char_code;
			if ($defaultCurrencyCharCode != $this->in_currency) {
				$this->price = (int)Currency::convert($this->price, $this->in_currency, $defaultCurrencyCharCode);
			}
		}
		
		if($this->isNewRecord) {
			$sql = 'SELECT MAX(sorter) FROM '.$this->tableName().' WHERE apartment_id = "'.$this->apartment_id.'"';
			$maxSorter = Yii::app()->db
				->createCommand($sql)
				->queryScalar();
			$this->sorter = $maxSorter+1;
		}

		return parent::beforeSave();
	}

	public function afterFind() {
		$this->priceWithType = $this->dateStart = $this->dateEnd  = '-';
		$currencyName = (issetModule('currency')) ? Currency::getCurrentCurrencyName() : param('siteCurrency', '$');

		if ($this->price && is_numeric($this->price)) {
			$price = round(issetModule('currency') ? Currency::convertFromDefault($this->price) : $this->price, param('round_price', 2));
			$this->priceWithType = Apartment::setPrePretty($price).' '.$currencyName.' ('.Seasonalprices::rentalPeriodNames($this->price_type).')';
		}

		$dateFormat = Yii::app()->locale->getDateFormat('long');
		if (($strpos=mb_strpos($dateFormat,'y'))!==false)
			$dateFormat = mb_substr($dateFormat, 0, $strpos,'utf-8');
		
		if ($this->date_start && $this->month_start)
			$this->dateStart = Yii::app()->dateFormatter->format($dateFormat, CDateTimeParser::parse($this->date_start.'-'.$this->month_start, 'd-M'));

		if ($this->date_end && $this->month_end)
			$this->dateEnd = Yii::app()->dateFormatter->format($dateFormat, CDateTimeParser::parse($this->date_end.'-'.$this->month_end, 'd-M'));

		$this->min_rental_period_with_type = (!$this->min_rental_period) ? '-' : $this->min_rental_period.' '.Seasonalprices::rentalPeriodNames($this->price_type);

		parent::afterFind();
	}

	public function search(){
		$criteria = new CDbCriteria;
		$criteria->order = 'id DESC';

		$criteria->compare('id', $this->id, true);
		$criteria->compare('apartment_id', $this->apartment_id, true);
		$criteria->order = 'sorter ASC';

		return new CActiveDataProvider($this, array(
			'criteria'=>$criteria,
			'pagination'=>array(
				'pageSize'=>param('adminPaginationPageSize', 20),
			),
		));
	}

	public static function isUserAd($apartmentId = null, $ownerId = null) {
		if ($apartmentId && $ownerId) {
			if (Apartment::model()->findByAttributes(array('id' => $apartmentId, 'owner_id' => $ownerId))) {
				return true;
			}
		}
		return false;
	}

	public static function rentalPeriodNames($period = null) {
		$array = array();

		$array[Apartment::PRICE_PER_HOUR] = tt('hour', 'seasonalprices');
		$array[Apartment::PRICE_PER_DAY] = tt('day', 'seasonalprices');
		$array[Apartment::PRICE_PER_WEEK] = tt('week', 'seasonalprices');
		$array[Apartment::PRICE_PER_MONTH] = tt('month', 'seasonalprices');

		if (is_numeric($period)) {
			if (array_key_exists($period, $array))
				return $array[$period];
			elseif (array_key_exists($period, HApartment::getPriceArray(NULL, true)))
				return HApartment::getPriceName($period);
			else return '-';
		}

		return $array;
	}
	
	public static function getMinSorters($apartmentId) {
		$sql = 'SELECT MIN(sorter) FROM {{seasonal_prices}} WHERE apartment_id = '.(int) $apartmentId;
		return (int) Yii::app()->db->createCommand($sql)->queryScalar();
	}
	
	public static function getMaxSorters($apartmentId) {
		$sql = 'SELECT MAX(sorter) FROM {{seasonal_prices}} WHERE apartment_id = '.(int) $apartmentId;
		return (int) Yii::app()->db->createCommand($sql)->queryScalar();
	}
}
