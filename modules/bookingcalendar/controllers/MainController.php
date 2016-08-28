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

class MainController extends ModuleUserController{
	public $modelName = 'Bookingcalendar';

	public function actionAddFieldBooking($element) {
		if (Yii::app()->request->isAjaxRequest && !Yii::app()->user->isGuest) {
			$apartment = new Apartment;
			$model = new $this->modelName;

			$this->excludeJs();

			$this->renderPartial('_booking_period', array(
				'apartment' => $apartment,
				'model' => $model,
				'element' => $element,
			), false, true);
		}
		return false;
	}

	public function actionSaveBooking($dateStart, $dateEnd, $dateStatus, $apId) {
		$msg = 'access_error';
		if (Yii::app()->request->isAjaxRequest && !Yii::app()->user->isGuest) {
			if (($apId && Bookingcalendar::isUserAd($apId, Yii::app()->user->id)) ||
				($apId && Yii::app()->user->checkAccess('backend_access'))) {

				$model = new $this->modelName;

				$model->date_start = $dateStart;
				$model->date_end = $dateEnd;
				$model->status = $dateStatus;
				$model->apartment_id = $apId;

				if ($model->validate()) {
					if ($model->save(false)) {
						$msg = 'ok';
						/*if (Yii::app()->user->checkAccess('backend_access')){
							//$msg = Yii::app()->controller->createUrl("/apartments/backend/main/update", array("id" => $apId));
						} else {
							//$msg = Yii::app()->controller->createUrl("/userads/main/update", array("id" => $apId));
						}*/
					}
					else {
						$msg = 'error_save';
					}
				}
				else {
					$msg = 'error_filling';
				}
			}
			else {
				$msg = 'access_error';
			}
		}
		echo $msg;
	}

	public function actionEditBooking($dateStart, $dateEnd, $dateStatus, $apId, $idDb) {
		$msg = 'access_error';
		if (Yii::app()->request->isAjaxRequest && !Yii::app()->user->isGuest) {
			if ($idDb && $apId) {
				if (($apId && Bookingcalendar::isUserAd($apId, Yii::app()->user->id)) ||
				($apId && Yii::app()->user->checkAccess('backend_access'))) {

					$model = Bookingcalendar::model()->findByPk($idDb);
					if ($model) {
						$model->date_start = $dateStart;
						$model->date_end = $dateEnd;
						$model->status = $dateStatus;

						if ($model->save()) {
								$msg = 'ok';
						}
						else {
							$msg = 'error_filling';
						}
					} else
						$msg = 'error_save';
				}
			}
		}
		echo $msg;
	}

	public function actionDeleteBooking($idDb, $apId) {
		$msg = 'access_error';
		if (Yii::app()->request->isAjaxRequest && !Yii::app()->user->isGuest) {
			if ($idDb && $apId) {
				if (($apId && Bookingcalendar::isUserAd($apId, Yii::app()->user->id)) ||
				($apId && Yii::app()->user->checkAccess('backend_access'))) {
					$sql = 'DELETE FROM {{booking_calendar}} WHERE apartment_id="'.$apId.'" AND id = "'.$idDb.'"';
					if (Yii::app()->db->createCommand($sql)->execute())
						$msg = 'ok';
					else
						$msg = 'error';
				}
				else {
					$msg = 'access_error';
				}
			}
			else {
				$msg = 'access_error';
			}
		}
		echo $msg;
	}
	
	public function actionGetJsonDataFullCalendar() {
		if (Yii::app()->request->isAjaxRequest) {	
			$return = array();
			$data = filter_var_array($_GET, FILTER_SANITIZE_STRING); 	
			
			$start = (isset($data['start'])) ? $data['start'] : '';
			$end = (isset($data['end'])) ? $data['end'] : '';
			
			if (!$start) {
				$start = date('Y-m-01', strtotime(date('Y-m-d')));
			}
			
			if (!$end) {
				$end = date('Y-m-t', strtotime(date('Y-m-d')));
			}
			
			$res = Yii::app()->db->createCommand()
					->select('ap.id, bc.date_start, bc.date_end, ap.title_'.Yii::app()->language.' as ap_title, apti.title_'.Yii::app()->language.' as timeInTitle, apto.title_'.Yii::app()->language.' as timeOutTitle, bt.time_in, bt.time_out')
					->from('{{booking_calendar}} bc')
					->join('{{booking_table}} bt', ' bc.booking_id = bt.id')
					->join('{{apartment}} ap', 'ap.id = bc.apartment_id')
					->join('{{apartment_times_in}} apti', 'apti.id = bt.time_in')
					->join('{{apartment_times_out}} apto', 'apto.id = bt.time_out')
					->where('bc.status = "'.  Bookingcalendar::STATUS_BUSY.'" AND (DATE("'.$start.'") <= bc.date_end AND DATE("'.$end.'") >= bc.date_start)')
					->queryAll();
			
			if (!empty($res)) {
				if(param('booking_half_day')){
					$timeList = Booking::getTimeList();
				}

				foreach($res as $items) {
					//$item['id'] = $items['id'];
					$title = $items['ap_title'];

					if(param('booking_half_day')){
						$timeInTitle = Booking::getTimeList(null, $items['time_in']);
						$timeOutTitle = Booking::getTimeList(null, $items['time_out']);
					} else {
						$timeInTitle = $items['timeInTitle'];
						$timeOutTitle = $items['timeOutTitle'];
					}

					$title .= ' :: '.$items['date_start'].'('.$timeInTitle.')'.' - '.$items['date_end'].'('.$timeOutTitle.') ::';
					
					$item['title'] = $title;
					
					$item['url'] = Apartment::getUrlById($items['id']);
					$item['start'] = $items['date_start'];
					
					// +1 day for fullcalendar script
					$dateEnd = DateTime::createFromFormat('Y-m-d', $items['date_end']);
					$dateEnd->modify('+1 day');
					
					$item['end'] = $dateEnd->format('Y-m-d');
					
					$item['color'] = '#ffbec3';
					$item['textColor'] = '#000000';
					
					$return[] = $item;
				}
			}
			
			echo CJSON::encode($return);
		}
	}
}