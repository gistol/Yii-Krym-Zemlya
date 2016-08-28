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


class Currency extends CActiveRecord {
 	const PARSE_CBR = 1;
	const PARSE_ECB = 2;
	const PARSE_YAHOO = 3;

	private static $_defaultCurrencyModel;
	private static $_currentCurrencyModel;
	private static $_valuteArray;
	private static $_usedCurrenciesIds;

    public $translate = array();

	public static function model($className = __CLASS__)
	{
		return parent::model($className);
	}

	public function tableName()
	{
		return '{{currency}}';
	}

	public function rules()
	{
		$rules = array(
			array('char_code, value', 'required'),
			array('value, is_default, not_parse', 'numerical'),
			array('char_code', 'length', 'max' => 3),
			array('char_code', 'unique'),
			array('char_code', 'match', 'pattern' => '#^[A-Z]{1,3}$#', 'message' => tc('It is allowed to use the characters "A-Z" without spaces')),
			array('id, active, sorter, char_code, value, is_default, date_updated', 'safe', 'on' => 'search'),
		);

        $langs = Lang::getActiveLangs();
        $fields = array();
        foreach($langs as $lang){
            $fields[] = 'translate_'.$lang;
        }
        if($fields){
            $rules[] = array(implode(',', $fields), 'safe');
        }
        return $rules;
	}

	public function relations()
	{
		return array(
		);
	}

	public function attributeLabels()
	{
		return array(
			'id' => 'ID',
			'char_code' => tt('Short name'),
			'nominal' => tt('Nominal'),
			'value' => tt('Exchange rate'),
			'is_default' => tt('Is Default'),
			'not_parse' => tt('Not parse'),
			'date_updated' => tc('Last updated on'),
		);
	}

	public function search()
	{
		$criteria = new CDbCriteria;

		$criteria->compare('id', $this->id);
		$criteria->compare('char_code', $this->char_code, true);
		$criteria->compare('value', $this->value, true);
		$criteria->compare('is_default', $this->is_default);
		$criteria->compare('date_updated', $this->date_updated, true);


		return new CActiveDataProvider($this, array(
			'criteria' => $criteria,
			'pagination' => array(
				'pageSize' => param('adminPaginationPageSizeBig', 60),
			),
			'sort' => array(
				'defaultOrder' => array(
					'active' => 'DESC',
					'char_code' => false,
				)
			),
		));
	}

	public function parseExchangeRates()
	{
		$defaultCurrency = self::getDefaultCurrencyModel();
		if (!isset(self::$_valuteArray)) {
			switch (param('currencySource', 1)) {
				case 1:
					$this->_parseCbr();
					break;
				case 2:
					$this->_parseEcb();
					break;
				case 3:
					$this->_parseYahoo();
					break;
			}
			if (!isset(self::$_valuteArray))
				return false;
		}
		$valutes = self::$_valuteArray;

		if (isset($valutes[$defaultCurrency->char_code])) {
			foreach ($valutes as $charCode=>$valute) {
				//echo $charCode.': '.$valutes[$charCode]['value'].'-',$valutes[$defaultCurrency->char_code]['value'],'<br>';
				if ($charCode != $defaultCurrency->char_code)
					$valutes[$charCode]['value'] = $valutes[$charCode]['value']/$valutes[$defaultCurrency->char_code]['value'];
			}
			$valutes[$defaultCurrency->char_code]['value'] = 1;

		} else
			return false;
		$sorter = 1;
		foreach ($valutes as $charCode => $valute) {
			if ($valutes[$charCode]['currency_id']) {
				$currency = Currency::model()->findByPk($valutes[$charCode]['currency_id']);
				if($currency->not_parse){
					continue;
				}
				$sql = "UPDATE " . $this->tableName() .
					" SET char_code=:char_code, value=:value WHERE id=" . $valutes[$charCode]['currency_id'];
			} else {
				$sql = "INSERT INTO " . $this->tableName() . " (sorter, char_code, value)
				VALUES ({$sorter}, :char_code, :value) ";
				$sorter++;
			}

			//$valutes[$charCode]['value'] = $valutes[$charCode]['value']/$defaultValue;

			Yii::app()->db->createCommand($sql)
				->bindParam(':char_code', $charCode, PDO::PARAM_STR)
				->bindParam(':value', $valutes[$charCode]['value'], PDO::PARAM_STR)
				->execute();
		}
		return true;

	}

