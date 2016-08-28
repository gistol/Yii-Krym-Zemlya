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

class TariffPlans extends ParentModel {
	const DEFAULT_TARIFF_PLAN_ID = 1;
	private static $_cache;

	const STATUS_INACTIVE = 0;
	const STATUS_ACTIVE = 1;

	public static function model($className=__CLASS__) {
		return parent::model($className);
	}

	public function behaviors() {
		return array(
			'ERememberFiltersBehavior' => array(
				'class' => 'application.components.behaviors.ERememberFiltersBehavior',
				'defaults'=>array(),
				'defaultStickOnClear'=>false
			),
			'AutoTimestampBehavior' => array(
				'class' => 'zii.behaviors.CTimestampBehavior',
				'createAttribute' => 'date_created',
				'updateAttribute' => 'date_updated',
				'setUpdateOnCreate' => false,
			),
		);
	}

	public function tableName() {
		return '{{tariff_plans}}';
	}

	public function rules() {
		return array(
			//array('duration, price', 'required'),
			array('name', 'i18nRequired'),
			array('show_address, show_phones', 'boolean'),
			array('limit_objects, limit_photos', 'numerical'),
			array('duration, price', 'numerical'),

			array('duration, price', 'required', 'except' => 'default_tariff_plan_edit'),
			array('duration, price', 'numerical', 'min' => 1, 'except' => 'default_tariff_plan_edit'),

			array('name', 'i18nLength', 'max' => 255),
			array($this->getI18nFieldSafe(), 'safe'),

			array('id, duration, price, show_address, show_phones, limit_objects, limit_photos, price', 'safe', 'on'=>'search'),
		);
	}

	public function i18nFields(){
		return array(
			'name' => 'varchar(255) not null',
			'description' => 'text not null',
		);
	}

	public function attributeLabels() {
		return array(
			'id' => 'ID',
			'name' => tt('Name', 'tariffPlans'),
			'description' => tt('Description', 'tariffPlans'),
			'limit_objects' => tt('Limit_objects', 'tariffPlans'),
			'limit_photos' => tt('Limit_photos', 'tariffPlans'),
			'price' => tt('Price', 'tariffPlans'),
			'duration' => tt('Duration', 'tariffPlans'),
			'show_address' => tt('Show_address', 'tariffPlans'),
			'show_phones' => tt('Show_phones', 'tariffPlans'),
			'date_updated' => tc('Last updated on'),
			'active' => tt('Status', 'tariffPlans'),
		);
	}

	public function search() {
		$criteria=new CDbCriteria;

		$criteria->compare('id',$this->id);

		$tmp = 'name_'.Yii::app()->language;
		$criteria->compare($tmp, $this->$tmp, true);

		$tmp = 'description_'.Yii::app()->language;
		$criteria->compare($tmp, $this->$tmp, true);

		$criteria->compare('limit_objects', $this->limit_objects, true);
		$criteria->compare('limit_photos', $this->limit_photos, true);
		$criteria->compare('price', $this->price, true);
		$criteria->compare('duration', $this->duration, true);
		$criteria->compare('show_address', $this->show_address);
		$criteria->compare('show_phones', $this->show_phones);
		$criteria->compare('date_updated', $this->date_updated, true);

		return new CustomActiveDataProvider($this, array(
			'criteria' => $criteria,
			'pagination' => array(
				'pageSize' => param('adminPaginationPageSize', 20),
			),
		));
	}

	public function beforeDelete(){
		// удалять тарифы пользователей. деактивировать объявления.
		$sql = 'SELECT user_id FROM {{users_tariff_plans}} WHERE tariff_id = '.$this->id;
		$res = Yii::app()->db->createCommand($sql)->queryColumn();

		if ($res) {
			$usersIds = array();
			if (count($res) > 0) {
				foreach($res as $userId) {
					$usersIds[$userId] = $userId;
				}
			}

			if (count($usersIds) > 0) {
				TariffPlans::deactivateUserAdsByTariffPlan($usersIds);
			}
		}

		$sql = 'UPDATE {{users_tariff_plans}} SET status = '.UsersTariffPlans::STATUS_NO_ACTIVE.' WHERE tariff_id = '.$this->id;
		Yii::app()->db->createCommand($sql)->execute();

		return parent::beforeDelete();
	}

