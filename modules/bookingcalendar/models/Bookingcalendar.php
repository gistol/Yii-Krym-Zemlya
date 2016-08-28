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

class Bookingcalendar extends ParentModel {
	const STATUS_BUSY = 1;
	const STATUS_FREE = 0;

	private static $_statuses_arr;

	public static $extremeDays = array();

	public $dateStart = array();
	public $dateEnd = array();
	public $status = array();

	public $dateStartDb = array();
	public $dateEndDb = array();
	public $statusDb = array();

	/*public function init() {
		$this->publishAssets();
	}*/

	public static function publishAssets() {
		$assetsPath = Yii::getPathOfAlias('webroot.themes.'.Yii::app()->theme->name . '.views.modules.bookingcalendar.assets');
		if (is_dir($assetsPath)) {
			$baseUrl = Yii::app()->assetManager->publish($assetsPath);
			Yii::app()->clientScript->registerCssFile($baseUrl . '/css/booking-calendar.css');
		}
	}

	public static function model($className=__CLASS__) {
		return parent::model($className);
	}

	public function tableName() {
		return '{{booking_calendar}}';
	}

	public function rules() {
		return array(
			array('date_start, date_end', 'required'),
			array('apartment_id', 'required', 'on'=>'insert'),
			array('status', 'numerical', 'min' => 1),
			array('booking_id', 'numerical', 'integerOnly' => true),
		);
	}

	public function relations() {
		return array(
			'bookingRequest' => array(self::BELONGS_TO, 'Bookingtable', 'booking_id')
		);
	}
	public function behaviors() {
		$arr = array();
		$arr['AutoTimestampBehavior'] = array(
			'class' => 'zii.behaviors.CTimestampBehavior',
			'createAttribute' => 'date_created',
			'updateAttribute' => 'date_updated',
		);
		if (issetModule('historyChanges')) {
			$arr['ArLogBehavior'] = array(
				'class' => 'application.modules.historyChanges.components.ArLogBehavior',
			);
		}

		return $arr;
	}

	public function attributeLabels() {
		return array(
			'id' => 'Id',
			'date_start' => tt('From', 'bookingcalendar'),
			'date_end' => tt('To', 'bookingcalendar'),
			'status' => tt('Status', 'bookingcalendar'),
			'dateStart' => tt('from', 'bookingcalendar'),
			'dateEnd' => tt('to', 'bookingcalendar'),
		);
	}

	public static function getAllStatuses(){
		return array(
			self::STATUS_BUSY => tt('Reserved', 'bookingcalendar'),
		);
    }

	public static function getStatus($status){
        if(!isset(self::$_statuses_arr)){
            self::$_statuses_arr = self::getAllStatuses(NULL, true);
        }
        return self::$_statuses_arr[$status];
    }

	public function search(){
		$criteria = new CDbCriteria;
		$criteria->order = 'id DESC';

		$criteria->compare('apartment_id', $this->apartment_id, true);

		return new CActiveDataProvider($this, array(
			'criteria'=>$criteria,
			'pagination'=>array(
				'pageSize'=>param('adminPaginationPageSize', 20),
			),
		));
	}

	public static function isUserAd($apartmentId = null, $ownerId = null) {
		if ($apartmentId && $ownerId) {
			if (Apartment::model()->findByAttributes(array('id' => $apartmentId, 'owner_id' => $ownerId)))
				return true;
			return false;

		}
		return false;
	}

