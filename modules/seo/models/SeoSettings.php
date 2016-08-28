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


class SeoSettings
{
    public $keys = array('siteName', 'siteKeywords', 'siteDescription');
    public $keysLabel = array();
    public $models = array();

    public function __construct()
    {
        $criteria = new CDbCriteria();
        $criteria->compare('category', 'module_seo');
        $criteria->compare('message', $this->keys);
        $this->models = TranslateMessage::model()->findAll($criteria);

        $this->keysLabel = array(
            'siteName' => tt('siteName_label'),
            'siteKeywords' => tt('siteKeywords_label'),
            'siteDescription' => tt('siteDescription_label'),
        );
    }

    public function getLabel($key)
    {
        return isset($this->keysLabel[$key]) ? $this->keysLabel[$key] : null;
    }
}