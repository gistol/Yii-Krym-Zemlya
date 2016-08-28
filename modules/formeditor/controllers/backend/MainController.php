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

class MainController extends ModuleAdminController {
    public $modelName = 'FormDesigner';

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

    private $iteration = 0;

    private $fieldName;

    private $_types = array(
        FormDesigner::TYPE_REFERENCE => 'int(11) NOT NULL',
        FormDesigner::TYPE_TEXT => 'varchar(255) NOT NULL',
        FormDesigner::TYPE_TEXT_AREA => 'text NOT NULL',
        FormDesigner::TYPE_TEXT_AREA_WS => 'text NOT NULL',
        FormDesigner::TYPE_INT => "float NOT NULL DEFAULT '0'",
    );

    public function actionCreate(){
        $model = new FormDesigner();
        $model->scenario = 'advanced';
        $model->type = FormDesigner::TYPE_TEXT;

        $translate = new TranslateMessage();

        if(isset($_POST['FormDesigner'])){
            $model->attributes = $_POST['FormDesigner'];

            if($model->validate()){
                // magic begin
                $this->fieldName = translit($model->getStrByLang('label'), '_', true);
                $this->fieldName = substr($this->fieldName, 0, 12);

                if($this->setFieldInTable($_POST['FormDesigner']['type']) || $model->type == FormDesigner::TYPE_MULTY){
                    $model->field = $this->fieldName;

                    $translate->attributes = $_POST['TranslateMessage'];
                    $translate->category = 'common';
                    $translate->message = 'Search by '.$this->fieldName;
                    if($translate->save()){
                        $model->save();
                        Yii::app()->cache->flush();

                        if ($model->type == FormDesigner::TYPE_REFERENCE || $model->type == FormDesigner::TYPE_MULTY) {
                            if (!$model->reference_id) {
                                $ref_id = HFormEditor::createCategoryFromField($model);
                                if ($ref_id) {
                                    $model->reference_id = $ref_id;
                                    $model->update(array('reference_id'));
                                    Yii::app()->user->setFlash('success', tt('The new field is successfully created.').' '.tt('Please add values to the field now.'));
                                    $this->redirect(array('/referencevalues/backend/main/create','cat_id'=>$model->reference_id));
                                }
                            } else {
                                $sql = "SELECT count(id) FROM {{apartment_reference_values}} WHERE reference_category_id=:id";
                                $count = Yii::app()->db->createCommand($sql)->queryScalar(array(':id' => $model->reference_id));
                                if (!$count) {
                                    Yii::app()->user->setFlash('success', tt('The new field is successfully created.').' '.tt('Please add values to the field now.'));
                                    $this->redirect(array('/referencevalues/backend/main/create','cat_id'=>$model->reference_id));
                                }
                            }
                        }

                        Yii::app()->user->setFlash('success', tt('The new field is successfully created.'));
                        $this->redirect(Yii::app()->createUrl('/formdesigner/backend/main/admin'));
                    }

                } else {
                    $model->addError('', tt('Failed to create field'));
                }
            }
        }

        $this->render('create', array('model' => $model, 'translate' => $translate));
    }

    public function actionUpdate($id){
        $model = $this->loadModel($id);

        if($model->standard_type != FormDesigner::STANDARD_TYPE_NEW){
            $this->redirect(Yii::app()->createUrl('/formdesigner/backend/main/update', array('id' => $this->id)));
        }

        $model->scenario = 'advanced';

        $this->performAjaxValidation($model);

        $translate = $model->getTranslateModel();

        if(isset($_POST[$this->modelName])){
            $model->attributes=$_POST[$this->modelName];
            if($model->validate()){
                $translate->attributes = $_POST['TranslateMessage'];
                if($translate->save()){
                    if($model->save(false)){
                        $this->redirect(Yii::app()->createUrl('/formdesigner/backend/main/admin'));
                    }
                }
            }
        }

        $this->render('update', array(
            'model'=>$model,
            'translate' => $translate,
        ));
    }

    public function actionView($id){
        $this->redirect(Yii::app()->createUrl('/formdesigner/backend/main/admin'));
    }

    private function setFieldInTable($type){		
        if(!in_array($type, array_keys(CMap::mergeArray(array(FormDesigner::TYPE_MULTY => FormDesigner::TYPE_MULTY), $this->_types)))) {
            return false;
        }
		
        $sql = "SELECT COLUMN_NAME FROM information_schema.COLUMNS WHERE TABLE_NAME='{{apartment}}' AND COLUMN_NAME='{$this->fieldName}' AND table_schema = DATABASE()";
        $fieldApartmentExist = Yii::app()->db->createCommand($sql)->queryScalar();
		
		$sql = "SELECT EXISTS(SELECT 1 FROM {{formdesigner}} WHERE field = '{$this->fieldName}' LIMIT 1)";
		$fieldFormDesignerExist = Yii::app()->db->createCommand($sql)->queryScalar();
		
        if (!$fieldApartmentExist && !$fieldFormDesignerExist) {
			if ($type == FormDesigner::TYPE_MULTY) {
				return true;
			}
			
			return (Yii::app()->db->createCommand()->addColumn('{{apartment}}', $this->fieldName, $this->_types[$type]) === false) ? false : true;
        }

        $this->iteration++;
        $this->fieldName = $this->fieldName . $this->iteration;

        if($this->iteration > 30){
            return false;
        }

        return $this->setFieldInTable($type);
    }

}