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


class Advert extends ParentModel {
	public $supportExt = 'jpg, png, gif';
	public $fileMaxSize = 10485760; /* 1024 * 1024 * 10 - 10 MB */
	public $file;

	public $areas;

	public function init() {
		$fileMaxSize['postSize'] = toBytes(ini_get('post_max_size'));
		$fileMaxSize['uploadSize'] = toBytes(ini_get('upload_max_filesize'));

		$this->fileMaxSize = min($fileMaxSize);
	}

	public function publishAssets() {
		$assetsPath = Yii::getPathOfAlias('webroot.themes.'.Yii::app()->theme->name . '.views.modules.advertising.assets');
		if (is_dir($assetsPath)) {
			$baseUrl = Yii::app()->assetManager->publish($assetsPath);
			Yii::app()->clientScript->registerCssFile($baseUrl . '/css/style-rkl.css');
		}
	}

	public static function model($className=__CLASS__) {
		return parent::model($className);
	}

	public function tableName() {
		return '{{advertising}}';
	}

	public function rules() {
		return array(
			array('position, type, areas', 'required'),

			array('url, alt_text', 'length', 'min' => 5, 'on' => 'file'),
			array('url', 'length', 'max' => 255, 'on' => 'file'),

			array(
				'file_path', 'file',
				'types' => "{$this->supportExt}",
				'maxSize' => $this->fileMaxSize,
				'allowEmpty' => !$this->isNewRecord,
				'on' => 'file',
			),
			array('url, alt_text, active', 'safe'),
			array('active', 'safe', 'on'=>'search'),
			array($this->getI18nFieldSafe(), 'safe'),
		);
	}

	public function i18nFields(){
		return array(
			'html' => 'text not null',
			'js' => 'text not null',
		);
	}