	public function getName(){
		return $this->getStrByLang('name');
	}

	public function setName($value){
		$this->setStrByLang('name', $value);
	}

	public function getDescription(){
		return $this->getStrByLang('description');
	}

	public function getDescriptionForBuy() {
		$html = '';
		$description = $this->getDescription();
		if ($description)
			$html .= '<div class="buy_descr_tariff">'.$description.'</div>';

		if($this->price)
			$html .= '<div class="buy_price_tariff">'.tt('Price', 'tariffPlans').': <strong>'.$this->price.'</strong> '.Currency::getDefaultCurrencyModel()->name.'</div>';
		else
			$html .= '<div class="buy_price_tariff">'.tt('Price', 'tariffPlans').': <span class="tariff_unlimited">'.tt('Price is free', 'tariffPlans').'</span></div>';

		if($this->duration)
			$html .= '<div class="buy_duration_tariff">'.tt('Duration', 'tariffPlans').': <strong>'. $this->duration.'</strong> '.tt('days', 'tariffPlans').'</div>';
		else
			$html .= '<div class="buy_duration_tariff">'.tt('Duration', 'tariffPlans').': <span class="tariff_unlimited">'.tt('Unlimited', 'tariffPlans').'</span></div>';

		if($this->limit_objects)
			$html .= '<div class="buy_objects_tariff">'.tt('Limit_objects', 'tariffPlans').': <strong>'.$this->limit_objects.'</strong></div>';
		else
			$html .= '<div class="buy_objects_tariff">'.tt('Limit_objects', 'tariffPlans').': <span class="tariff_unlimited">'.tt('Unlimited', 'tariffPlans').'</span></div>';

		if($this->limit_photos)
			$html .= '<div class="buy_photos_tariff">'.tt('Limit_photos', 'tariffPlans').': <strong>'.$this->limit_photos.'</strong></div>';
		else
			$html .= '<div class="buy_photos_tariff">'.tt('Limit_photos', 'tariffPlans').': <span class="tariff_unlimited">'.tt('Unlimited', 'tariffPlans').'</span></div>';

		if($this->show_address)
			$html .= '<div class="buy_on_map_tariff">'.tt('Show_address', 'tariffPlans').': <strong>'.tc('Yes').'</strong></div>';
		else
			$html .= '<div class="buy_on_map_tariff">'.tt('Show_address', 'tariffPlans').': <strong>'.tc('No').'</strong></div>';

		if($this->show_phones)
			$html .= '<div class="buy_on_map_tariff">'.tt('Show_phones', 'tariffPlans').': <strong>'.tc('Yes').'</strong></div>';
		else
			$html .= '<div class="buy_on_map_tariff">'.tt('Show_phones', 'tariffPlans').': <strong>'.tc('No').'</strong></div>';

		return $html;
	}

	public function setDescription($value){
		$this->setStrByLang('description', $value);
	}

	public function getLimitObjectForGrid() {
		if ($this->limit_objects)
			return $this->limit_objects;
		else
			return tt('Unlimited');
	}

	public function getLimitPhotosForGrid() {
		if ($this->limit_photos)
			return $this->limit_photos;
		else
			return tt('Unlimited');
	}

	public function getPriceForGrid() {
		if ($this->price)
			return $this->price;
		else
			return tt('Price is free');
	}

	public function getDurationForGrid() {
		if ($this->duration)
			return $this->duration;
		else
			return tt('Unlimited');
	}

