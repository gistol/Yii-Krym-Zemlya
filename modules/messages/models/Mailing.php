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

class Mailing extends User {
	public $countryListing;
	public $regionListing;
	public $cityListing;
	public $withListings;
	public $userType;

	const MAILING_USERS_LIMIT = 30;

	public function attributeLabels() {
		$return = array(
			'id' => 'Id',
			'username' => tt('User name', 'users'),
			'email' => tt('E-mail', 'users'),
			'phone' => Yii::t('common', 'Phone number'),
			'type' => tc('Type'),
			'userType' => tc('Type'),
			'withListings' => tt('withListings', 'messages'),
			'countryListing' => tt('countryListing', 'messages'),
			'regionListing' => tt('regionListing', 'messages'),
			'cityListing' => tt('cityListing', 'messages'),
		);
		return $return;
	}

	public function rules() {
		return array(
			array('withListings, userType, countryListing, regionListing, cityListing, type, username, email, phone', 'safe', 'on' => 'search'),
		);
	}

	public function search(){
		$criteria=new CDbCriteria;

		$criteria->compare($this->getTableAlias().'.active', 1);
		$criteria->compare($this->getTableAlias().'.username', $this->username,true);
		$criteria->compare($this->getTableAlias().'.email', $this->email,true);
		$criteria->compare($this->getTableAlias().'.phone', $this->phone,true);
		$criteria->compare($this->getTableAlias().'.role', 'registered');

		if ($this->userType)
			$criteria->compare($this->getTableAlias().'.type', $this->userType);

		if ($this->type)
			$criteria->compare($this->getTableAlias().'.type', $this->type);

		$criteria->select = $this->getTableAlias().'.username, '.$this->getTableAlias().'.email, '.$this->getTableAlias().'.phone, '.$this->getTableAlias().'.id, '.$this->getTableAlias().'.type';

		if(issetModule('location')){
			if ($this->countryListing) {
				$criteria->join = 'LEFT JOIN {{apartment}} apartmentOwner ON '.$this->getTableAlias().'.`id` = `apartmentOwner`.`owner_id`';
				$criteria->compare('apartmentOwner.loc_country', $this->countryListing);
				$criteria->group = 'apartmentOwner.owner_id';
			}

			if ($this->countryListing && $this->regionListing) {
				$criteria->join = 'LEFT JOIN {{apartment}} apartmentOwner ON '.$this->getTableAlias().'.`id` = `apartmentOwner`.`owner_id`';
				$criteria->compare('apartmentOwner.loc_country', $this->countryListing);
				$criteria->compare('apartmentOwner.loc_region', $this->regionListing);
				$criteria->group = 'apartmentOwner.owner_id';
			}

			if ($this->countryListing && $this->regionListing && $this->cityListing) {
				$criteria->join = 'LEFT JOIN {{apartment}} apartmentOwner ON '.$this->getTableAlias().'.`id` = `apartmentOwner`.`owner_id`';
				$criteria->compare('apartmentOwner.loc_country', $this->countryListing);
				$criteria->compare('apartmentOwner.loc_region', $this->regionListing);
				$criteria->compare('apartmentOwner.loc_city', $this->cityListing);
				$criteria->group = 'apartmentOwner.owner_id';
			}
		}
		else {
			if ($this->cityListing) {
				$criteria->join = 'LEFT JOIN {{apartment}} apartmentOwner ON '.$this->getTableAlias().'.`id` = `apartmentOwner`.`owner_id`';
				$criteria->compare('apartmentOwner.city_id', $this->cityListing);
				$criteria->group = 'apartmentOwner.owner_id';
			}
		}

		if ($this->withListings != '') {
			$withListings = (int) $this->withListings;
			$owners = array();

			// получаем ID пользователей с оплаченными объявлениями
			$sql = 'SELECT DISTINCT(owner_id) FROM {{apartment}}';
			$owners = Yii::app()->db->createCommand($sql)->queryColumn();

			if ($withListings) { // только с объявлениями
				if ($owners)
					$criteria->addInCondition($this->getTableAlias().'.id', $owners);
			}
			else { // все остальные
				if ($owners)
					$criteria->addNotInCondition($this->getTableAlias().'.id', $owners);
			}
		}

		return new CActiveDataProvider($this, array(
			'criteria'=>$criteria,
			'pagination'=>array(
				'pageSize'=>User::model()->count($criteria),
			),
		));
	}
}