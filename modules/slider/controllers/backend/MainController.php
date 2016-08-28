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

class MainController extends ModuleAdminController{
	public $modelName = 'Slider';
	public $defaultAction='admin';

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

	public function actionView($id){
		$this->redirect(array('admin'));
	}

	public function actionAdmin(){
		$this->getMinSorter();
		$this->getMaxSorter();
		parent::actionAdmin();
	}

	public function actionCreate() {
		$model = new $this->modelName;

		if(isset($_POST["{$this->modelName}"])){
            $model->attributes = $_POST["{$this->modelName}"];
			$model->scenario = 'upload';

			if($model->validate()) {
				$model->upload = CUploadedFile::getInstance($model,'img');
				$model->img = md5(uniqid()).'.'.$model->upload->extensionName;

				if($model->save()){
					$model->upload->saveAs(Yii::getPathOfAlias($model->path).'/'.$model->img);

					Yii::app()->user->setFlash(
							'success', tt('Image succesfullty added to slider.')
					);
					$model->unsetAttributes();
					//$this->redirect(array());
				}
			}
        }

		$this->render('create', array('model' => $model));
	}

	public function returnImageFancy($data, $tableId, $ignore = 0, $width = 150, $height = 80) {
		if($ignore && $data->id == $ignore){
			return '';
		}

		$url = Yii::app()->request->baseUrl."/".  Slider::model()->sliderPath."/".$data->img;

		$options = array(
			'class'	=> 'fancy',
		);

		$img = CHtml::image(
			Yii::app()->request->baseUrl."/". Slider::model()->sliderPath."/".$data->getThumb($width, $height)
		);

		return '<div align="center">'.CHtml::link($img, $url, $options).'</div>';
	}
}