	public static function getTariffPlansHtml($withDateEnd = false, $withAddLink = false, $user = null) {
		if ($user && ($user->role == User::ROLE_MODERATOR || $user->role == User::ROLE_ADMIN))
			return '';

		$content = '';
		$htmlArray = $issetTariff = array();

		# применённые тарифы
		if(isset($user->userTariff)){
			$issetTariff[$user->userTariff['id']] = $user->userTariff;
		}

		# все тарифы
		$allTariffs = self::getAllTariffPlans();
		if ($allTariffs) {
			foreach($allTariffs as $tariff) {
				if (!Yii::app()->user->checkAccess("tariff_plans_admin")) {
					if (array_key_exists($tariff['id'], $issetTariff)) {
						$html = '<div class="paid_row">'.CHtml::link(
								$tariff['name'],
								array('/paidservices/main/index',
									'id'=>$user->id,
									'paid_id'=>$tariff['id'],
								),
								array('class'=>'fancy mgp-open-ajax'));
						if (isset($issetTariff[$tariff['id']]['tariff_date_end_format']) && $issetTariff[$tariff['id']]['tariff_date_end_format'])
							$html .= $withDateEnd ? '<span class="valid_till"> ('. tc('is valid till') . ': ' . $issetTariff[$tariff['id']]['tariff_date_end_format']. ')</span>' : '';
						else
							$html .= $withDateEnd ? '<span class="valid_till">('. tc('is valid till') . ': ' . tc('unlimited'). ')</span>' : '';
						$html .= '</div>';
					}
					else {
						$html = '<div class="paid_row_no"><span class="boldText">'.CHtml::link(
								$tariff['name'],
								array('/paidservices/main/index',
									'id'=>$user->id,
									'paid_id'=>$tariff['id'],
								),
								array('class'=>'fancy mgp-open-ajax')).'</span>';
						$html .= '</div>';
					}

					if (isset($html) && $html) {
						$htmlArray[] = $html;
						unset($html);
					}
				}
				else {
					if (array_key_exists($tariff['id'], $issetTariff) && $withDateEnd) {
						$html = '<div class="paid_row"><span class="boldText">'.$tariff['name'].'</span>';

						if (isset($issetTariff[$tariff['id']]['tariff_date_end_format']) && $issetTariff[$tariff['id']]['tariff_date_end_format'])
							$html .= $withDateEnd ? '<span class="valid_till"> ('. tc('is valid till') . ': ' . $issetTariff[$tariff['id']]['tariff_date_end_format']. ')</span>' : '';
						else
							$html .= $withDateEnd ? '<span class="valid_till">('. tc('is valid till') . ': ' . tc('unlimited'). ')</span>' : '';
						$html .= '</div>';
					}

					if (isset($html) && $html) {
						$htmlArray[] = $html;
						unset($html);
					}
				}
			}
		}

		if(count($htmlArray) > 0) {
			$content = implode('', $htmlArray);
		}
		else {
			$content = '<div class="paid_row">'.tc('No').'</div>';
		}

		if(Yii::app()->user->checkAccess("tariff_plans_admin") && $withAddLink){
			$addUrl = Yii::app()->createUrl('/tariffPlans/backend/main/addPaid', array(
				'id' => $user->id,
				'withDate' => (int) $withDateEnd,
			));

			$content .= CHtml::link(tc('Change'), $addUrl,	array(
				'class' => 'tempModal boldText',
				'title' => tt('Apply a tariff plan to the user', 'tariffPlans')
			));
		}

		return CHtml::tag('div', array('id' => 'paid_row_el_'.$user->id), $content);
	}

