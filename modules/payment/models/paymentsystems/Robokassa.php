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

class Robokassa extends PaymentSystem {

	public $login;
	public $password1;
	public $password2;
	public $incCurrLabel;
	public $mode;
	public $text;

    public static function workWithCurrency(){
        return array('RUR');
    }

//    public function init(){
//        $this->name = 'robokassa';
//        return parent::init();
//    }

	public function rules(){
		return array(
			array('login, password1, incCurrLabel, mode', 'required'),
			array('password2', 'safe'),
		);
	}

	public function attributeLabels(){
		return array(
			'login' => tt('Login', 'payment'),
			'password1' => tt('Password 1', 'payment'),
			'password2' => tt('Password 2', 'payment'),
			'text' => tt('Description of the system', 'payment'),
			'mode' => tt('Mode', 'payment'),
			'incCurrLabel' => tt('Available payment methods', 'payment'),
		);
	}

	public function processRequest(){
		$return = array(
			'id' => 0,
		);
		$payment = $_REQUEST["payment"];
		$outSum = (isset($_REQUEST["OutSum"])) ? $_REQUEST["OutSum"] : null;
		$invId = (isset($_REQUEST["InvId"])) ? $_REQUEST["InvId"] : null;
        $crc = isset($_REQUEST["SignatureValue"]) ? strtoupper($_REQUEST["SignatureValue"]) : '';

		if ($payment == "result") {
			$myCrc = strtoupper(md5("$outSum:$invId:$this->password2"));
		} else
			$myCrc = strtoupper(md5("$outSum:$invId:$this->password1"));

		if($crc != $myCrc || $_REQUEST['payment'] == 'fail'){
			$return['result'] = 'fail';
			if($_REQUEST['payment'] == 'fail' && $crc == $myCrc){
				$return['id'] = $invId;
			}
		} else {
			$return['id'] = $invId;
			$return['result'] = 'success';

		}
		return $return;
	}

	public function echoSuccess(){
		if($_REQUEST["payment"] == 'result'){
			echo("OK". $_REQUEST["InvId"]."\n");
			Yii::app()->end();
		}
	}

	public function processPayment(Payments $payment){

        $workWithCurrency = self::workWithCurrency();
        if(!in_array($payment->currency_charcode, $workWithCurrency)){
            $currency = $workWithCurrency[0];
            $amount = round(Currency::convert($payment->amount, $payment->currency_charcode, $currency), 0);
        } else {
            $amount = $payment->amount;
            //$currency = $payment->currency_charcode;
        }

		$sign = array(
			$this->login,
			$amount,
			$payment->id,
			$this->password1,
		);

		$sign = md5(implode(':', $sign));

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

		$url  = $this->mode
			? 'https://merchant.roboxchange.com/Index.aspx?'
			: 'http://test.robokassa.ru/Index.aspx?';

		$data = array(
			'MrchLogin'=>$this->login,
			'OutSum'=>$amount,
			'InvId'=>$payment->id,
			'Desc'=>$description,
			'SignatureValue'=>$sign,
			'IncCurrLabel'=>$this->incCurrLabel,
			'Email'=>Yii::app()->user->email,
			'Culture'=> Yii::app()->language //'ru'
		);
		$url .= http_build_query($data);

		Yii::app()->controller->redirect($url);
	}

	public function printInfo(){
		?>
		<br />
		<ul>
			<li><?php
					echo Yii::t('module_payment','Result URL: ').
						(Yii::app()->controller->createAbsoluteUrl('/payment/main/income',
							array(
								'sys' => 'robokassa',
								'payment' => 'result',
							))
						);
				?>
			</li>
			<li><?php
					echo Yii::t('module_payment','Success URL: ').
						(Yii::app()->controller->createAbsoluteUrl('/payment/main/income',
							array(
								'sys' => 'robokassa',
								'payment' => 'success',
							))
						);
				?>
			</li>
			<li><?php
					echo Yii::t('module_payment','Fail URL: ').
						(Yii::app()->controller->createAbsoluteUrl('/payment/main/income',
							array(
								'sys' => 'robokassa',
								'payment' => 'fail',
							))
						);
				?>
			</li>
		</ul>
		<?php
	}
}