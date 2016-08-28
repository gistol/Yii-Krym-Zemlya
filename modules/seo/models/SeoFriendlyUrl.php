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
 * This is the model class for table "{{seo_friendly_url}}".
 *
 * The followings are the available columns in table '{{seo_friendly_url}}':
 * @property integer $id
 * @property string $model_name
 * @property integer $model_id
 * @property string $url_ru
 * @property string $url_en
 * @property string $title_ru
 * @property string $title_en
 * @property string $description_ru
 * @property string $description_en
 * @property string $keywords_ru
 * @property string $keywords_en
 */
class SeoFriendlyUrl extends ParentModel {
	private static $_allActiveCityRoutes;
	private static $_allActiveObjTypesRoutes;
	/**
	 * Returns the static model of the specified AR class.
	 * @param string $className active record class name.
	 * @return SeoFriendlyUrl the static model class
	 */
	public static function model($className=__CLASS__)
	{
		return parent::model($className);
	}

	/**
	 * @return string the associated database table name
	 */
	public function tableName()
	{
		return '{{seo_friendly_url}}';
	}

	/**
	 * @return array validation rules for model attributes.
	 */
	public function rules()
	{
		return array(
			array('model_name, model_id', 'required'),
			array('model_id', 'numerical', 'integerOnly'=>true),
			array('model_name', 'length', 'max'=>20),
			array($this->i18nRules('url'), 'validUniqueUrl', 'except' => 'image'),
			array($this->i18nRules('url'), 'match', 'pattern' => '#^[-a-zA-Z0-9_+]{1,150}$#', 'message' => tc('It is allowed to use the characters "-a-zA-Z0-9_+" without spaces')), // допускаются любые символы кроме кириллицы
			array($this->i18nRules('url'), 'length', 'max'=>255),
			array($this->i18nRules('description'), 'length', 'max'=>255),
			array($this->i18nRules('keywords'), 'length', 'max'=>255),
			array($this->i18nRules('alt'), 'length', 'max'=>255),
			array($this->getI18nFieldSafe().', direct_url', 'safe'),
			array('url', 'i18nRequired', 'except' => 'image'),
			array('url_' . Yii::app()->language, 'uniqInLangs', 'except' => 'image'),

			array('id, model_name, model_id', 'safe', 'on'=>'search'),
		);
	}

    public function uniqInLangs($attribute, $params) {
        if(!$this->direct_url){
            return;
        }

        $activeLangs = Lang::getActiveLangs();

        $allValue = array();
        foreach($activeLangs as $lang){
            $field = 'url_'.$lang;
            $allValue[] = $this->$field;
        }

        if(array_diff_assoc($allValue, array_unique($allValue))){
            $this->addError('url', tt('The same URL for different languages', 'seo'));
        }
    }

	public function validUniqueUrl($attribute, $params) {
        $reservedUrl = array(
            'sitemap.xml',
            'yandex_export_feed.xml',
            'version',
            'sell',
            'rent',
            'rss',
            'entries',
            'faq',
            'login',
            'admin',
            'register',
            'recover',
            'reviews'
        );

		$langs = Lang::getActiveLangs(true);
        $reservedUrl = CMap::mergeArray($langs, $reservedUrl);

        $label = '';
        if(count($langs) > 1){
            $ex = explode('_', $attribute);
            if(isset($ex[1]) && array_key_exists($ex[1], $langs)){
                $label = 'url ' . $langs[$ex[1]]['name'] . ' - ';
            }
        }

        if($this->direct_url && in_array($this->$attribute, $reservedUrl)){
            $this->addError($attribute, $label . tt('This url already exists', 'seo'));
            return false;
        }

        $where = $this->isNewRecord ? '' : ' AND id != '.$this->id;

        $arr = array();
        foreach($langs as $lang){
            $arr[] = 'url_'.$lang['name_iso'].' = :alias';
        }
        $condition = '('.implode(' OR ', $arr).')';


        if($this->direct_url){
            $sql = "SELECT id FROM ".$this->tableName()." WHERE direct_url=1 AND " . $condition . $where;
            $exist = Yii::app()->db->createCommand($sql)
                ->queryScalar(array(
                    ':alias' => $this->$attribute,
                ));
        } else {
            $sql = "SELECT id FROM ".$this->tableName()." WHERE model_name=:model_name AND " . $condition . $where;
            $exist = Yii::app()->db->createCommand($sql)
                ->queryScalar(array(
                    ':alias' => $this->$attribute,
                    ':model_name' => $this->model_name,
                ));
        }

        if($exist){
            $this->addError($attribute, $label . tt('This url already exists', 'seo'));
            return false;
        }

        $this->clearErrors($attribute);
        return true;
	}

