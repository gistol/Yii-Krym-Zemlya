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

class SearchController extends ModuleAdminController {
    public $modelName = 'SearchFormModel';

	public function accessRules(){
		return array(
			array('allow',
				'expression'=> "Yii::app()->user->checkAccess('all_modules_admin')",
			),
			array('deny',
				'users'=>array('*'),
			),
		);
	}

    public function actionEditSearchForm(){
        $this->render('editSearchForm');
    }

    public function actionLoadElement(){
        $elements = SearchForm::getSearchFields();
        $elementsString = '';
        $inForm = '';

        $objTypeId = Yii::app()->request->getParam('id');
        $elementsForm = SearchFormModel::model()->sort()->findAllByAttributes(array('obj_type_id' => $objTypeId), array('group' => 'field'));

        foreach($elementsForm as $el){
            $disabled = $el->status == SearchFormModel::STATUS_NOT_REMOVE ? ' ui-state-disabled' : '';

            $inForm .= '<li key="'.$el->field.'" class="ui-state-default'.$disabled.'">' . $el->getLabel() . '</li>';
            if(isset($elements[$el->field])){
                unset($elements[$el->field]);
            }
        }

        foreach($elements as $field => $fieldParams){
            $elementsString .= '<li key="'.$field.'" class="ui-state-default">' . SearchFormModel::getLabelByField($field) . '</li>';
        }

        echo CJSON::encode(array(
            'inForm' => $inForm,
            'elements' => $elementsString
        ));
    }

    public function actionSaveSort(){
        $objTypeId = Yii::app()->request->getParam('id', NULL);
        $sort = Yii::app()->request->getParam('sort');

        if(count($sort) >= param('searchMaxField', 15)){
            HAjax::jsonError(tt('Search max field ') . param('searchMaxField', 3));
        }

        if($objTypeId !== NULL && $sort && is_array($sort)){
            $elements = SearchForm::getSearchFields();

            $sql = "DELETE FROM {{search_form}} WHERE obj_type_id=:id AND status!=:status";
            Yii::app()->db->createCommand($sql)->execute(array(
                ':id' => $objTypeId,
                ':status' => SearchFormModel::STATUS_NOT_REMOVE,
            ));

            $i = 3;
            foreach($sort as $field){
                if(!isset($elements[$field])){
                    continue;
                }

                $search = new SearchFormModel();
                $search->attributes = array(
                    'obj_type_id' => $objTypeId,
                    'field' => $field,
                    'status' => $elements[$field]['status'],
                    'sorter' => $i,
                    'formdesigner_id' => isset($elements[$field]['formdesigner_id']) ? $elements[$field]['formdesigner_id'] : 0,
                );
                $search->save();
                $i++;
            }

			// delete assets js cache
			ConfigurationModel::clearGenerateJSAssets();

            HAjax::jsonOk();
        }

        HAjax::jsonError();
    }
}