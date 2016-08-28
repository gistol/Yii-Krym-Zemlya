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

class Slider extends ParentModel {
	public $path = 'webroot.uploads.slider';
	public $sliderPath = 'uploads/slider';
	public $title;
	public $url;
	public $upload;
	public $img;
	public $maxHeight;
	public $maxWidth;
	public $supportExt = 'jpg, png, gif';
	public $fileMaxSize = 10485760; /* 1024 * 1024 * 10 - 10 MB */

	public function init() {
		$fileMaxSize['set'] = $this->fileMaxSize;
		$fileMaxSize['postSize'] = toBytes(ini_get('post_max_size'));
		$fileMaxSize['uploadSize'] = toBytes(ini_get('upload_max_filesize'));
		$this->fileMaxSize = min($fileMaxSize);

		$this->publishAssets();

		return parent::init();
	}

	public static function model($className=__CLASS__) {
		return parent::model($className);
	}

	public function tableName() {
		return '{{slider}}';
	}

	public function publishAssets() {
		$assetsPath = Yii::getPathOfAlias('webroot.themes.'.Yii::app()->theme->name . '.views.modules.slider.assets');

		if (is_dir($assetsPath)) {
			$baseUrl = Yii::app()->assetManager->publish($assetsPath);
			Yii::app()->clientScript->registerCssFile($baseUrl . '/module_slider.css');
		}
	}

	public function rules() {
		return array(
			array(
				'img', 'file',
				'types' => "{$this->supportExt}",
				'minSize' => 1 * 1024, // 1 kb
				'maxSize' => $this->fileMaxSize,
				'tooLarge' => Yii::t('module_slider', 'The file was larger than {size}MB. Please upload a smaller file.', array('{size}' => $this->fileMaxSize)),
				'on' => 'upload',
			),
			array('active, sorter, use_effect', 'numerical', 'integerOnly'=>true),
			//array('title', 'i18nRequired'),
			array('url', 'url'),
			array(''.$this->getI18nFieldSafe().', url, use_effect, active, sorter', 'safe'),
		);
	}


	public function attributeLabels() {
		return array(
			'id' => 'ID',
			'title' => tt('Image title', 'slider'),
			'url' => tt('Image url', 'slider'),
			'img'	=> tt('Image', 'slider'),
			'use_effect' => tt('Use Philips Ambilight effect', 'slider')
		);
	}

	public function i18nFields(){
		return array(
			'title' => 'text not null',
		);
	}

	public function search() {
		$criteria = new CDbCriteria;

		$criteria->compare('title_'.Yii::app()->language, $this->{'title_'.Yii::app()->language}, true);
		$criteria->compare('url', $this->url, true);
		$criteria->order = 'sorter ASC';

		return new CActiveDataProvider($this, array(
			'criteria' => $criteria,
			'sort' => array(
				'defaultOrder' => 'date_updated DESC',
			),
			'pagination' => array(
				'pageSize' => param('adminPaginationPageSize', 20),
			),
		));
	}

	public function behaviors(){
		return array(
			'AutoTimestampBehavior' => array(
				'class' => 'zii.behaviors.CTimestampBehavior',
				'createAttribute' => 'date_updated',
				'updateAttribute' => 'date_updated',
			),
		);
	}

	public function beforeSave(){
		if($this->isNewRecord){
			$this->active = 1;

			$maxSorter = Yii::app()->db->createCommand()
				->select('MAX(sorter) as maxSorter')
				->from('{{slider}}')
				->queryRow();
			$this->sorter = $maxSorter['maxSorter']+1;
		}

		$sql = "UPDATE {{slider}} SET date_updated=NOW() WHERE id='".$this->id."'";
		Yii::app()->db->createCommand($sql)->execute();

		return parent::beforeSave();
	}

	protected function beforeDelete() {
		$sql = 'SELECT img FROM {{slider}} WHERE id="'.$this->id.'"';

		$item = Yii::app()->db->createCommand($sql)->queryRow();

		if (isset($item['img'])) {
			if (file_exists($this->path.'/'.$item['img'])) {
				@unlink($this->path.'/'.$item['img']);
			}
		}

		return parent::beforeDelete();
	}

	public function getTitle(){
		return $this->getStrByLang('title');
	}

	public function getActiveImages($inCriteria = null){

		if($inCriteria === null){
			$criteria = new CDbCriteria;
			$criteria->condition = 'active = 1';
			$criteria->order = 'sorter ASC';
		} else {
			$criteria = $inCriteria;
		}

		$dependency = new CDbCacheDependency('SELECT MAX(date_updated) FROM {{slider}}');

		$items = $this->cache(param('cachingTime', 1209600), $dependency)->findAll($criteria);

		return $items;
	}

	public function getThumb($width, $height){
		$path = Yii::getPathOfAlias($this->path);
		$fileName = 'thumb_'.$width.'x'.$height."_".$this->img;
		$filePath = $path.DIRECTORY_SEPARATOR.$fileName;
		
		if(file_exists($filePath)){
			return $fileName;
		} 
		else {
			if (file_exists($path.DIRECTORY_SEPARATOR.$this->img)) {
				$useEffect = $this->use_effect;
				
				Yii::import('application.extensions.image.Image');
				$image = new Image($path.DIRECTORY_SEPARATOR.$this->img);

				if ($useEffect) {
					$image->resizeWithEffect($width, $height);
				}
				else {
					$image->resize($width, $height);
				}
				$image->save($filePath);
				return $fileName;
			}
			return null;
		}
	}
}