	public function i18nFields(){
		return array(
			'url' => 'varchar(255) not null',
			'title' => 'varchar(255) not null',
			'description' => 'varchar(255) not null',
			'keywords' => 'varchar(255) not null',
			'alt' => 'varchar(255) not null',
			'body_text' => 'text not null',
		);
	}

	/**
	 * @return array relational rules.
	 */
	public function relations() {
		$relation = array();
		$relation['apartmentImages'] = array(self::BELONGS_TO, 'Images', 'model_id', 'on' => 'model_name="Images"');
		$relation['entriesImages'] = array(self::BELONGS_TO, 'EntriesImage', 'model_id', 'on' => 'model_name="EntriesImage"');
		return $relation;
	}
	
	public function scopes() {
		return array(
			'notImages' => array(
				'condition' => $this->getTableAlias() . '.model_name <> "Images" AND '.$this->getTableAlias() . '.model_name <> "EntriesImage"',
			),
			'onlyImages' => array(
				'condition' => $this->getTableAlias() . '.model_name = "Images" OR '.$this->getTableAlias() . '.model_name = "EntriesImage"',
			),
			'withNotEmptyAlt' => array(
				'condition' => 'LENGTH('.$this->getTableAlias() . '.alt_'.Yii::app()->language.') > 0 ',
			),
			'locationModuleCity' => array(
				'condition' => $this->getTableAlias() . '.model_name <> "ApartmentCity"',
			),
			'notLocationModuleCity' => array(
				'condition' => $this->getTableAlias() . '.model_name <> "City"',
			),
		);
	}

	/**
	 * @return array customized attribute labels (name=>label)
	 */
	public function attributeLabels()
	{
		return array(
			'id' => 'ID',
			'model_name' => tt('Model Name', 'seo'),
			'model_id' => tt('Model Id', 'seo'),
			'direct_url' => tt('Direct url', 'seo'),
			'body_text' => tt('Body text', 'seo'),
			'title' => tt('Title', 'seo'),
			'description' => tt('Description', 'seo'),
			'url' => tt('URL', 'seo'),
			'keywords' => tt('Keywords', 'seo'),
		);
	}

	/**
	 * Retrieves a list of models based on the current search/filter conditions.
	 * @return CActiveDataProvider the data provider that can return the models based on the search/filter conditions.
	 */
	public function search()
	{
		$criteria=new CDbCriteria;
		$lang = Yii::app()->language;

		$criteria->compare($this->getTableAlias().'.id',$this->id);
		$criteria->compare($this->getTableAlias().'.model_name',$this->model_name);
		$criteria->compare($this->getTableAlias().'.model_id',$this->model_id);
		
		$criteria->compare($this->getTableAlias().".url_{$lang}", $this->{'url_'.$lang}, true);
		$criteria->compare($this->getTableAlias().".title_{$lang}", $this->{'title_'.$lang}, true);
		$criteria->compare($this->getTableAlias().".alt_{$lang}", $this->{'alt_'.$lang}, true);
		$criteria->compare($this->getTableAlias().".body_text_{$lang}", $this->{'body_text_'.$lang}, true);
				
		$criteria->with = array('apartmentImages', 'apartmentImages.apartment', 'entriesImages', 'entriesImages.entry');

		return new CActiveDataProvider($this, array(
			'criteria'=>$criteria,
			'sort' => array(
				'defaultOrder' => $this->getTableAlias().'.id DESC',
			),
			'pagination' => array(
				'pageSize' => param('adminPaginationPageSize', 20),
			),
		));
	}

	public function setDefault($model){
        $this->model_id = $model->id;
        $this->model_name = get_class($model) == 'UserAds' ? 'Apartment' : get_class($model);

        $this->setSeoData($model);
		
        // проверяем есть ли такой урл, подбираем уникальный 29 раз
        for($i = 0; $i < 30; $i++){
            if($this->validate('url_'.Lang::getDefaultLang())){
                break;
            }

            $this->setSeoData($model, $model->id + $i);
        }

		
		
		return true;
	}

