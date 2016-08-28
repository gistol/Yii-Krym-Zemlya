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

class MainController extends ModuleUserController {
    public $canUseDirectUrl = false;

	public function actionAjaxSave() {
		$showBodyTextField = (int) Yii::app()->request->getParam('showBodyTextField');
		
		if(isset($_POST['SeoFriendlyUrl'])){
            $this->canUseDirectUrl = (int) Yii::app()->request->getPost('canUseDirectUrl');
			$scenario = Yii::app()->request->getParam('scenario');
			$fromSeoImageWidget = (int) Yii::app()->request->getParam('fromSeoImageWidget');

			$friendlyUrl = SeoFriendlyUrl::model()->findByPk($_POST['SeoFriendlyUrl']['id']);

			if(!$friendlyUrl){
				$friendlyUrl = new SeoFriendlyUrl();
			}
			
			if ($scenario) {
				$friendlyUrl->scenario = $scenario;
			}
			
			$friendlyUrl->attributes = $_POST['SeoFriendlyUrl'];
			
			$renderForm = ($fromSeoImageWidget) ? '_form_image' : '_form';
			
			if($friendlyUrl->save()){
				echo CJSON::encode(array(
					'status' => 'ok',
					'html' => $this->renderPartial('//modules/seo/views/'.$renderForm, array('friendlyUrl' => $friendlyUrl, 'showBodyTextField' => $showBodyTextField, 'afterRefresh' => true), true)
				));
				Yii::app()->end();
			}else{
				echo CJSON::encode(array(
					'status' => 'err',
					'html' => $this->renderPartial('//modules/seo/views/'.$renderForm, array('friendlyUrl' => $friendlyUrl, 'showBodyTextField' => $showBodyTextField, 'afterRefresh' => true), true)
				));
				Yii::app()->end();
			}
		}
		throw404();
	}
	
	public function actionViewSummaryInfo() {
		$cityUrlName = filter_var(Yii::app()->request->getParam('cityUrlName'), FILTER_SANITIZE_STRING);
		$objTypeUrlName = filter_var(Yii::app()->request->getParam('objTypeUrlName'), FILTER_SANITIZE_STRING);
		
		$bodyText = '';
		$cityId = $objTypeId = 0;
		$cityModel = $objTypeModel = null;
		$seoCity = $seoObjType = $widgetTitle = null;
		
		if ($cityUrlName) {
			$cityRoute = SeoFriendlyUrl::getActiveCityRoute();
			
			if (!empty($cityRoute)) {
				foreach($cityRoute as $cityId => $value) {
					foreach ($value as $lang => $val) {
						if ($val['url'] == $cityUrlName) {
							break 2;
						}
					}
				}
			}
			
			if ($cityId) {
				$modelName = (issetModule('location')) ? 'City' : 'ApartmentCity';
				$model = CActiveRecord::model($modelName);
				
				$cityModel = $model->findByPk($cityId);
				
				if ($cityModel) {
					Yii::app()->controller->selectedCity = $cityModel->id;
					
					$url = $cityUrlName;
					$addParams = array();
					
					$seoCity = SeoFriendlyUrl::getForView($url, $modelName, $addParams);
					if($seoCity){
						$this->setSeo($seoCity);
						$widgetTitle = $seoCity->getStrByLang('title');
						$bodyText = $seoCity->getStrByLang('body_text');
					}
					
					if ($objTypeUrlName) {
						$objTypeRoute = SeoFriendlyUrl::getActiveObjTypesRoute();
									
						if (!empty($objTypeRoute)) {
							foreach($objTypeRoute as $objTypeId => $value) {
								foreach ($value as $lang => $val) {
									if ($val['url'] == $objTypeUrlName) {
										break 2;
									}
								}
							}
						}
						
						$modelName = 'ApartmentObjType';
						$model = CActiveRecord::model($modelName);
						$objTypeModel = $model->findByPk($objTypeId);						
						if ($objTypeModel) {
							Yii::app()->controller->objType = $objTypeModel->id;			
							$url = $objTypeUrlName;
							
							$addParams['cityId'] = $cityId;
							
							$bodyText = '';
							$seoObjType = SeoFriendlyUrl::getForView($url, $modelName, $addParams);
							
							if($seoObjType){
								$allAttributes = $seoObjType->getAttributes();
								$safeAttributes = $seoObjType->getI18nFieldSafe();
											
								if (!empty($allAttributes) && $safeAttributes) {
									$safeAttributesArr = explode(',', $safeAttributes);
									$safeAttributesArr = array_map("trim", $safeAttributesArr);
										
									foreach($safeAttributesArr as $nameAttribute) {	
										if (isset($allAttributes[$nameAttribute])) {
											$seoObjType->setAttributes(array($nameAttribute => str_replace('{cityName}', $cityModel->getStrByLang('name'), $seoObjType->{$nameAttribute})));
										}
									}
								}
								
								$this->setSeo($seoObjType);
								$widgetTitle = $seoObjType->getStrByLang('title');
								$bodyText = $seoObjType->getStrByLang('body_text');
							}
						}
					}
					
					if ($page = Yii::app()->request->getParam('page')) {
						$widgetTitle .= ' - '. tt('Page', 'service'). ' '. (int) $page;
					}
					
					$criteria = new CDbCriteria;
					$criteria->addCondition('active = ' . Apartment::STATUS_ACTIVE);
					if(param('useUserads')) {
						$criteria->addCondition('owner_active = ' . Apartment::STATUS_ACTIVE);
					}
					
					if(issetModule('location')) {
						$criteria->addCondition('loc_city = '.$cityModel->id);
						Yii::app()->controller->selectedCountry = $cityModel->country_id;
						Yii::app()->controller->selectedRegion = $cityModel->region_id;
					}
					else {
						$criteria->addCondition('city_id = '.$cityModel->id);
					}
					
					if ($objTypeModel) {
						$criteria->addCondition('obj_type_id = '.$objTypeModel->id);
					}
					
					if(Yii::app()->request->isAjaxRequest) {
						$this->excludeJs();
						$this->renderPartial('//modules/seo/views/view_summary_info', array(
							'criteria' => $criteria,
							'bodyText' => $bodyText,
							'cityModel' => $cityModel,
							'objTypeModel' => $objTypeModel,
							'widgetTitle' => $widgetTitle,
							'seoCity' => $seoCity,
							'seoObjType' => $seoObjType,
							'cityUrlName' => $cityUrlName,
						), false, true);
					} 
					else {
						$this->render('//modules/seo/views/view_summary_info', array(
							'criteria' => $criteria,
							'bodyText' => $bodyText,
							'cityModel' => $cityModel,
							'objTypeModel' => $objTypeModel,
							'widgetTitle' => $widgetTitle,
							'seoCity' => $seoCity,
							'seoObjType' => $seoObjType,
							'cityUrlName' => $cityUrlName,
						));
					}
					Yii::app()->end();
				}
			}
		}
		throw404();
	}
}