	private static function setCache() {
		$tariffs = TariffPlans::model()
			->cache(param('cachingTime', 1209600), self::getDependency())
			->findAll();

		if ($tariffs) {
			foreach($tariffs as $tariff) {
				self::$_cache[$tariff->id]['id'] = $tariff->id;
				self::$_cache[$tariff->id]['active'] = $tariff->active;
				self::$_cache[$tariff->id]['name'] = $tariff->name;
				self::$_cache[$tariff->id]['description'] = $tariff->description;
				self::$_cache[$tariff->id]['descriptionForBuy'] = $tariff->getDescriptionForBuy();
				self::$_cache[$tariff->id]['limitObjects'] = $tariff->limit_objects;
				self::$_cache[$tariff->id]['limitPhotos'] = $tariff->limit_photos;
				self::$_cache[$tariff->id]['price'] = $tariff->price;
				self::$_cache[$tariff->id]['duration'] = $tariff->duration;
				self::$_cache[$tariff->id]['showAddress'] = $tariff->show_address;
				self::$_cache[$tariff->id]['showPhones'] = $tariff->show_phones;
			}
		}
	}

	public static function getFullTariffInfoById($tariffId = null) {
		if(!isset(self::$_cache)){
			self::setCache();
		}

		if (isset(self::$_cache[$tariffId]))
			return self::$_cache[$tariffId];

		return null;
	}

	public static function getAllTariffPlans($onlyActive = false, $withOutDefault = false, $onlyWithPrice = false) {
		if(!isset(self::$_cache)){
			self::setCache();
		}

		$return = self::$_cache;

		if ($onlyActive) {
			foreach($return as $k => $tariff) {
				if ($tariff['active'] != TariffPlans::STATUS_ACTIVE)
					unset($return[$k]);
			}
		}

		if ($withOutDefault) {
			foreach($return as $k => $tariff) {
				if ($tariff['id'] == TariffPlans::DEFAULT_TARIFF_PLAN_ID)
					unset($return[$k]);
			}
		}

		if ($onlyWithPrice) {
			foreach($return as $k => $tariff) {
				if ($tariff['price'] <= 0)
					unset($return[$k]);
			}
		}

		return $return;
	}

	public static function getDependency(){
		return new CDbCacheDependency('SELECT MAX(date_updated) FROM {{tariff_plans}}');
	}

	public static function applyToUser($userId, $tariffId, $dateEnd, $interval = null, $setByAdmin = false) {
		$return = false;

		$user = User::model()->findByPk($userId);

		if(!$user){
			throw new CHttpException('User no valid data');
		}

		$data = Yii::app()->statePersister->load();
		if (isset($data['next_check_status_users_tariffs'])) {
			$data['next_check_status_users_tariffs'] = time() - BeginRequest::TIME_UPDATE_TARIFF_PLANS;
			Yii::app()->statePersister->save($data);
		}
		unset($data);

		##############################
		// deactivate other tariffs
		$tariffsToDeactivate = UsersTariffPlans::model()->findAllByAttributes(array(
			'user_id' => $userId,
			'status' => UsersTariffPlans::STATUS_ACTIVE,
		));

		if ($tariffsToDeactivate) {
			foreach($tariffsToDeactivate as $tariffDeactivate) {
				$tariffDeactivate->status = UsersTariffPlans::STATUS_NO_ACTIVE;
				$tariffDeactivate->update(array('status'));
			}
		}

		##############################

		$userTariffPlansModel = UsersTariffPlans::model()->findByAttributes(array(
			'tariff_id' => $tariffId,
			'user_id' => $userId,
		));

		if(!$userTariffPlansModel){
			$userTariffPlansModel = new UsersTariffPlans();
			$userTariffPlansModel->date_end = $dateEnd;
		} else {
			if(time() < strtotime($userTariffPlansModel->date_end) && $interval){
				$userTariffPlansModel->date_end = new CDbExpression('date_end + ' . $interval);
			}
			else{
				$userTariffPlansModel->date_end = $dateEnd;
			}
		}

		$userTariffPlansModel->user_id = $userId;
		$userTariffPlansModel->tariff_id = $tariffId;
		$userTariffPlansModel->date_start = new CDbExpression('NOW()');
		$userTariffPlansModel->status = UsersTariffPlans::STATUS_ACTIVE;
		$userTariffPlansModel->setByAdmin = ($setByAdmin) ? 1 : 0;

		if ($userTariffPlansModel->save())
			$return = true;

		return $return;
	}