    private function setSeoData($model, $postfix = ''){
		$langs = Lang::getActiveLangs();

		$params = CMap::mergeArray(array(
			'fieldTitle' => 'title',
			'fieldDescription' => 'description'
		), $model->seoFields());

		foreach($langs as $lang){
			$fieldTitle = $params['fieldTitle'].'_'.$lang;
			$fieldDescription = $params['fieldDescription'].'_'.$lang;

			$fieldSeoTitle = 'title_'.$lang;
			$fieldSeoDescription = 'description_'.$lang;
			$fieldUrl = 'url_'.$lang;

			if(empty($model->$fieldTitle) || !$model->$fieldTitle){
				return false;
			}

            $translitTitle = translit($model->$fieldTitle) . (param('genUrlWithID', 0) ? '-' . $model->id : '') . $postfix;

			$this->$fieldSeoTitle = $model->$fieldTitle;
			$this->$fieldUrl = $translitTitle;

			if(isset($model->$fieldDescription)){
				$this->$fieldSeoDescription = utf8_substr(trim(strip_tags($model->$fieldDescription)), 0, 255);
			}
		}
	}

	private static $_prefixUrlArray = array(
		'Apartment' => 'property/',
		'EntriesCategory' => '',
		'Entries' => '',
		'Article' => 'faq/',
		'InfoPages' => 'page/',
		'ApartmentObjType' => '',
		'City' => '',
		'ApartmentCity' => '',
	);

	public function getPrefixUrl(){
		return isset(self::$_prefixUrlArray[$this->model_name]) && !$this->direct_url ? self::$_prefixUrlArray[$this->model_name] : '';
	}

	public static $seoLangUrls = array();

	/**
	 * @param $url
	 * @param $modelName
	 * @return SeoFriendlyUrl
	 */
	public static function getForView($url, $modelName, $addParams = array()){
		if(param('urlExtension')){
			$url = rstrtrim($url, '.html');
		}

		$seo = SeoFriendlyUrl::model()->findByAttributes(array(
			'model_name' => $modelName,
			'url_'.Yii::app()->language => $url
		));
				
		if($seo){			
			$activeLangs = Lang::getActiveLangs();
			foreach($activeLangs as $lang){
				$field = 'url_'.$lang;
				if(isset(self::$_prefixUrlArray[$modelName]) && isset($seo->$field)){
					if ($modelName == 'Entries') {
						$entryModel = Entries::model()->findByPk($seo->model_id);
						$seoEntriesCategories = SeoFriendlyUrl::model()->findByAttributes(array(
							'model_name' => 'EntriesCategory',
							'model_id' => $entryModel->category_id
						));
									
						if ($seoEntriesCategories && isset($seoEntriesCategories->{'url_'.$lang})) {
							$prefix = ($lang == Lang::getDefaultLang() ? '' : $lang . '/').$seoEntriesCategories->{'url_'.$lang}.'/';
						}
						elseif ($seo->direct_url)  {
							$prefix = '';
						}
						else {
							$prefix = ($lang == Lang::getDefaultLang() ? '' : $lang . '/').self::$_prefixUrlArray[$modelName];
						}
					}
					elseif ($modelName == 'ApartmentObjType') {
						if (isset($addParams['cityId'])) {
							$cityId = $addParams['cityId'];
							$searchModelName = (issetModule('location')) ? 'City' : 'ApartmentCity';
							$seoCityModel = SeoFriendlyUrl::model()->findByAttributes(array(
								'model_name' => $searchModelName,
								'model_id' => $cityId
							));
							
							if ($seoCityModel && isset($seoCityModel->{'url_'.$lang})) {
								$prefix = ($lang == Lang::getDefaultLang() ? '' : $lang . '/').$seoCityModel->{'url_'.$lang}.'/';
							}
							elseif ($seo->direct_url)  {
								$prefix = '';
							}
							else {
								$prefix = ($lang == Lang::getDefaultLang() ? '' : $lang . '/').self::$_prefixUrlArray[$modelName];
							}
						}
					}
					else {
						$prefix = $seo->direct_url ? '' : ($lang == Lang::getDefaultLang() ? '' : $lang . '/') . self::$_prefixUrlArray[$modelName];
					}

					if($seo->$field){
						$prefixUrlExtension = (param('urlExtension') && ($modelName != 'EntriesCategory' && $modelName != 'ApartmentObjType' && $modelName != 'ApartmentCity' && $modelName != 'City')) ? '.html' : '';
						
						self::$seoLangUrls[$lang] = Yii::app()->baseUrl . '/' . $prefix . $seo->$field . $prefixUrlExtension;
					}
					else{
						self::$seoLangUrls[$lang] = Yii::app()->baseUrl . '/' . $prefix . $seo->model_id;
					}
					
                    //deb(self::$seoLangUrls);
				}
			}
		}

		//exit;
		return $seo;
	}

