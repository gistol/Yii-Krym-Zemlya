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

class currencySelectorWidget extends CWidget {
	public $type = 'dropdown';

	public function run() {
		$currencyActvie = Currency::getActiveCurrencyArray(2);
		$currentCharCode = Currency::getCurrentCurrencyModel()->char_code;

		foreach($currencyActvie as $char_code => $currencyName) {
			echo CHtml::hiddenField(
				$char_code,
				$this->getOwner()->createLangUrl(Yii::app()->language, array('currency' =>$char_code))
				, array('id'=>'currency_'.$char_code)
			);
		}

		echo CHtml::form();

		$class = (Yii::app()->theme->name == 'atlas') ? 'currency' : 'currency-drop';

		echo CHtml::dropDownList(
			'currency',
			$currentCharCode,
			$currencyActvie,
			array(
				'onchange'=>'this.form.action=$("#currency_"+this.value).val(); this.form.submit(); return false;',
				'class' => $class
			));
		echo CHtml::endForm();
	}
}