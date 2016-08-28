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

class PaidServices extends ParentModel {

	const ID_SPECIAL_OFFER = 1;
	const ID_UP_IN_SEARCH = 2;
	const ID_ADD_IN_SLIDER = 3;
	const ID_ADD_FUNDS = 4;
	const ID_BOOKING_PAY = 5;
	const ID_ADD_FUNDS_TO_AGENT = 6;

	const TYPE_FOR_AD = 1;
	const TYPE_OTHER = 9;

	public static function model($className=__CLASS__) {
		return parent::model($className);
	}

	public function tableName() {
		return '{{paid_services}}';
	}

	public function rules()	{
		return array(
            array('name', 'i18nRequired'),
			array('sorter, date_updated', 'required'),
			array('active, sorter', 'numerical', 'integerOnly'=>true),
			array($this->i18nRules('name'), 'length', 'max'=>255),
			array($this->i18nRules('description'), 'safe'),
			array('id, active, name', 'safe', 'on'=>'search'),
		);
	}

    public function i18nFields(){
        return array(
            'name' => 'varchar(255) not null',
            'description' => 'text not null'
        );
    }

	public function relations() {
		return array(
			'options' => array(self::HAS_MANY, 'PaidOptions', 'paid_service_id'),
		);
	}

	public function behaviors() {
		$arr = array();
		if (issetModule('historyChanges')) {
			$arr['ArLogBehavior'] = array(
				'class' => 'application.modules.historyChanges.components.ArLogBehavior',
			);
		}
		$arr['JsonBehavior'] = array(
			'class' => 'application.components.behaviors.JsonBehavior',
		);

		return $arr;
	}

	public function attributeLabels() {
		$labels = array(
			'id' => 'ID',
			'active' => tc('Status'),
			'sorter' => 'Sorter',
			'name' => tt('Name'),
			'description' => tt('Description'),
			'date_updated' => 'Date Updated',
			'percent' => tt('The percentage of advance payment for booking', 'booking')
		);

		return $labels;
	}

	public function search() {
		$criteria=new CDbCriteria;

		$criteria->compare('id',$this->id);
		$criteria->compare('name',$this->name,true);

        $criteria->order = 'sorter ASC';

		return new CActiveDataProvider($this, array(
			'criteria'=>$criteria,
		));
	}

    public function beforeSave(){

        if($this->isNewRecord){
            $maxSorter = Yii::app()->db->createCommand()
                ->select('MAX(sorter) as maxSorter')
                ->from($this->tableName())
                ->queryScalar();
            $this->sorter = $maxSorter+1;
        }

		if(isset($_POST['BookingPayForm'])){

		}

//        $defaultCurrencyCharCode = Currency::getDefaultCurrencyModel()->char_code;
//        if($defaultCurrencyCharCode != $this->in_currency){
//            $this->price = (int) Currency::convert($this->price, $this->in_currency, $defaultCurrencyCharCode);
//        }

        return parent::beforeSave();
    }

    public static function genImgForSlider($apartmentId){

		$mainImage = Images::getMainImageData(null, $apartmentId);

        if($mainImage){
			$imgName = $mainImage['file_name'];

            Yii::import('application.extensions.image.Image');

			$pathImg = DIRECTORY_SEPARATOR.Images::UPLOAD_DIR.DIRECTORY_SEPARATOR
				.Images::OBJECTS_DIR.DIRECTORY_SEPARATOR
				.$apartmentId.DIRECTORY_SEPARATOR
				.Images::ORIGINAL_IMG_DIR.DIRECTORY_SEPARATOR
				.$imgName;

			$sliderDir = DIRECTORY_SEPARATOR.Images::UPLOAD_DIR.DIRECTORY_SEPARATOR
				.Images::OBJECTS_DIR.DIRECTORY_SEPARATOR
				.$apartmentId.DIRECTORY_SEPARATOR
				.Images::MODIFIED_IMG_DIR.DIRECTORY_SEPARATOR;

			if($mainImage['file_name_modified']){
				$name = $mainImage['file_name_modified'];
			} else {
				$name = Images::updateModifiedName($mainImage);
			}

			$sliderImgName = 'thumb_'.param('slider_img_width', 500).'x'.param('slider_img_height', 280).'_'.$name;
			$pathImgSlider = $sliderDir . DIRECTORY_SEPARATOR . $sliderImgName;

            if(!is_dir(ROOT_PATH . $sliderDir)){
                @mkdir(ROOT_PATH . $sliderDir);
            }

            @unlink(ROOT_PATH . $pathImgSlider);
            if(!file_exists(ROOT_PATH . $pathImgSlider) && file_exists(ROOT_PATH . $pathImg)){
                $image = new Image(ROOT_PATH . $pathImg);
                $image->resizeWithEffect(param('slider_img_width', 500), param('slider_img_height', 280));

				$image->save(ROOT_PATH . $pathImgSlider);

				return true;
            } else {
				return true;
			}
        }

		return false;
    }

    public function getName(){
        return $this->getStrByLang('name');
    }

    public function getDescription(){
        return $this->getStrByLang('description');
    }

