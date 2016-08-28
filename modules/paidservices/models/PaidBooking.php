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

class PaidBooking extends CFormModel
{
    public $percent;
    public $pay_immediately;
    public $empty_flag;
    public $discount_guest;
    public $consider_num_guest;

    const EMPTY_FLAG_PAY_MIN = 1;
    const EMPTY_FLAG_PAY_MAX = 2;
    const EMPTY_FLAG_PAY_NO = 3;

    public function rules()
    {
        return array(
            array('percent', 'required'),
            array('percent', 'numerical', 'integerOnly' => true, 'min' => 1, 'max' => 100),
            array('discount_guest', 'numerical', 'integerOnly' => true, 'min' => 0, 'max' => 100),
            array('pay_immediately,empty_flag,consider_num_guest', 'numerical'),
        );
    }

    public function attributeLabels()
    {
        return array(
            'percent' => tt('The percentage of advance payment for booking', 'booking'),
            'discount_guest' => tt('Discount if there are more than 1 guest(%)', 'booking'),
            'pay_immediately' => tt('Payment immediately', 'paidservices'),
            'empty_flag' => tt('Fee calculation for non-season days', 'paidservices'),
            'consider_num_guest' => tt('Taking account of number of guests while calculating the booking fee', 'paidservices'),
        );
    }

    public static function getEmptyFlagDays()
    {
        return array(
            self::EMPTY_FLAG_PAY_MIN => tt('Calculate by minimum seasonal price'),
            self::EMPTY_FLAG_PAY_MAX => tt('Calculate by maximum seasonal price'),
            self::EMPTY_FLAG_PAY_NO => tt('No calculation, price will be set by admin. Payment is immediately cancelled.'),
        );
    }
}