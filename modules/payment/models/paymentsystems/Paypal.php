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

class Paypal extends PaymentSystem {

    public $email;
    public $mode;

    public static function workWithCurrency(){
        return array("USD","EUR","GBP","YEN","CAD","RUR");
    }

//    public function init(){
//        $this->name = 'paypal';
//        return parent::init();
//    }

    public function rules(){
        return array(
            array('email', 'required'),
            array('email', 'email'),
            array('mode', 'safe'),
        );
    }

    public function attributeLabels(){
        return array(
            'email' => tt('PayPal email', 'payment'),
        );
    }

    public function processRequest(){
        $return['result'] = 'fail';
        $return['id'] = intval(getReq("custom", 0));

        $payment = NULL;
        if ($return['id']) {
            $payment = Payments::model()->findByPk($return['id']);
        }

        if (!$return['id'] || !$payment) {
            logs('paypal not find payment');
            return $return;
        }

        $get_magic_quotes_exists = false;
        if(function_exists('get_magic_quotes_gpc')) {
            $get_magic_quotes_exists = true;
        }

//      http://stackoverflow.com/questions/12284341/paypal-ipn-override-charset
//        $postdata = "cmd=_notify-validate";
//        foreach ($_POST as $key => $value) {
//            if($get_magic_quotes_exists == true && get_magic_quotes_gpc() == 1) {
//                $value = urlencode(stripslashes($value));
//            } else {
//                $value = urlencode($value);
//            }
//            if ($key == 'charset') {
//                $postdata .= "&charset=utf-8";
//            } else {
//                $value = urlencode(stripslashes($value));
//                $postdata .= "&$key=$value";
//            }
//        }

        $sGatewayURL = $this->mode == Paysystem::MODE_TEST ? 'https://www.sandbox.paypal.com/cgi-bin/webscr' : 'https://www.paypal.com/cgi-bin/webscr';

//        $_POST['charset'] = 'utf-8';
        $_POST['cmd'] = '_notify-validate';

        $curl = curl_init($sGatewayURL);
        curl_setopt ($curl, CURLOPT_HEADER, 0);
        curl_setopt ($curl, CURLOPT_POST, 1);
        curl_setopt ($curl, CURLOPT_POSTFIELDS, http_build_query($_POST));
        curl_setopt ($curl, CURLOPT_SSL_VERIFYPEER, 0);
        curl_setopt ($curl, CURLOPT_RETURNTRANSFER, 1);
        curl_setopt ($curl, CURLOPT_SSL_VERIFYHOST, 1);
        curl_setopt($curl, CURLOPT_HTTPHEADER, array('Connection: Close', 'User-Agent: '.$this->email));
        $response = curl_exec ($curl);
        curl_close ($curl);

        if ($response != "VERIFIED") {
            logs("TRANSACTION_NOT_VERIFIED");
            logs($response);
            logs($_POST);
            return $return;
        }

        if (strtolower($_POST['receiver_email']) != $this->email){
            logs("INVALID_RECEIVER");
            logs($_POST);
            return $return;
        }

        if ($_POST["txn_type"] != "web_accept"){
            logs("INVALID_TRANSACTION_TYPE");
            logs($_POST);
            return $return;
        }

        $payment_status = Yii::app()->request->getParam('payment_status');
		
		// валюта
        $mc_currency = $_POST['mc_currency'];
        // стоимость
        $mc_gross = (int) $_POST['mc_gross'];

        if($payment_status == "Completed"){
            if($mc_gross == $payment->amount && $mc_currency == $payment->currency_charcode){
                $return['result'] = 'success';
            }
			else{
                logs('Incorrect payment amount');
                logs($_POST);
                $return['result'] = 'pending';
            }
        } 
		elseif($payment_status == "Pending") {
            $return['result'] = 'pending';
            $return['pending_reason'] = Yii::app()->request->getParam('pending_reason');
        } 
		else {
            $return['result'] = 'fail';
        }

        return $return;
    }

//    public function echoSuccess(){
//        if($_REQUEST["payment"] == 'result'){
//            echo("OK". $_REQUEST["InvId"]."\n");
//            Yii::app()->end();
//        }
//    }