	public static function getCountUserObjects($userId) {
		if (!$userId)
			$userId = Yii::app()->user->id;

		$sql = 'SELECT COUNT(id) FROM {{apartment}} WHERE owner_id = '.$userId.' AND active <> '.Apartment::STATUS_DRAFT.' AND deleted <> 1 AND owner_active = '.Apartment::STATUS_ACTIVE.' AND price_type IN('.implode(",", array_keys(HApartment::getPriceArray(Apartment::PRICE_SALE, true))).')';
		$result = Yii::app()->db->createCommand($sql)->queryScalar();

		return $result;
	}

	public static function getTariffInfoByUserId($userId = null) {
		$id = $name = $description = $limitObjects = $limitPhotos = $price = $duration = $showAddress = $showPhones = $tariffDateStart = $tariffDateEnd = $tariffDateStartFormat = $tariffDateEndFormat = $tariffStatus = null;

		if (!$userId)
			$userId = Yii::app()->user->id;

		if ($userId) {
			$userModel = User::model()->with()->findByPk($userId);

			if (isset($userModel->userTariff) && $userModel->userTariff) {
				$id = $userModel->userTariff['id'];
				$name = $userModel->userTariff['name'];
				$description = $userModel->userTariff['description'];
				$limitObjects = $userModel->userTariff['limitObjects'];
				$limitPhotos = $userModel->userTariff['limitPhotos'];
				$price = $userModel->userTariff['price'];
				$duration = $userModel->userTariff['duration'];
				$showAddress = $userModel->userTariff['showAddress'];
				$showPhones = $userModel->userTariff['showPhones'];

				if ($userModel->role == User::ROLE_ADMIN || $userModel->role == User::ROLE_MODERATOR) {
					$limitObjects = $limitPhotos = 0; # unlimited
					$showAddress = $showPhones = 1; # unlimited
				}
				
				$tariffDateStart = (isset($userModel->userTariff['tariff_date_start']))  ? $userModel->userTariff['tariff_date_start'] : '';
				$tariffDateEnd = (isset($userModel->userTariff['tariff_date_end'])) ? $userModel->userTariff['tariff_date_end'] : '';

				$tariffDateStartFormat = (isset($userModel->userTariff['tariff_date_start']))  ? $userModel->userTariff['tariff_date_start_format'] : '';
				$tariffDateEndFormat = (isset($userModel->userTariff['tariff_date_end'])) ? $userModel->userTariff['tariff_date_end_format'] : '';

				$tariffStatus = (isset($userModel->userTariff['tariff_status'])) ? $userModel->userTariff['tariff_status'] : TariffPlans::STATUS_ACTIVE;
			}
		}

		return compact("id", "name", "description", "limitObjects", "limitPhotos", "price", "duration", "showAddress", "showPhones", "tariffDateStart", "tariffDateEnd", "tariffDateStartFormat", "tariffDateEndFormat", "tariffStatus");
	}