    public static function getImgForSlider(){
		$ownerActiveCond = '';
		if (param('useUserads'))
			$ownerActiveCond = ' AND owner_active = '.Apartment::STATUS_ACTIVE.' ';

		$sql = "  SELECT i.id, i.id_object, i.file_name, i.file_name_modified, i.is_main, a.title_".Yii::app()->language." AS title
				  FROM {{images}} i
				  INNER JOIN {{apartment}} a ON a.id = i.id_object
				  INNER JOIN {{apartment_paid}} ap ON ap.apartment_id = i.id_object
				  WHERE ap.paid_id = :paid_id AND ap.status = 1 AND a.active=".Apartment::STATUS_ACTIVE." {$ownerActiveCond} AND i.is_main=1 GROUP BY ap.apartment_id";

        $images = Yii::app()->db->createCommand($sql)->queryAll(true, array(
			':paid_id' => PaidServices::ID_ADD_IN_SLIDER
		));

        $imgs = array();

		if($images){
			$width = (Yii::app()->theme->name == 'atlas') ? 663 : param('slider_img_width', 500);
			$height = (Yii::app()->theme->name == 'atlas') ? 380 : param('slider_img_height', 280);

			foreach($images as $image){
				$imgs[$image['id_object']]['url'] = Apartment::getUrlById($image['id_object']);
				$imgs[$image['id_object']]['title'] = $image['title'];
				$imgs[$image['id_object']]['src'] = Images::getThumbUrl($image, $width, $height);
				$imgs[$image['id_object']]['width'] = $width;
				$imgs[$image['id_object']]['height'] = $height;
            }
        }

        return $imgs;
    }

	public function getEditHtml() {
		$edit = CHtml::link(tc('Edit'), Yii::app()->createUrl('/paidservices/backend/main/update', array('id' => $this->id)));

		return $edit;
	}

	public function getListOptions() {
		$options = array();
		if(isset($this->options)){
			foreach($this->options as $option){
				$in = tc('Cost of service'). ' ' . $option->getPriceAndCurrency() . ', ';
				$in .= tc('The service will be active') . ' ' . Yii::t('common', '{n} day', $option->duration_days);
				$options[$option->id] = $in;
			}
		}
		return $options;
	}

	public function getHtmlClassForAdmin() {
		if($this->active == 0){
			return 'service_not_active';
		} else {
			return '';
		}
	}

	public static function getListForType($type) {
		$data = PaidServices::model()->findAll('type = :type', array(':type' => $type));
		return CHtml::listData($data, 'id', 'name');
	}


	public static function applyToApartment($apartmentId, $paidId, $dateEnd, $interval = null) {
		$apartment = Apartment::model()->findByPk($apartmentId);

		if(!$apartment){
			throw new CHttpException('PaidService no valid data');
		}

		$data = Yii::app()->statePersister->load();
		if (isset($data['next_check_status'])) {
			$data['next_check_status'] = time() - BeginRequest::TIME_UPDATE;
			Yii::app()->statePersister->save($data);
		}
		unset($data);

		$apartment->scenario = 'update_status';

		$apartmentPaid = ApartmentPaid::model()->findByAttributes(array(
			'paid_id' => $paidId,
			'apartment_id' => $apartmentId,
			'user_id' => $apartment->owner_id
		));

		if(!$apartmentPaid){
			$apartmentPaid = new ApartmentPaid();
            $apartmentPaid->date_end = $dateEnd;
        } else {
            if(time() < strtotime($apartmentPaid->date_end) && $interval){
                $apartmentPaid->date_end = new CDbExpression('date_end + ' . $interval);
            }else{
                $apartmentPaid->date_end = $dateEnd;
            }
        }

		$apartmentPaid->paid_id = $paidId;
		$apartmentPaid->apartment_id = $apartment->id;
		$apartmentPaid->user_id = $apartment->owner_id;
		$apartmentPaid->date_start = new CDbExpression('NOW()');
		$apartmentPaid->status = ApartmentPaid::STATUS_ACTIVE;

		$apartmentPaid->save();

		// reloading date_end field
		$apartmentPaid = ApartmentPaid::model()->findByPk($apartmentPaid->id);

		switch($paidId){
			case PaidServices::ID_SPECIAL_OFFER:
				$apartment->is_special_offer = 1;
				$apartment->is_free_to = date('Y-m-d', strtotime($apartmentPaid->date_end));
				return $apartment->update(array('is_special_offer', 'is_free_to'));
				break;

			case PaidServices::ID_UP_IN_SEARCH:
				$apartment->date_up_search = new CDbExpression('NOW()');
				return $apartment->update('date_up_search');
				break;

			case PaidServices::ID_ADD_IN_SLIDER:
				return self::genImgForSlider($apartment->id);
				break;
		}

		return false;
	}

    public function getImageIcon($title = ''){
        $arr = array(
            self::ID_UP_IN_SEARCH => 'pays-up.png',
            self::ID_ADD_IN_SLIDER => 'pays-slider.png',
            self::ID_SPECIAL_OFFER => 'pays-special.png',
        );

        return isset($arr[$this->id]) ? CHtml::image(Yii::app()->theme->baseUrl . '/images/design/' . $arr[$this->id], $this->getName(), array('title' => $title ? $title : $this->getName())) : '';
    }

    public function isActive()
    {
        return $this->active == 1;
    }

}