	private function _parseCbr() {
		$url = 'http://www.cbr.ru/scripts/XML_daily_eng.asp?date_req=' . date("d/m/Y");
		if( function_exists('curl_version')  ){
			$ch = curl_init();
			curl_setopt($ch, CURLOPT_URL, $url);
			curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
			curl_setopt($ch, CURLOPT_HEADER, 0);
			$buf = curl_exec($ch);
			curl_close($ch);
		} else {
			$buf = @file_get_contents($url);
		}
		if ($buf) {
			$xmldoc = new SimpleXMLElement($buf);
			if (isset($xmldoc->Valute)) {


				$valutes['RUB'] = array(
						'currency_id' => self::getIdByCharCode('RUB'),
						'value' => 1,
				);

				foreach ($xmldoc->Valute as $valute) {
					$charCode = (string)$valute->CharCode;
					$valutes[$charCode]['currency_id'] = self::getIdByCharCode($charCode);
					$valutes[$charCode]['value'] = self::normFloat($valute->Value)/$valute->Nominal;
				}

				self::$_valuteArray = $valutes;
			}
		} else
			return false;
	}

	private function _parseEcb() {
		$url = 'http://www.ecb.europa.eu/stats/eurofxref/eurofxref-daily.xml';
		if( function_exists('curl_version')  ){
			$ch = curl_init();
			curl_setopt($ch, CURLOPT_URL, $url);
			curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
			curl_setopt($ch, CURLOPT_HEADER, 0);
			$buf = curl_exec($ch);
			curl_close($ch);
		} else {
			$buf = @file_get_contents($url);
		}
		if ($buf) {
			$xmldoc = new SimpleXMLElement($buf);
			if (isset($xmldoc->Cube->Cube->Cube)) {


				$valutes['EUR'] = array(
						'currency_id' => self::getIdByCharCode('EUR'),
						'value' => 1,
				);

				foreach ($xmldoc->Cube->Cube->Cube as $valute) {
					$charCode = (string)$valute["currency"];
					$valutes[$charCode]['currency_id'] = self::getIdByCharCode($charCode);
					$valutes[$charCode]['value'] = 1/(float)$valute["rate"];
				}

				self::$_valuteArray = $valutes;
			}
		} else
			return false;
	}

	private function _parseYahoo() {
		$currencies = array_keys(self::getActiveCurrencyArray(2));

		$currencies = array_map(array('self','addUsd'), $currencies);
		$pairs = implode("','",$currencies);

		$query = "select * from yahoo.finance.xchange where pair in ('".$pairs."')";

		$url = "http://query.yahooapis.com/v1/public/yql?q=".urlencode($query).'&env=store://datatables.org/alltableswithkeys';

		if( function_exists('curl_version')  ){

			$ch = curl_init();
			curl_setopt($ch, CURLOPT_URL, $url);
			curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
			curl_setopt($ch, CURLOPT_HEADER, 0);
			$buf = curl_exec($ch);
			curl_close($ch);
		} else {
			$buf = @file_get_contents($url);
		}
		if ($buf) {
			$xmldoc = new SimpleXMLElement($buf);

			if (isset($xmldoc->results->rate)) {

				$valutes['USD'] = array(
						'currency_id' => self::getIdByCharCode('USD'),
						'value' => 1,
				);

				foreach ($xmldoc->results->rate as $valute) {
					if($valute->Name == "N/A")
						continue;
					$charCode = substr($valute->Name,0,3);
					$valutes[$charCode]['currency_id'] = self::getIdByCharCode($charCode);
					$valutes[$charCode]['value'] = (float)$valute->Rate;
				}

				self::$_valuteArray = $valutes;
			}
		} else
			return false;


	}

	public static function addUsd($item) {
		return $item.'USD';
	}

	public static function getCurrencySourceList() {
		$list = array(
			self::PARSE_CBR => tt('Central Bank of Russia'),
			self::PARSE_ECB => tt('Europe Central Bank'),
			self::PARSE_YAHOO => tt('Yahoo Currency Service'),
		);
		return $list;
	}

    public static function getDataByCharCode($charCode)
    {
        $dataDefault = array(
            'id' => null,
            'not_parse' => null,
        );
        $sql = "SELECT * FROM {{currency}} WHERE char_code=:char_code";
        $data = Yii::app()->db->createCommand($sql)
            ->bindParam(':char_code', $charCode, PDO::PARAM_STR)
            ->queryRow();
        return $data ? $data : $dataDefault;
    }

	public static function getIdByCharCode($charCode)
	{
		$sql = "SELECT id FROM {{currency}} WHERE char_code=:char_code";
		return Yii::app()->db->createCommand($sql)
			->bindParam(':char_code', $charCode, PDO::PARAM_STR)
			->queryScalar();
	}

	public static function getDefaultValuteId()
	{
		$sql = "SELECT id FROM {{currency}} WHERE is_default=1";
		return Yii::app()->db->createCommand($sql)->queryScalar();
	}

	public static function normFloat($float)
	{
		return str_replace(',', '.', $float);
	}

	public function beforeSave()
	{

		if ($this->scenario == 'set_default') {
			$sql = "UPDATE " . $this->tableName() . " SET is_default=0 WHERE id!=" . $this->id;
			Yii::app()->db->createCommand($sql)->execute();
		}

		return parent::beforeSave();
	}

