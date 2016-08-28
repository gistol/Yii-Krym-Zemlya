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

class SeosummaryinfoWidget extends CWidget {
	public function getViewPath($checkTheme=true){
		if($checkTheme && ($theme=Yii::app()->getTheme())!==null){
			if (is_dir($theme->getViewPath().DIRECTORY_SEPARATOR.'modules'.DIRECTORY_SEPARATOR.'seo'.DIRECTORY_SEPARATOR.'views'))
				return $theme->getViewPath().DIRECTORY_SEPARATOR.'modules'.DIRECTORY_SEPARATOR.'seo'.DIRECTORY_SEPARATOR.'views';
		}
		return Yii::getPathOfAlias('application.modules.seo.views');
	}
	
	public function run() {	
		$citiesListResult = $objTypesListResult = $resCounts = array();
		if (issetModule('seo')) {
			$citiesListResult = SeoFriendlyUrl::getActiveCityRoute();
			$objTypesListResult = SeoFriendlyUrl::getActiveObjTypesRoute();
			$resCounts = SeoFriendlyUrl::getCountApartmentsForCategories();
		}
				
		$countApartmentsByCategories = array();
		if (!empty($resCounts)) {
			foreach($resCounts as $values) {
				$countApartmentsByCategories[$values['city']][$values['obj_type_id']] = $values['count'];
			}
		}
		unset($resCounts);
						
		$this->render('widgetSeosummaryinfo', 
			array(
				'citiesListResult' => $citiesListResult, 
				'objTypesListResult' => $objTypesListResult,
				'countApartmentsByCategories' => $countApartmentsByCategories,
			)
		);
	}
}