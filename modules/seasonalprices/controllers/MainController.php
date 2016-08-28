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
	public $modelName = 'Seasonalprices';

	public function actionSavePrice() {
		$msg = 'access_error';
		$msg_full = '';
		if (Yii::app()->request->isPostRequest && !Yii::app()->user->isGuest) {
			if (!isset($_POST) || !isset($_POST[$this->modelName])) {
				echo $msg;
				Yii::app()->end();
			}

			$apId = (int) Yii::app()->request->getParam('apId');

			if (($apId && Seasonalprices::isUserAd($apId, Yii::app()->user->id)) ||
				($apId && Yii::app()->user->checkAccess('backend_access'))) {

				$model = new $this->modelName;
				$model->attributes = $_POST[$this->modelName];
				$model->in_currency = issetModule('currency') ? $_POST[$this->modelName]['in_currency'] : '';

				$dateStartFormattingArr = explode('-', $_POST[$this->modelName]['date_start_formatting']);
				if ($dateStartFormattingArr) {
					$dateStartFormattingArr = array_map('intval', $dateStartFormattingArr);
					if (count($dateStartFormattingArr) == 2) {
						$model->date_start = $dateStartFormattingArr[0];
						$model->month_start = $dateStartFormattingArr[1];
					}
				}

				$dateEndFormattingArr = explode('-', $_POST[$this->modelName]['date_end_formatting']);
				if ($dateEndFormattingArr) {
					$dateEndFormattingArr = array_map('intval', $dateEndFormattingArr);
					if (count($dateEndFormattingArr) == 2) {
						$model->date_end = $dateEndFormattingArr[0];
						$model->month_end = $dateEndFormattingArr[1];
					}
				}
				$model->apartment_id = $apId;
				if ($model->validate()) {
					if ($model->save(false)) {
						$msg = 'ok';
					}
					else {
						$msg = 'error_save';
						$msg_full = ($model->hasErrors()) ? CHtml::errorSummary($model) : '';
					}
				}
				else {
					$msg = 'error_filling';
					$msg_full = ($model->hasErrors()) ? CHtml::errorSummary($model) : '';
				}
			}
			else {
				$msg = 'access_error';
			}
		}

		echo CJSON::encode(array(
			'msg' => $msg,
			'msg_full' => $msg_full,
		));
		Yii::app()->end();
	}

	public function actionDeletePrice($id, $apId) {
		$msg = 'access_error';
		if (Yii::app()->request->isAjaxRequest && !Yii::app()->user->isGuest) {
			if ($id && $apId) {
				if (($apId && Seasonalprices::isUserAd($apId, Yii::app()->user->id)) ||
				($apId && Yii::app()->user->checkAccess('backend_access'))) {

					$sql = 'DELETE FROM {{seasonal_prices}} WHERE apartment_id="'.$apId.'" AND id = "'.$id.'"';
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
	
	public function actionMove(){
		if(Yii::app()->request->isAjaxRequest){
			if(isset($_GET['id']) && isset($_GET['direction'])){
				$id = (int) (int) Yii::app()->request->getQuery('id');		
				$objectId = (int) Yii::app()->request->getQuery('objectid');

				$model = $this->loadModel($id);

				if (($objectId && Seasonalprices::isUserAd($objectId, Yii::app()->user->id)) || ($objectId && Yii::app()->user->checkAccess('backend_access'))) {
					$direction = isset($_GET['direction']) ? $_GET['direction'] : '' ;

					if($model && ($direction == 'up' || $direction == 'down') ){
						$sorter = $model->sorter;

						if($direction == 'up'){
							if($sorter > 1){
								if($direction == 'up') {
									$sql = 'UPDATE '.$model->tableName().' SET sorter="'.$sorter.'" WHERE sorter < "'.($sorter).'" AND apartment_id = "'.$objectId.'" ORDER BY sorter DESC LIMIT 1';
									Yii::app()->db->createCommand($sql)->execute();
									$model->sorter--;
								} 
								$model->update('sorter');
							}
						}
						if($direction == 'down'){
							$maxSorter = Seasonalprices::getMaxSorters($objectId);

							if($sorter < $maxSorter){
								if ($direction == 'down') {
									$sql = 'UPDATE '.$model->tableName().' SET sorter="'.$sorter.'" WHERE sorter > "'.($sorter).'" AND apartment_id = "'.$objectId.'" ORDER BY sorter ASC LIMIT 1';
									Yii::app()->db->createCommand($sql)->execute();
									$model->sorter++;
								}

								$model->update('sorter');
							}
						}
					}
				}
			}
		}
	}
	
	public function actionUpdate($id){
		if (Yii::app()->user->checkAccess('backend_access')) {
			$this->layout='//layouts/admin';
		}
		else {
			$this->layout='//layouts/usercpanel';
		}
				
		$seasonalPricesModel = $this->loadModel($id);
		
		if (($seasonalPricesModel && Seasonalprices::isUserAd($seasonalPricesModel->apartment_id, Yii::app()->user->id)) ||
				($seasonalPricesModel && Yii::app()->user->checkAccess('backend_access'))) {
			
			$apartment = Apartment::model()->findByPk($seasonalPricesModel->apartment_id);
			if (!$apartment) {
				throw404();
			}
			
			# with lead zero
			$seasonalPricesModel->date_start_formatting = sprintf("%02d", $seasonalPricesModel->date_start).'-'.sprintf("%02d", $seasonalPricesModel->month_start);
			$seasonalPricesModel->date_end_formatting = sprintf("%02d", $seasonalPricesModel->date_end).'-'.sprintf("%02d", $seasonalPricesModel->month_end);
			
			//$seasonalPricesModel->dateStart = Yii::app()->dateFormatter->format('dd, MMMM', CDateTimeParser::parse($seasonalPricesModel->date_start.'-'.$seasonalPricesModel->month_start, 'd-M'));
			//$seasonalPricesModel->dateEnd = Yii::app()->dateFormatter->format('dd, MMMM', CDateTimeParser::parse($seasonalPricesModel->date_end.'-'.$seasonalPricesModel->month_end, 'd-M'));
						
			$datepickerDateStart = date('Y').'-'.sprintf("%02d", $seasonalPricesModel->month_start).'-'.sprintf("%02d", $seasonalPricesModel->date_start);
			$datepickerDateEnd = date('Y').'-'.sprintf("%02d", $seasonalPricesModel->month_end).'-'.sprintf("%02d", $seasonalPricesModel->date_end);
			
			if(isset($_POST[$this->modelName])){
				$seasonalPricesModel->attributes = $_POST[$this->modelName];
				$seasonalPricesModel->in_currency = issetModule('currency') ? $_POST[$this->modelName]['in_currency'] : '';
				
				$dateStartFormattingArr = explode('-', $_POST[$this->modelName]['date_start_formatting']);
				if ($dateStartFormattingArr) {
					$dateStartFormattingArr = array_map('intval', $dateStartFormattingArr);
					if (count($dateStartFormattingArr) == 2) {
						$seasonalPricesModel->date_start = $dateStartFormattingArr[0];
						$seasonalPricesModel->month_start = $dateStartFormattingArr[1];
					}
				}

				$dateEndFormattingArr = explode('-', $_POST[$this->modelName]['date_end_formatting']);
				if ($dateEndFormattingArr) {
					$dateEndFormattingArr = array_map('intval', $dateEndFormattingArr);
					if (count($dateEndFormattingArr) == 2) {
						$seasonalPricesModel->date_end = $dateEndFormattingArr[0];
						$seasonalPricesModel->month_end = $dateEndFormattingArr[1];
					}
				}
				
				if ($seasonalPricesModel->save()) {
					Yii::app()->user->setFlash('success', tc('Success'));
					if (Yii::app()->user->checkAccess('backend_access')) {
						$this->redirect(array('/apartments/backend/main/update', 'id' => $seasonalPricesModel->apartment_id));
					}
					else {
						$this->redirect(array('/userads/main/update', 'id' => $seasonalPricesModel->apartment_id));
					}
				}
			}
			
			$this->render('update', array(
				'seasonalPricesModel' => $seasonalPricesModel,
				'apartment' => $apartment,
				'datepickerDateStart' => $datepickerDateStart,
				'datepickerDateEnd' => $datepickerDateEnd,
				'setDatepickerDate' => true,
			));
		}
		else {
			throw404();
		}
	}
}