	public static function checkAllowUserActivateAd($userId = null, $isReturn = false, $comparison = '>=') {
		if ($userId) {
			$tariffInfo = self::getTariffInfoByUserId($userId);
			$activeUserAds = self::getCountUserObjects($userId);

			if ($tariffInfo && count($tariffInfo)) {
				$limit = $tariffInfo['limitObjects'];

				switch ($comparison) {
					case "=":  $allow = ($activeUserAds == $limit) ? false : true; break;
					case "!=": $allow = ($activeUserAds != $limit) ? false : true; break;
					case ">=": $allow = ($activeUserAds >= $limit) ? false : true; break;
					case "<=": $allow = ($activeUserAds <= $limit) ? false : true; break;
					case ">":  $allow = ($activeUserAds > $limit) ? false : true; break;
					case "<":  $allow = ($activeUserAds < $limit) ? false : true; break;
					default: $allow = false;
				}

				if ($limit && $limit > 0) {
					if(!$allow) {
						if ($isReturn) {
							return false;
						}
						else {
							if(!Yii::app()->request->isAjaxRequest) {
								Yii::app()->user->setFlash('error', Yii::t('module_tariffPlans', 'Exhausted the limit of {limit} active ads, deactivate other ads or <a href="{link}">change tariff plan</a>', array('{limit}' => $limit, '{link}' => Yii::app()->createAbsoluteUrl('/tariffPlans/main/index'))));
							}
							else {
								echo "<div class='flash-error'>".Yii::t('module_tariffPlans', 'Exhausted the limit of {limit} active ads, deactivate other ads or <a href="{link}">change tariff plan</a>', array('{limit}' => $limit, '{link}' => Yii::app()->createAbsoluteUrl('/tariffPlans/main/index')))."</div>";
							}

							Yii::app()->end();
						}
					}
				}
			}
		}

		return true;
	}

	public static function deactivateUserAdsByTariffPlan($users = array()) {
		if ($users && count($users)) {
			foreach($users as $id) {
				$criteria = new CDbCriteria;
				$criteria->addCondition('owner_id = '.$id);
				$criteria->addCondition('active <> '.Apartment::STATUS_DRAFT);
				$criteria->addCondition('owner_active = '. Apartment::STATUS_ACTIVE);
				$criteria->order = 'date_created DESC';
				$usersAds = Apartment::model()->findAll($criteria);

				if ($usersAds) {
					$cntAds = count($usersAds);

					$defaultTariffInfo = TariffPlans::getFullTariffInfoById(TariffPlans::DEFAULT_TARIFF_PLAN_ID);
					$limit = $defaultTariffInfo['limitObjects'];

					if ($limit && $cntAds > $limit) {
						$adsToDeactivate = array_slice($usersAds, $limit);

						if ($adsToDeactivate) {
							foreach($adsToDeactivate as $ad) {
								if ($ad) {
									//$ad->active = Apartment::STATUS_INACTIVE;
									$ad->owner_active = Apartment::STATUS_INACTIVE;
									//$ad->update(array('active', 'owner_active'));
									$ad->update(array('owner_active'));
								}
							}
						}
					}
				}
			}
		}
	}

	public static function checkAllowShowPhone() {
		if (Yii::app()->user->isGuest)
			return false;

		$tariffInfo = self::getTariffInfoByUserId(Yii::app()->user->id);

		if ($tariffInfo) {
			if ($tariffInfo['showPhones'])
				return true;
		}
		return false;
	}

	public static function checkAllowShowAddress() {
		if (Yii::app()->user->isGuest)
			return false;

		$tariffInfo = self::getTariffInfoByUserId(Yii::app()->user->id);

		if ($tariffInfo) {
			if ($tariffInfo['showAddress'])
				return true;
		}
		return false;
	}

	public static function checkDeactivateTariffUsers() {
		$activePaids = UsersTariffPlans::model()->findAll('date_end <= NOW() AND status=' . UsersTariffPlans::STATUS_ACTIVE);

		if ($activePaids) {
			$users = array();
			foreach ($activePaids as $paid) {
				$allow = true;

				$tariffInfo = TariffPlans::getFullTariffInfoById($paid->tariff_id);
				if ($tariffInfo && $tariffInfo['duration'] <= 0 && !$paid->setByAdmin)
					$allow = false;

				if ($allow) {
					$paid->status = UsersTariffPlans::STATUS_NO_ACTIVE;

					if (!$paid->update(array('status'))) {
						//deb($paid->getErrors());
					}

					$users[$paid->user_id] = $paid->user_id;
				}
			}

			if ($users)
				TariffPlans::deactivateUserAdsByTariffPlan($users);
		}
	}
}