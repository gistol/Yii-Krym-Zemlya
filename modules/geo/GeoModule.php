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

/**
 * Class GeoModule
 * подставлять определенные geo данные в форму поиска - страну, страну и регион, страну, регион и город, не подставлять
 * фильтровать объявления на главной странице по geo данным - страну, страну и регион, страну, регион и город, не подставлять
 * подставлять определенные geo данные при добавлении объявлений - страну, страну и регион, страну, регион и город, не подставлять
 */

class GeoModule extends Module
{
    public function init()
    {
        Yii::import('application.modules.geo.components.SxGeo');

        //parent::init();
    }

    public function getGeoPath()
    {
        return Yii::getPathOfAlias('webroot.protected.modules.geo.components');
    }

    public function getGeoData($ip = '')
    {
        if(!$ip){
            return array();
        }

        $SxGeo = new SxGeo($this->getGeoPath().DIRECTORY_SEPARATOR.'SxGeoCity.dat');
        return $SxGeo->getCityFull($ip);

//        $SxGeo = new SxGeo('SxGeoCity.dat', SXGEO_BATCH | SXGEO_MEMORY); // Самый производительный режим, если нужно обработать много IP за раз
//        echo '<pre>';
//        var_export($SxGeo->getCityFull($ip)); // Вся информация о городе
//        echo '<hr>';
//        var_export($SxGeo->get($ip));         // Краткая информация о городе или код страны (если используется база SxGeo Country)
//        echo '<hr>';
//        var_export($SxGeo->about());          // Информация о базе данных
//        echo '</pre>';
//
//        exit;
    }
}