	public function getMaxSorter()
	{
		return Yii::app()->db->createCommand()
			->select('MAX(sorter) as maxSorter')
			->from($this->tableName())
			->queryScalar();
	}

	public static function getCurrencyArray($activeOnly = false)
	{
		$where = $active = '';

		if ($activeOnly) {
			$where = ' WHERE ';
			$active = 'active = 1';
		}

		$sql = "SELECT id, char_code FROM {{currency}} {$where} {$active}";

		$arr = array();
		$all = Yii::app()->db->createCommand($sql)->queryAll();
		foreach ($all as $item) {
			$arr[$item['id']] = $item['char_code'] . ' - ' . tt($item['char_code'] . '_translate', 'currency');
		}
		return $arr;
	}

	public static function getUsedCurrenciesIds()
	{
		if (!isset(self::$_usedCurrenciesIds)) {
			self::$_usedCurrenciesIds = array_unique(
				array_merge(
					CHtml::listData(Lang::getActiveLangs(1),'currency_id', 'currency_id'),
					array(1=>Currency::getDefaultValuteId())
				)
			);
		}
		return self::$_usedCurrenciesIds;
	}

	public static function getDefaultCurrencyModel()
	{
		if (!isset(self::$_defaultCurrencyModel)) {
			self::$_defaultCurrencyModel = Currency::model()->findByAttributes(array('is_default' => 1));
		}
		return self::$_defaultCurrencyModel;
	}

	public static function getCurrentCurrencyModel()
	{
		if (!isset(self::$_currentCurrencyModel)) {
			setCurrency();

			$charCode = '';
			if (isset(Yii::app()->request->cookies['_currency'])) {
				$charCode = Yii::app()->request->cookies['_currency']->value;
			}

			if ($charCode) {
				self::$_currentCurrencyModel = Currency::model()->findByAttributes(array('char_code' => $charCode));
				if (!self::$_currentCurrencyModel) {
					// Если $charCode был не валидный, получаем модель валюты согласно языку сайта
					self::$_currentCurrencyModel = self::getModelByLang(Yii::app()->language);
					// Удаляем его из куков
					unset(Yii::app()->request->cookies['_currency']);
				}
			} else {
				self::$_currentCurrencyModel = self::getModelByLang(Yii::app()->language);
			}

			if (!self::$_currentCurrencyModel) {
				self::$_currentCurrencyModel = self::getDefaultCurrencyModel();
			}

		}
		return self::$_currentCurrencyModel;
	}

	public static function getModelByLang($lang)
	{
		if(isFree()){
			$sql = 'SELECT currency_id FROM {{lang}} WHERE name_iso=:lang';
			$currency_id = Yii::app()->db->createCommand($sql)->queryScalar(array(':lang' => $lang));
		} else {
			$currency_id = Lang::getCurrencyIdForLang($lang);
		}
		return $currency_id ? Currency::model()->findByPk($currency_id) : NULL;
	}

	public static function getDefaultCurrncyId()
	{
		return self::getDefaultCurrencyModel()->id;
	}

	public static function getDefaultCurrencyName()
	{
		return tt(self::getDefaultCurrencyModel()->char_code . '_translate', 'currency');
	}

	public static function getCurrentCurrencyName()
	{
		return tt(self::getCurrentCurrencyModel()->char_code . '_translate', 'currency');
	}

	public static function convertFromDefault($price, $round = false)
	{

		$defaultCurrency = self::getDefaultCurrencyModel();
		$currentCurrency = self::getCurrentCurrencyModel();

		$price = self::convert($price, $defaultCurrency->char_code, $currentCurrency->char_code);

		return $round ? round($price, param('round_price', 2)) : $price;
	}

	 public static function convertToDefault($price, $round = false)
	{

		$defaultCurrency = self::getDefaultCurrencyModel();
		$currentCurrency = self::getCurrentCurrencyModel();

		$price = self::convert($price, $currentCurrency->char_code, $defaultCurrency->char_code);

		return $round ? round($price, param('round_price', 2)) : $price;
	}

