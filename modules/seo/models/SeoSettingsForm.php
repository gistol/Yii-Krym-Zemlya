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

class SeoSettingsForm extends CFormModel
{
    // settings

    //public $seoPrefixAd;
    public $urlExtension;
    public $genFirendlyUrl;
    public $allowUserSeo;
    public $useSchemaOrgMarkup;

    public $configKeys = array('urlExtension', 'genFirendlyUrl', 'allowUserSeo', 'useSchemaOrgMarkup');

    public function init()
    {
        foreach($this->configKeys as $key){
            $this->{$key} = param($key);
        }
    }

    public function rules(){
        return array(
            array('genFirendlyUrl, urlExtension, allowUserSeo, useSchemaOrgMarkup', 'safe'),
        );
    }

    public function attributeLabels()
    {
        return array(
            'urlExtension' => tt('urlExtension', 'configuration'),
            'genFirendlyUrl' => tt('genFirendlyUrl', 'configuration'),
            'allowUserSeo' => tt('allowUserSeo', 'configuration'),
            'useSchemaOrgMarkup' => tt('useSchemaOrgMarkup', 'configuration')
        );
    }

    public function save()
    {
        $criteria = new CDbCriteria();
        $criteria->compare('section', 'seo');
        $criteria->compare('name', $this->configKeys);
        $models = ConfigurationModel::model()->findAll($criteria);
        foreach($models as $model){
            $key = $model->name;
            if($model->value != $this->{$key}){
                $model->value = $this->{$key};
                if(!$model->update(array('value'))){
                    $this->addError($key, $model->getError($key));
                }
            }
        }

        if($this->hasErrors()){
            return false;
        }

        return true;
    }

}