	private static $_cache;

	public static function getForUrl($id, $modelName) {
        if(!isset(self::$_cache[$modelName][$id])){
            self::$_cache[$modelName][$id] = SeoFriendlyUrl::model()->findByAttributes(array('model_name' => $modelName, 'model_id' => $id));
        }

		return self::$_cache[$modelName][$id];
	}

	public static function getAndCreateForModel($model, $reset = false, $scenario = ''){
		if(!param('genFirendlyUrl')){
			return false;
		}

		// костылек
		$modelName = get_class($model) == 'UserAds' ? 'Apartment' : get_class($model);

		$friendlyUrl = SeoFriendlyUrl::model()->findByAttributes(array(
			'model_name' => $modelName,
			'model_id' => $model->id
		));

		if($reset && $friendlyUrl){
			$friendlyUrl->delete();
			$friendlyUrl = null;
		}

		// Если еще нет, создаем
		if(!$friendlyUrl){
			$friendlyUrl = new SeoFriendlyUrl();
			if ($scenario) {
				$friendlyUrl->scenario = $scenario;
			}

			if($model->id > 0 && $friendlyUrl->setDefault($model)){
				$friendlyUrl->save();
			} else {
				$friendlyUrl->model_name = $modelName;
				$friendlyUrl->model_id = $model->id;
			}
		}

		return $friendlyUrl;
	}

	public static function getModelNameList() {
		$return = array();
		$return['Apartment'] = tt('Listing', 'seo');
		$return['Entries'] = tt('Entries', 'seo');
		$return['Article'] = tt('Article', 'seo');
		$return['InfoPages'] = tt('InfoPages', 'seo');
		$return['EntriesCategory'] = tt('EntriesCategory', 'seo');
		$return['ApartmentObjType'] = tt('ApartmentObjType', 'seo');
		
		if (issetModule('location')) {
			$return['City'] = tt('City', 'seo');
		}
		else {
			$return['ApartmentCity'] = tt('City', 'seo');
		}
		
		return $return;
	}
	
	public static function getModelImagesNameList()
	{
		return array(
			'EntriesImage' => tt('Entries images', 'seo'),
			'Images' => tt('Listing images', 'seo'),
		);
	}

	public function getModelName()
	{
		$list = self::getModelNameList();
		return isset($list[$this->model_name]) ? $list[$this->model_name] : '?';
	}
	
	public function getModelImageName()
	{
		$list = self::getModelImagesNameList();
		return isset($list[$this->model_name]) ? $list[$this->model_name] : '?';
	}

	public function getUrlForTable()
	{
		$url = 'url_'.Yii::app()->language;
		return $this->{$url};
	}
	
	public function getImageUrlForParent() {
		$return = '?';
				
		if (isset($this->apartmentImages) && isset($this->apartmentImages->apartment) && $this->apartmentImages->apartment) {
			$return = CHtml::link($this->apartmentImages->apartment->id, $this->apartmentImages->apartment->getUrl(), array('target' => '_blank'));
		}
		
		if (isset($this->entriesImages) && isset($this->entriesImages->entry) && $this->entriesImages->entry) {
			$return = CHtml::link($this->entriesImages->entry->id, $this->entriesImages->entry->getUrl(), array('target' => '_blank'));
		}
		
		return $return;
	}
	
	public static function getActiveCityRoute() {	
		if (oreInstall::isInstalled()) {
			if (self::$_allActiveCityRoutes === null) {
				$citiesListModel = SeoFriendlyUrl::getAllActiveCities();
				$langs = Lang::getActiveLangs();
				$citiesListArr = array();
				
				if (!empty($citiesListModel)) {
					foreach($citiesListModel as $item) {
						foreach($langs as $lang) {
							$citiesListArr[$item->id][$lang] = array('cityId' => $item->id, 'lang' => $lang, 'name' => $item->{'name_' . $lang}, 'url' => translit($item->{'name_' . $lang}));
						}
					}
				}
				
				if (!empty($citiesListArr)) {
					if(param('genFirendlyUrl')){
						$modelName = (issetModule('location')) ? 'City' : 'ApartmentCity';
						$citiesSeoFriendlyList = SeoFriendlyUrl::model()->findAllByAttributes(array('model_name' => $modelName));
						if (is_array($citiesSeoFriendlyList) && !empty($citiesSeoFriendlyList)) {							
							foreach($citiesSeoFriendlyList as $city) {
								foreach ($langs as $lang) {
									if (isset($citiesListArr[$city->model_id][$lang])) {
										$citiesListArr[$city->model_id][$lang]['url'] = $city->{'url_' . $lang};
									}
								}
							}
						}
					}
				}
												
				self::$_allActiveCityRoutes = $citiesListArr;
			}
		}
				
		return self::$_allActiveCityRoutes;
	}
	
