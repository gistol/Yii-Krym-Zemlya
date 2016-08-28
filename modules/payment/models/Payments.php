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

class Payments extends ParentModel {
	const STATUS_WAITPAYMENT=1;
	const STATUS_PAYMENTCOMPLETE=2;
	const STATUS_DECLINED=3;
	const STATUS_WAITOFFLINE = 4;
	const STATUS_PENDING = 5;
	public $paysystem_name;

	public static function model($className=__CLASS__) {
		return parent::model($className);
	}

	public function tableName() {
		return '{{payments}}';
	}

	public function rules() {
		return array(
			array('amount, status', 'required'),
			array('amount, status, paysystem_id, user_id, paid_id, apartment_id, tariff_id, booking_id, agent_id', 'numerical'),
			array('currency_charcode', 'length', 'max'=>3),
			array('id, amount, status, paysystem_name, paysystem_id, tariff_id', 'safe', 'on' => 'search'),
		);
	}

	public function relations() {
		$relations = array();

		$relations['paysystem'] = array(self::BELONGS_TO, 'Paysystem', 'paysystem_id');
		$relations['user'] = array(self::BELONGS_TO, 'User', 'user_id');
		$relations['agent'] = array(self::BELONGS_TO, 'User', 'agent_id');
		$relations['ad'] = array(self::BELONGS_TO, 'Apartment', 'apartment_id');
		$relations['paidOption'] = array(self::BELONGS_TO, 'PaidOptions', 'paid_option_id');
		$relations['paidservice'] = array(self::BELONGS_TO, 'PaidServices', 'paid_id');

		if(issetModule('tariffPlans')) {
			$relations['tariffInfo'] = array(self::BELONGS_TO, 'TariffPlans', 'tariff_id');
		}

		return $relations;
	}

	public function attributeLabels() {
		return array(
			'id' => 'ID',
			'amount' => tt('Amount'),
			'status' => tt('Status'),
			'date_created' => tt('Payment date'),
			'order' => tt('Booking #'),
			'paysystem_name' => tt('Method of payment'),
			'apartment_id' => Yii::t('module_comments', 'Apartment_id'),
			'tariff_id' => Yii::t('module_tariffPlans', 'Tariff_id'),
		);
	}

	public function search() {
		$criteria = new CDbCriteria;

		$criteria->compare($this->getTableAlias().'.id', $this->id, true);
		$criteria->compare($this->getTableAlias().'.amount', $this->amount, true);
		$criteria->compare($this->getTableAlias().'.status', $this->status, true);
		$criteria->compare('paysystem.name', $this->paysystem_name, true);

		$criteria->with = (issetModule('tariffPlans')) ?
			array('user', 'paysystem', 'paidOption', 'tariffInfo') : array('user', 'paysystem', 'paidOption');

		return new CActiveDataProvider($this, array(
			'criteria' => $criteria,
			'sort' => array(
				'defaultOrder' => $this->getTableAlias().'.date_created DESC',
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
				'createAttribute' => 'date_created',
				'updateAttribute' => 'date_updated',
			),
		);
	}

	public function getPaidserviceName() {
		$return = '';
        if($this->booking_id){
            $return = tt('Purchase booking', 'booking');
        }elseif ($this->tariff_id && issetModule('tariffPlans')) {
			$return = tt('Purchase tariff plan', 'tariffPlans');
		}
		elseif(isset($this->paidservice) && $this->paidservice) {
			$return = $this->paidservice->name;
		}

		return $return;
	}

	public function getStatuses() {
		return array(
			'' => '',
			Payments::STATUS_WAITPAYMENT => tt('Wait for payment', 'payment'),
			Payments::STATUS_PAYMENTCOMPLETE => tt('Payment complete', 'payment'),
			Payments::STATUS_DECLINED => tt('Payment declined', 'payment'),
			Payments::STATUS_WAITOFFLINE => tt('Awaiting confirmation of receipt', 'payment'),
            Payments::STATUS_PENDING => tt('Payment pending', 'payment')
		);
	}

	public function returnStatusHtml() {
		$return = '';
		switch ($this->status) {
			case self::STATUS_WAITPAYMENT:
				$return = tt('Wait for payment', 'payment');
				break;
			case self::STATUS_PAYMENTCOMPLETE:
				$return = tt('Payment complete', 'payment');
				break;
			case self::STATUS_DECLINED:
				$return = tt('Payment declined', 'payment');
				break;
			case self::STATUS_WAITOFFLINE:
				$return = tt('Awaiting confirmation of receipt', 'payment');
				break;
			case self::STATUS_PENDING:
				$return = tt('Payment pending', 'payment');
				break;
		}
		return $return;
	}

	public static function getCountWait(){
		$sql = "SELECT COUNT(id) FROM {{payments}} WHERE status IN (".self::STATUS_WAITOFFLINE.", ".self::STATUS_WAITPAYMENT.")";
		return (int) Yii::app()->db->createCommand($sql)->queryScalar();
	}

	public function complete() {
        if($this->booking_id){
            $model = Bookingtable::model()->findByPk($this->booking_id);
            $model->active = Bookingtable::STATUS_CONFIRM;
			$calc = HBooking::calculateAdvancePayment($model);
			if($calc == $model->amount){
				$model->details = HBooking::$calculateHtml;
			}
            $model->update(array('active', 'details'));

            $modelBookingCalendar = new Bookingcalendar;

            $modelBookingCalendar->date_start = $model->date_start;
            $modelBookingCalendar->date_end = $model->date_end;
            $modelBookingCalendar->status = Bookingcalendar::STATUS_BUSY;
            $modelBookingCalendar->apartment_id = $model->apartment_id;
			$modelBookingCalendar->booking_id = $model->id;
            $modelBookingCalendar->save(false);

        } elseif ($this->tariff_id) { # оплата за тарифный план
			$tariffInfo = TariffPlans::getFullTariffInfoById($this->tariff_id);

			if ($tariffInfo['duration'])
				$interval = 'INTERVAL '.$tariffInfo["duration"].' DAY';
			else
				$interval = 'INTERVAL 1460 DAY';

			$dateEnd = new CDbExpression('NOW() + ' . $interval);

			TariffPlans::applyToUser($this->user_id, $this->tariff_id, $dateEnd, $interval);
		} elseif ($this->agent_id){ #перевод на счёт агента
			$agent = User::model()->findByPk($this->agent_id);
			if(!$agent)
				throw new CHttpException('Not user with ID ' . $this->user_id);

			$agent->addToBalance($this->amount);
		} else {
			if($this->paid_id != PaidServices::ID_ADD_FUNDS){
				if ($this->paid_id == PaidServices::ID_ADD_FUNDS_TO_AGENT) {
					//$userFrom = User::model()->findByPk($this->user_id);
					$userTo = User::model()->findByPk($this->agent_id);
					if(!$userTo){
						throw new CHttpException('Not user with ID ' . $this->user_id);
					}
					
					$userTo->addToBalance($this->amount);
				}
				else {
					$paidOption = $this->paidOption;

					$interval = 'INTERVAL '.$paidOption->duration_days.' DAY';
					$dateEnd = new CDbExpression('NOW() + ' . $interval);

					PaidServices::applyToApartment($this->apartment_id, $this->paid_id, $dateEnd, $interval);
				}
			}
			else{
				$user = User::model()->findByPk($this->user_id);
				if(!$user){
					throw new CHttpException('Not user with ID ' . $this->user_id);
				}
				$user->addToBalance($this->amount);
			}
		}

		$this->status = Payments::STATUS_PAYMENTCOMPLETE;
		$this->update('status');

		return true;
	}
}