	public function relations() {
        Yii::import('application.modules.advertising.models.AdvertArea');
        return array(
			'areas' => array(self::HAS_MANY, 'AdvertArea', 'id_advertising',
				'order' => 'areas.id DESC',
			)
		);
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

	public function attributeLabels() {
		return array(
			'id' => 'Id',
			'type' => tt('I want place'),
			'position' => tt('Position'),
			'areas' => tt('Pages'),
			'file_path' => tt('File'),
			'html' => tt('Text/HTML code'),
			'js' => tt('Javascript code'),
			'url' => tt('URL'),
			'alt_text' => tt('Alternative text'),
			'active' => tt('Active'),
			'views' => tt('Views'),
			'clicks' => tt('Clicks'),
		);
	}

	public function getHtml(){
		return $this->getStrByLang('html');
	}

	public function getJs(){
		return $this->getStrByLang('js');
	}

	public function afterSave(){
		if($this->areas){
			$this->setAreas($this->areas);
		}
		return parent::afterSave();
	}

	public function beforeDelete(){
		$sql = 'DELETE FROM {{advertising_area}} WHERE id_advertising="'.$this->id.'"';
		Yii::app()->db->createCommand($sql)->execute();

		$sql = 'SELECT file_path FROM {{advertising}} WHERE id="'.$this->id.'"';
		$item = Yii::app()->db->createCommand($sql)->queryScalar();

		if ($item) {
			if (file_exists(Yii::getPathOfAlias('webroot').'/uploads/rkl/'.$item)) {
				@unlink(Yii::getPathOfAlias('webroot').'/uploads/rkl/'.$item);
			}
		}

		return parent::beforeDelete();
	}

	public static function getAvailableTypes() {
		return array(
			'file' => tt('Image'),
			'html' => tt('HTML code'),
			'js' => tt('Javascript code'),
		);
	}

	public static function getCurrentTypeName($type) {
		$types = self::getAvailableTypes();
		return $types[$type];
	}


	public static function getAvailablePositions() {
		return array(
			'pos1' => tt('Top-left'),
			'pos2' => tt('Top-right'),
			'pos3' => tt('Top-center'),
			'pos4' => tt('Bottom-left'),
			'pos5' => tt('Bottom-right'),
			'pos6' => tt('Bottom-center'),
		);
	}

	private static $_cacheAreas;
	
	public static function getAvailableAreas() {
		if (!isset(self::$_cacheAreas)) {
			$areas = array(
				'allpages' => tt('All pages').' ***',
				'site/index' => tt('Main page'),
				//'' => tt('Main page'),
				'apartments/main/view' => tt('View listing'),
				'quicksearch/main/mainsearch' => tt('Search results'),
				'contactform/main/index' => tt('Contact us'),
				'specialoffers/main/index' => tt('Special offers'),
				'entries/main/index' => tt('Entries'),
				'entries/main/view' => tt('Entries -> view'),
				'articles/main/index' => tt('Articles'),
				'articles/main/view' => tt('Articles -> view'),
				'reviews/main/index' => tt('Reviews'),
				//'reviews/main/add' => tt('Reviews -> add'),
				'guestad/main/create' => tt('Guestad -> add'),
				'infopages/main/view' => tt('Infopages -> view'),
				'site/login' => tt('Login page'),
				'site/register' => tt('Registration page'),
				'site/recover' => tt('Recovery password page'),
			);

			if (issetModule('menumanager')) {
				$additionalAreas = Yii::app()->db->createCommand()
					->select('id, pageId, title_'.Yii::app()->language.' as title')
					->from(Menu::model()->tableName())
					->where('special = 0 AND ( type = '.Menu::LINK_NEW_INFO.')')
					->queryAll();


				foreach ($additionalAreas as $item) {
					$areas["infopages/main/view?id={$item['pageId']}"] = $item['title'];
				}
			}
			
			self::$_cacheAreas = $areas;
		}

		return self::$_cacheAreas;
	}

	public static function getCurrentAreasName($area) {
		$areas = self::getAvailableAreas();
		return isset($areas[$area]) ? $areas[$area] : "";
	}

	public function getAreas(){
		$sql = 'SELECT page FROM {{advertising_area}} WHERE id_advertising="'.$this->id.'"';
		return Yii::app()->db->createCommand($sql)->queryColumn();
	}

	public function setAreas($areas){
		$sql = 'DELETE FROM {{advertising_area}} WHERE id_advertising="'.$this->id.'"';
		Yii::app()->db->createCommand($sql)->execute();
		if($areas && is_array($areas)){
			$values = array();
			foreach ($areas as $page) {
				$values[] = "( '{$this->id}', '{$page}')";
			}

			if ($values) {
				$sql = 'INSERT INTO {{advertising_area}} (id_advertising, page) VALUES ' . implode(',', $values);
				Yii::app()->db->createCommand($sql)->execute();
			}
		}
	}

	public function getAdvertContent() {
		//	$url = Yii::app()->getRequest();
		//	$query = $url->pathInfo . ($url->queryString ? "?".$url->queryString : "");
		//	$cName = Yii::app()->controller->id;;
		//	$aName = Yii::app()->controller->action->id;

		$mName = (Yii::app()->controller->module) ? Yii::app()->controller->module->name : '';

		$params = '';
		if ($mName == 'infopages')
			$params = (isset($_GET['id'])) ? "?id=".$_GET['id'] : "";

		$page = Yii::app()->getUrlManager()->parseUrl(Yii::app()->getRequest()) . $params;

		if (isset($page) && empty($page))
			$page = 'site/index';

		$content = Yii::app()->db->createCommand()
				->select('a.id, a.position, a.type, a.file_path, a.html_'.Yii::app()->language.' as html, a.url, a.alt_text, a.js_'.Yii::app()->language.' as js')
				->from($this->tableName().' a')
				->join(AdvertArea::model()->tableName().' aa', 'aa.id_advertising = a.id')
				->where(' a.active = 1 AND ( aa.page LIKE "'.$page.'%" OR aa.page = "allpages" )')
				->group('a.id')
				->queryAll();

		if ($content && is_array($content)) {
			$this->publishAssets();

			$pos1 = $pos2 = $pos3 = $pos4 = $pos5 = $pos6 = array();
			foreach ($content as $key => $item) {
				if ($item['position'] == 'pos1') {
					$pos1[] = $item;
				}
				if ($item['position'] == 'pos2') {
					$pos2[] = $item;
				}
				if ($item['position'] == 'pos3') {
					$pos3[] = $item;
				}
				if ($item['position'] == 'pos4') {
					$pos4[] = $item;
				}
				if ($item['position'] == 'pos5') {
					$pos5[] = $item;
				}
				if ($item['position'] == 'pos6') {
					$pos6[] = $item;
				}
			}
			Yii::app()->controller->advertPos1 = $pos1;
			Yii::app()->controller->advertPos2 = $pos2;
			Yii::app()->controller->advertPos3 = $pos3;
			Yii::app()->controller->advertPos4 = $pos4;
			Yii::app()->controller->advertPos5 = $pos5;
			Yii::app()->controller->advertPos6 = $pos6;

			// set count
			if ((int) $item['id']) {
				Advert::model()->updateCounters(
					array('views'=>1),
					"id = :id",
					array(':id' => (int) $item['id'])
				);
			}

			unset($pos1, $pos2, $pos3, $pos4, $pos5, $pos6);
		}

		//return $content;
	}

	public static function getDependency(){
        return new CDbCacheDependency('SELECT MAX(date_updated) FROM {{advertising}}');
    }
}