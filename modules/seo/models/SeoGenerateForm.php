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


class SeoGenerateForm extends CFormModel
{
    public $forModels;

    public $regenOld;

    public function rules()
    {
        return array(
            array('regenOld, forModels', 'safe'),
            array('forModels', 'valid'),
        );
    }

    public function valid()
    {
        if(!$this->forModels){
            $this->addError('forModels', tt('Please, indicate the section(s)'));
            return false;
        }

        $models = SeoFriendlyUrl::getModelNameList();
        $models = array_keys($models);

        foreach($models as $modelName){
            if(!in_array($modelName, $this->forModels)){
                continue;
            }

            $modelAll = $modelName::model()->findAll();
            foreach($modelAll as $model){
                if($model && $model instanceof ParentModel){
                    SeoFriendlyUrl::getAndCreateForModel($model, $this->regenOld);
                }
            }
        }
    }

    public function attributeLabels()
    {
        return array(
            'regenOld' => tt('Delete old urls and metadata'),
            'forModels' => tt('For sections'),
        );
    }
}