	public static function getReservedDays($id) {
		$reservedDays = '[]';

		$id = intval($id);
		if ($id) {
			$resultTempArr = $resultArr = self::$extremeDays = array();

			$result = Yii::app()->db->createCommand()
				->select('t.date_start, t.date_end, b.time_in, b.time_out')
				->from('{{booking_calendar}} AS t')
				->leftJoin('{{booking_table}} AS b', 'b.id = booking_id')
				->where('t.apartment_id = :ap_id AND status = :status')
				->queryAll(true, array(
					':ap_id' => $id,
					':status' => self::STATUS_BUSY
				));

			if ($result && count($result) > 0) {
				foreach ($result as $item) {
					$resultTempArr[] = self::dateRange($item['date_start'], $item['date_end']);

					if(param('booking_half_day')) {
						// 1 начало, 2 конец, 3 середина
						if(isset($item['time_in'])){
							$k = date('Y-n-j', strtotime($item['date_start']));
							if(isset(self::$extremeDays[$k]) && self::$extremeDays[$k] == 2) {
								self::$extremeDays[$k] = 3;
							}elseif((isset(self::$extremeDays[$k]) && self::$extremeDays[$k] != 3) || empty(self::$extremeDays[$k]) && $item['time_in'] == Booking::TIME_AFTER_NOON){
								self::$extremeDays[$k] = 1;
							}
						}
						if(isset($item['time_out'])){
							$k = date('Y-n-j', strtotime($item['date_end']));
							if(isset(self::$extremeDays[$k]) && self::$extremeDays[$k] == 1) {
								self::$extremeDays[$k] = 3;
							}elseif((isset(self::$extremeDays[$k]) && self::$extremeDays[$k] != 3) || empty(self::$extremeDays[$k]) && $item['time_out'] == Booking::TIME_BEFORE_NOON){
								self::$extremeDays[$k] = 2;
							}
						}
					}
				}

				foreach ($resultTempArr as $key => $item) {
					if (is_array($item) && $item) {
						foreach ($item as $value) {
							$resultArr[] = str_replace('-', ', ',$value);
						}
					}
				}
				$resultArr = array_unique($resultArr);

				$total = count($resultArr);
				if ($total > 0) {
					$counter = 0;
					foreach ($resultArr as $value) {
						$counter++;
						if ($counter == 1) {
							// first element
							$reservedDays = '[';
						}
						if($counter == $total){
							// last element
							$reservedDays .= "[{$value}]]";
						}
						else{
							$reservedDays .= "[{$value}],";
						}
					}
				}
			}
		}

		return $reservedDays;
	}

	public static function dateRange($first, $last, $step = '+1 day', $format = 'Y-n-d' ) {
		$dates = array();
		$current = strtotime( $first );
		$last = strtotime( $last );

		while( $current <= $last ) {

			$dates[] = date( $format, $current );
			$current = strtotime( $step, $current );
		}

		return $dates;
	}

    public static function getFirstFreeDay($apartmentId, $currentTime = NULL){
        $currentTime = $currentTime ? $currentTime : time();

        $sql = "SELECT UNIX_TIMESTAMP(date_end) AS time_date_end
                FROM {{booking_calendar}}
                WHERE apartment_id=:id AND date_end >= :date AND date_start <= :date AND status=:status ORDER BY date_start ASC";
        $time = Yii::app()->db->createCommand($sql)->queryScalar(array(
            ':id' => $apartmentId,
            ':date' => date('Y-m-d', $currentTime),
            ':status' => self::STATUS_BUSY
        ));
        if(!$time){
            return $currentTime;
        }
        $time += 86400;
        return self::getFirstFreeDay($apartmentId, $time);
    }

	public static function addRecord(Bookingtable $model)
	{
		$modelBookingCalendar = Bookingcalendar::model()->findByAttributes(array(
			'date_start' => $model->date_start,
			'date_end' => $model->date_end,
			'status' => Bookingcalendar::STATUS_BUSY,
			'apartment_id' => $model->apartment_id,
			'booking_id' => $model->id
		));

		if(!$modelBookingCalendar){
			$modelBookingCalendar = new Bookingcalendar();
			$modelBookingCalendar->date_start = $model->date_start;
			$modelBookingCalendar->date_end = $model->date_end;
			$modelBookingCalendar->status = Bookingcalendar::STATUS_BUSY;
			$modelBookingCalendar->apartment_id = $model->apartment_id;
			$modelBookingCalendar->booking_id = $model->id;
			$modelBookingCalendar->save(false);
		}

		return $modelBookingCalendar;
	}
}