	public static function getActiveObjTypesRoute() {
		if (oreInstall::isInstalled()) {
			if (self::$_allActiveObjTypesRoutes === null) {				
				$objTypesListModel = SeoFriendlyUrl::getAllActiveObjTypes();
				$langs = Lang::getActiveLangs();
				$objTypesListArr = array();
				
				if (!empty($objTypesListModel)) {
					foreach($objTypesListModel as $item) {
						foreach($langs as $lang) {
							$objTypesListArr[$item->id][$lang] = array('objTypeId' => $item->id, 'lang' => $lang, 'name' => $item->{'name_' . $lang}, 'url' => translit($item->{'name_' . $lang}));
						}
					}
				}
				
				if (!empty($objTypesListArr)) {
					if(param('genFirendlyUrl')){
						$objTypesSeoFriendlyList = SeoFriendlyUrl::model()->findAllByAttributes(array('model_name' => 'ApartmentObjType'));
						if (is_array($objTypesSeoFriendlyList) && !empty($objTypesSeoFriendlyList)) {							
							foreach($objTypesSeoFriendlyList as $objType) {
								foreach ($langs as $lang) {
									if (isset($objTypesListArr[$objType->model_id][$lang])) {
										$objTypesListArr[$objType->model_id][$lang]['url'] = $objType->{'url_' . $lang};
									}
								}
							}
						}
					}
				}
				
				self::$_allActiveObjTypesRoutes = $objTypesListArr;
			}
		}
		
		return self::$_allActiveObjTypesRoutes;
	}
	
	public static function sortArrayByArray(array $array, array $orderArray) {
		$ordered = array();
		foreach($array as $key => $value) {
			if(array_key_exists($key, $orderArray)) {
				$ordered[$key] = $orderArray[$key];
				unset($orderArray[$key]);
			}
		}
		return $ordered + $orderArray;
	}
	
	public static function getAllActiveCities() {
		$modelName = (issetModule('location')) ? 'City' : 'ApartmentCity';
		$model = CActiveRecord::model($modelName);
				
		$criteria = new CDbCriteria;
		if (issetModule('location')) {
			$criteria->join = 'INNER JOIN {{apartment}} ap ON ap.loc_city = t.id';
		}
		else {
			$criteria->join = 'INNER JOIN {{apartment}} ap ON ap.city_id = t.id';
		}
		$criteria->addCondition('t.active=1');
		$criteria->addInCondition('ap.price_type', array_keys(HApartment::getPriceArray(Apartment::PRICE_SALE, true)));
		$criteria->addCondition('ap.active = '.Apartment::STATUS_ACTIVE);
		if (param('useUserads')) {
			$criteria->addCondition('ap.owner_active = '.Apartment::STATUS_ACTIVE);
		}
		$criteria->order = 't.sorter';
		$criteria->group = 't.id';		
		
		return $model->findAll($criteria);
	}
	
	public static function getAllActiveObjTypes() {
		$criteria = new CDbCriteria;
		$criteria->order = 't.sorter';
		
		return ApartmentObjType::model()->findAll($criteria);
	}
	
	public static function getCountApartmentsForCategories() {		
		$ownerActiveCond = '';
		if (param('useUserads')) {
			$ownerActiveCond = ' AND owner_active = '.Apartment::STATUS_ACTIVE.' ';
		}

		$locationField = (issetModule('location')) ? 'loc_city' : 'city_id';
		
		$sql = 'SELECT obj_type_id, '.$locationField.' as city, COUNT(id) as count FROM {{apartment}} 
				WHERE price_type IN ('.implode(',', array_keys(HApartment::getPriceArray(Apartment::PRICE_SALE, true))).')
				AND active = '.Apartment::STATUS_ACTIVE.' '.$ownerActiveCond.'
				GROUP BY obj_type_id, '.$locationField;
		$result = Yii::app()->db->createCommand($sql)->queryAll();
				
		return $result;
	}
}