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

class Iecsv extends Apartment {
	public $isZip;
	public $assetsPath;
	public $libraryPath;
	public $csvPath;
	public $import;
	public $ownerEmail;

	public $fileMaxSize = 10485760; /* 1024 * 1024 * 10 - 10 MB */
	public $fileMaxSizeMessage = 10; /* 10 MB */

	public $itemsSelectedExport;
	public $itemsSelectedImport;

	public $selectedImportUser;

	public function init() {
		$this->preparePaths();
		$this->publishAssets();

		$fileMaxSize['postSize'] = toBytes(ini_get('post_max_size'));
		$fileMaxSize['uploadSize'] = toBytes(ini_get('upload_max_filesize'));
		$this->fileMaxSize = min($fileMaxSize);
		$this->fileMaxSizeMessage = self::formatBytes($this->fileMaxSize);

		parent::init();
	}

	public function rules() {
		$rules = array(
			array('id, city_id, active, type, ownerEmail', 'safe', 'on' => 'search'),
			array(
				'import', 'file',
				'types' => 'csv, zip',
				'maxSize' => $this->fileMaxSize,
				'tooLarge' => Yii::t('module_iecsv', 'The file was larger than {size}. Please upload a smaller file.', array('{size}' => $this->fileMaxSizeMessage)),
				'on' => 'insert',
			),
		);

		if (issetModule('location')) {
			$rules[] = array('loc_city, loc_region, loc_country', 'safe', 'on' => 'search');
			$rules[] = array('loc_city, loc_region, loc_country', 'numerical', 'integerOnly' => true);
		}

		return $rules;
	}

	public function relations() {
		Yii::import('application.modules.apartmentObjType.models.ApartmentObjType');
		Yii::import('application.modules.apartmentCity.models.ApartmentCity');
		$relations = array(
			'objType' => array(self::BELONGS_TO, 'ApartmentObjType', 'obj_type_id'),

			'city' => array(self::BELONGS_TO, 'ApartmentCity', 'city_id'),

			'user' => array(self::BELONGS_TO, 'User', 'owner_id'),

			'images' => array(self::HAS_MANY, 'Images', 'id_object', 'order' => 'images.sorter'),
		);

		if (issetModule('location')) {
			$relations['locCountry'] = array(self::BELONGS_TO, 'Country', 'loc_country');
			$relations['locRegion'] = array(self::BELONGS_TO, 'Region', 'loc_region');
			$relations['locCity'] = array(self::BELONGS_TO, 'City', 'loc_city');
		}

		if(issetModule('seo')){
			$relations['seo'] = array(self::HAS_ONE, 'SeoFriendlyUrl', 'model_id', 'on' => 'model_name="Apartment"');
		}

		return $relations;
	}

	public function attributeLabels() {
		return array(
			'id' => tt('ID', 'apartments'),
			'type' => tt('Type', 'apartments'),
			'title' => tt('Apartment title', 'apartments'),
			'obj_type_id' => tt('Object type', 'apartments'),
			'city_id' => tt('City', 'apartments'),
			'city' => tt('City', 'apartments'),
			'isZip' => tt('Export to .zip file with photos included'),
			'active' => tt('Status', 'apartments'),
			'ownerEmail' => tt('Owner', 'apartments'),
			'selectedImportUser' => tt('selectedImportUser', 'iecsv'),
			'loc_country' => tc('Country'),
			'locCountry' => tc('Country'),
			'loc_region' => tc('Region'),
			'locRegion' => tc('Region'),
			'loc_city' => tc('City'),
			'locCity' => tc('City'),
		);
	}

	public function preparePaths() {
		$this->assetsPath = Yii::getPathOfAlias('application.modules.iecsv.assets');
		$this->libraryPath = Yii::getPathOfAlias('application.modules.iecsv.library');
		$this->csvPath = Yii::getPathOfAlias('webroot.uploads.iecsv');
	}

	public function publishAssets() {
		if (is_dir($this->assetsPath)) {
			$baseUrl = Yii::app()->assetManager->publish($this->assetsPath);
			Yii::app()->clientScript->registerCssFile($baseUrl.DIRECTORY_SEPARATOR.'iecsv.css');
		}
	}

	public function searchExport() {
		$criteria = new CDbCriteria;
		$tmp = 'title_' . Yii::app()->language;

		$criteria->compare($this->getTableAlias().'.id', $this->id);
		$criteria->compare($this->getTableAlias().'.active', $this->active);
		$criteria->compare($tmp, $this->$tmp, true);
		if (issetModule('location')) {
			$criteria->compare($this->getTableAlias().'.loc_country', $this->loc_country);
			$criteria->compare($this->getTableAlias().'.loc_region', $this->loc_region);
			$criteria->compare($this->getTableAlias().'.loc_city', $this->loc_city);
		} else
			$criteria->compare($this->getTableAlias().'.city_id', $this->city_id);

		$criteria->compare($this->getTableAlias().'.type', $this->type);

		if (issetModule('userads') && param('useModuleUserAds', 1)) {
			if ($this->ownerEmail) {
				$criteria->addCondition('user.email LIKE "%'.$this->ownerEmail.'%"');
			}
		}

		$criteria->addCondition($this->getTableAlias().'.active <> :draft');
		$criteria->params['draft'] = Apartment::STATUS_DRAFT;

		$criteria->order = $this->getTableAlias().'.sorter DESC';

		$criteria->with = array('city', 'user');

		return new CActiveDataProvider($this, array(
			'criteria' => $criteria,
			'pagination' => array(
				'pageSize' => param('adminPaginationPageSize', 20),
			),
		));
	}

	public static function getUsersArray() {
		$usersArr = array();
		$userModel = User::model()->findAll(array('condition' => 'active = 1', 'order'=>'id'));
		if ($userModel) {
			foreach ($userModel as $user) {
				$usersArr[$user->id] = $user->username . ' ('.$user->email.')';
			}
		}
		return $usersArr;
	}

	public static function formatBytes($size, $precision = 2) {
		$base = log($size) / log(1024);
		$suffixes = array('', 'k', 'M', 'G', 'T');

		return round(pow(1024, $base - floor($base)), $precision) . $suffixes[floor($base)];
	}
}