	public function getIsDefaultHtml()
	{
		if ($this->active) {
			if ($this->is_default == 1) {
				$onclick = 'alert("' . tt('This is the default currency') . '"); return false;';
			} else {
				$onclick = "if (this.checked != 'checked') {
					$('#set_char_code').html('" . $this->char_code . "');
					$('#currency_id').val(" . $this->id . ");
					$('#myModal').modal('show');
				};
				return false;";
			}
			return CHtml::radioButton('is_default', ($this->is_default == 1), array(
				'onclick' => $onclick
			));
		}
	}

	public $convert_data;

	private $_modelsWithCurrencyField = array(
		'Apartment',
		'User',
		'Seasonalprices'
	);

	public function setDefault()
	{
		@set_time_limit(0);
		@ini_set('max_execution_time', 0);

		if ($this->is_default || !$this->active) {
			return false;
		}

		$packetNum = 100;


		if ($this->convert_data) {
			$char_code_from = Currency::getDefaultCurrencyModel()->char_code;
			$char_code_to = $this->char_code;

			//deb($char_code_from . ' - ' . $char_code_to); exit;

			foreach ($this->_modelsWithCurrencyField as $modelName) {
				$model = new $modelName;
				$table = $model->tableName();
				$modelCurrencyFields = $model->currencyFields();

				foreach ($modelCurrencyFields as $field) {
					$sql = "SELECT `id`, `{$field}` FROM {$table}";
					$allIds = Yii::app()->db->createCommand($sql)->queryAll();

					$i = 0;
					$sqlArr = array();
					foreach ($allIds as $item) {
						if ($item[$field] <= 0) continue;
						$convertValue = (int)$this->convert($item[$field], $char_code_from, $char_code_to);

						$sqlArr[] = "UPDATE {$table} SET `{$field}`={$convertValue} WHERE id=" . $item['id'].";";
						$i++;

						if($i >= $packetNum){
							$sql = implode("\n", $sqlArr);
							//logs($sql);
							Yii::app()->db->createCommand($sql)->execute();
							$sqlArr = array();
							$i = 0;
						}
					}

					if($sqlArr){
						$sql = implode("\n", $sqlArr);
						//logs($sql);
						Yii::app()->db->createCommand($sql)->execute();
					}
				}
			}
		}

		$this->scenario = 'set_default';
		$this->is_default = 1;
		$this->update('is_default');
		self::$_defaultCurrencyModel = null;
		return true;
	}

	private static $_activeCurrency;

	public static function convert($value, $char_code_from, $char_code_to) {
		if (!$value)
			return false;

		if ($char_code_from == $char_code_to) {
			return $value;
		}
		if (!isset(self::$_activeCurrency)) {
			self::getActiveCurrency();
		}

		if (array_key_exists($char_code_to, self::$_activeCurrency)) {
			//echo $value.'---'.$char_code_from.':'.self::$_activeCurrency[$char_code_from]['value'].'-'.$char_code_to.':'.self::$_activeCurrency[$char_code_to]['value'].'<br>';
			$value = ($value * self::$_activeCurrency[$char_code_from]['value']) /self::$_activeCurrency[$char_code_to]['value'];

			return $value;
		}
		else
			return $value;
	}

	public static function getActiveCurrency() {
		if (!isset(self::$_activeCurrency)) {
			$sql = "SELECT `id`, `char_code`, `value` FROM {{currency}} WHERE active=1";
			$all = Yii::app()->db->createCommand($sql)->queryAll();
			foreach ($all as $item) {
				self::$_activeCurrency[$item['char_code']] = $item;
			}
		}
		return self::$_activeCurrency;
	}

	public static function getActiveCurrencyArray($variant = 1)
	{
		if (!isset(self::$_activeCurrency)) {
			self::getActiveCurrency();
		}

		if(!isset(self::$_currentCurrencyModel)){
			self::getCurrentCurrencyModel();
		}
		$currentCharCode = self::$_currentCurrencyModel->char_code;
		$arr = array();

		foreach (self::$_activeCurrency as $item) {
			if ($variant == 1) {
				$arr[$item['id']] = $item['char_code'] . ' - ' . tt($item['char_code'] . '_translate', 'currency');
			}
			if ($variant == 2) {
				$arr[$item['char_code']] = tt($item['char_code'] . '_translate', 'currency');
			}
			if ($variant == 3) {
				$arr[$item['id']] = tt($item['char_code'] . '_translate', 'currency');
			}
			if ($variant == 4) {

				if ($item['char_code'] == $currentCharCode) {
					$admCurrency = array(
						'url' => '',
						'linkOptions' => array('onclick' => 'return false;', 'class' => 'boldText')
					);
				} else {
					$admCurrency = array(
						'url' => Yii::app()->controller->createLangUrl(Yii::app()->language, array('currency' => $item['char_code']))
					);
				}

				$admCurrency['label'] = tt($item['char_code'] . '_translate', 'currency');
				$arr[] = $admCurrency;
			}
		}
		return $arr;
	}

	public function getName(){
		return self::getNameByCharCode($this->char_code);
	}

	public static function getNameByCharCode($char)
	{
		return tt($char . '_translate', 'currency');
	}

    public function getTranslateModel(){
        tt($this->char_code."_translate", 'currency');
        $model = TranslateMessage::model()->findByAttributes(array(
            'category' => 'module_currency',
            'message' => $this->char_code."_translate"
        ));
        return $model;
    }
}