    public function processPayment(Payments $payment){

        $workWithCurrency = self::workWithCurrency();
        if(!in_array($payment->currency_charcode, $workWithCurrency)){
            $currency = $workWithCurrency[0];
            $amount = round(Currency::convert($payment->amount, $payment->currency_charcode, $currency), 0);
        } else {
            $amount = $payment->amount;
			if($payment->currency_charcode == "RUR")
				$payment->currency_charcode = "RUB";
            $currency = $payment->currency_charcode;
        }

        $payUrl = $this->mode == Paysystem::MODE_TEST ? 'https://www.sandbox.paypal.com/cgi-bin/webscr' : 'https://www.paypal.com/cgi-bin/webscr';

		if($payment->booking_id){
            $description = tt('Purchase booking', 'booking');
			if ($payment->apartment_id && isset($payment->ad) && $payment->ad) {
				$description .= ' '.$payment->ad->getStrByLang('title');
			}
        }
		elseif ($payment->paid_id && isset($payment->paidservice) && $payment->paidservice->name) {
			$description = Yii::t('module_payment', 'Paid service #{id} ({name}) with the price {price}',
				array('{id}'=>$payment->id, '{name}'=>$payment->paidservice->name, '{price}'=>$payment->amount . ' ' . $payment->currency_charcode));
		}
		elseif(issetModule('tariffPlans') && $payment->tariff_id && isset($payment->tariffInfo) && $payment->tariffInfo->name)  {
			$description = Yii::t('module_payment', 'Paid service #{id} ({name}) with the price {price}',
				array('{id}'=>$payment->id, '{name}'=>$payment->tariffInfo->name, '{price}'=>$payment->amount . ' ' . $payment->currency_charcode));
		}
		else {
			$description = Yii::t('module_payment', 'Paid service #{id} ({name}) with the price {price}',
				array('{id}'=>$payment->id, '{name}'=> 'Unknown name', '{price}'=>$payment->amount . ' ' . $payment->currency_charcode));
		}
		
        $form = '
        <h3>'.$description.'</h3>
        <p><strong>'.tc('Cost of service').': '.$payment->amount.' '.$payment->currency_charcode.'</strong></p>
        <p><strong id="notice_mess"></strong></p>
        <form method="post" action= "'.$payUrl.'" id="paypal_form">
        <input type="hidden" name="cmd" value="_xclick">
        <input type="hidden" name="business" value="'.$this->email.'">
        <input type="hidden" name="charset" value="utf-8">
        <input type="hidden" name="item_name" value="'.CHtml::encode($description).'">
        <input type="hidden" name="custom" value="'.$payment->id.'">
        <input type="hidden" name="amount" value="'.$amount.'">
        <input type="hidden" name="currency_code" value="'.$currency.'">
        <input type="hidden" name="no_shipping" value="1">
        <input type="hidden" name="notify_url" value="'.self::getUrlResult().'">
        <input type="hidden" name="return" value="'.self::getUrlSuccess().'">
        <input type="hidden" name="cancel_return" value="'.self::getUrlFail().'">
        <input type="submit" id="submit_paypal_form" value="'.tt('Pay Now', 'payment').'">
        </form>

        <script type="text/javascript">
        $(document).ready(function(){
            $("#notice_mess").html("'.tt('Please_wait_payment', 'payment').'");
            $("#submit_paypal_form").attr("disabled", "disabled");
            $("#paypal_form").submit();
        });
        </script>
        ';

        //return $form;
		return array(
			'status' => Paysystem::RESULT_HTML,
			'message' => $form,
		);
    }

    public static function getUrlResult(){
        return Yii::app()->controller->createAbsoluteUrl('/payment/main/income',
            array(
                'sys' => 'paypal',
                'payment' => 'result',
            ));
    }

    public static function getUrlSuccess(){
        return Yii::app()->controller->createAbsoluteUrl('/payment/main/income',
            array(
                'sys' => 'paypal',
                'payment' => 'success',
            ));
    }

    public static function getUrlFail(){
        return Yii::app()->controller->createAbsoluteUrl('/payment/main/income',
            array(
                'sys' => 'paypal',
                'payment' => 'fail',
            ));
    }

    public function printInfo()
    {
        echo '<div class="flash-notice">';
        // http://stackoverflow.com/questions/12284341/paypal-ipn-override-charset
        $info[] = tt('Go to your Paypal profile', 'payment');
        $info[] = tt('Click My selling tools in the sidebar', 'payment');
        $info[] = tt('Scroll to the bottom and click PayPal button language encoding', 'payment');
        $info[] = tt('Click More options and set the encoding to UTF-8', 'payment');

        echo '<ol>';
        foreach($info as $txt){
            echo CHtml::tag('li', array(), $txt);
        }
        echo '</ol>';

        if(Yii::app()->language == 'ru'){
            $imgUrl = Yii::app()->baseUrl . '/common/images/paypal-charset-ru.png';
        } else {
            $imgUrl = Yii::app()->baseUrl . '/common/images/paypal-charset.png';
        }

        echo CHtml::link(tt('Help image', 'payment'), $imgUrl, array('class' => 'fancy'));
        echo '</div>';
    }
}