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

class HistoryChanges extends ParentModel {
	public static $_modelAttach = array(
		'Apartment',
		'ApartmentPaid',
		'ApartmentPanorama',
		'ApartmentVideo',
		'Reference',
		'Comment',
		'ApartmentsComplain',
		'ApartmentsComplainReason',
		'Bookingcalendar',
		'Bookingtable',
		'User',
		'Reviews',
		'Clients',
		'PaidOptions',
		'PaidServices',
		'BlockIp',
		'Entries',
		'EntriesCategory',
		'EntriesImage',
		'EntriesTags',
	);
	
	public static function model($className=__CLASS__) {
		return parent::model($className);
	}

	public function tableName() {
		return '{{history_changes}}';
	}

	public function rules() {
		return array(
			array('user_id, model_id', 'numerical', 'integerOnly'=>true),
			array('description', 'length', 'max'=>255),
			array('action', 'length', 'max'=>20),
			array('model_name', 'length', 'max'=>45),
			array('model_id', 'length', 'max'=>11),
			array('field', 'length', 'max'=>155),
			array('old_value, new_value', 'safe'),

			array('id, description, action, model_name, model_id, field, date_created, user_id, old_value, new_value', 'safe', 'on'=>'search'),
		);
	}

	public function relations() {
		return array(
            'user' => array(self::BELONGS_TO, 'User', 'user_id'),
		);
	}
	
	public function behaviors() {
		$arr = array();
		$arr['ERememberFiltersBehavior'] = array(
			'class' => 'application.components.behaviors.ERememberFiltersBehavior',
			'defaults' => array(),
			'defaultStickOnClear' => false
		);
		return $arr;
	}

	public function attributeLabels() {
		return array(
			'id' => 'ID',
			'description' => tt('Description', 'historyChanges'),
			'action' => tt('Action', 'historyChanges'),
			'model_name' => tt('Model name', 'historyChanges'),
			'model_id' => tt('Model ID', 'historyChanges'),
			'field' => tt('Database field', 'historyChanges'),
			'date_created' => tc('Date created'),
			'user_id' => tt('User', 'historyChanges'),
			'old_value' => tt('Old value', 'historyChanges'),
			'new_value' => tt('New value', 'historyChanges'),
		);
	}

	public function search() {
		$criteria=new CDbCriteria;

		$criteria->compare($this->getTableAlias() . '.id',$this->id,true);
		$criteria->addSearchCondition($this->getTableAlias() . '.description',$this->description,true);
		$criteria->addSearchCondition($this->getTableAlias() . '.action',$this->action,true);
		$criteria->addSearchCondition($this->getTableAlias() . '.model_name',$this->model_name,true);
		$criteria->addSearchCondition($this->getTableAlias() . '.model_id',$this->model_id,true);
		$criteria->compare($this->getTableAlias() . '.field',$this->field,true);
		if ($this->date_created) {				
			$criteria->compare($this->getTableAlias() . '.date_created', HSite::convertDateWithTimeZoneToDate($this->date_created, 'Y-m-d'), true);
		}
		$criteria->compare($this->getTableAlias() . '.user_id',$this->user_id);
		$criteria->addSearchCondition($this->getTableAlias() . '.old_value',$this->old_value,true);
		$criteria->addSearchCondition($this->getTableAlias() . '.new_value',$this->new_value,true);
		
		$criteria->addCondition('(old_value IS NOT NULL AND new_value IS NOT NULL) AND ( field <> "is_free_to" AND old_value <> "0000-00-00")');
		$criteria->with = 'user';

		return new CActiveDataProvider($this, array(
			'criteria'=>$criteria,
            'sort' => array(
                'defaultOrder' => $this->getTableAlias() . '.id DESC',
            ),
            'pagination'=>array(
                'pageSize' => param('adminPaginationPageSize', 20),
            ),
		));
	}

	public function getDescr() {
		if (!$this->model_name || !$this->model_id)
			return $this->description;

        $changeArr = array(
            'create' => '<span class="historyChangeAdded">'.tt('User added', 'historyChanges').'</span>',
            'update' => '<span class="historyChangeModified">'.tt('User modified', 'historyChanges').'</span>',
            'delete' => '<span class="historyChangeDeleted">'.tt('User deleted', 'historyChanges').'</span>',
        );

        $modelArr = array(
            'Apartment' => tc('Listings'),
			'UserAds' => tc('Listings'),
			'ApartmentPaid' => tc('Listings'),
			'ApartmentPanorama' => tc('Panorama'),
			'ApartmentVideo' => tc('Videos for listing'),
			'Reference' => tc('Listings'),
			'Comment' => tc('Comments'),
			'ApartmentsComplain' => tc('Complains'),
			'ApartmentsComplainReason' => tt('Reasons of complain', 'apartmentsComplain'),
			'Bookingcalendar' => tc('Booking'),
			'Bookingtable' => tc('Booking'),
			'User' => tc('Users'),
			'Reviews' => tt('Reviews', 'reviews'),
			'Clients' => tt('Clients', 'clients'),
			'PaidOptions' => tc('Paid services'),
			'PaidServices' => tc('Paid services'),
			'BlockIp' => tc('Blockip'),
			'Entries' => tt('Entries', 'entries'),
			'EntriesCategory' => tt('Categories of entries', 'entries'),
			'EntriesImage' => tt('Entries', 'entries'),
			'EntriesTags' => tt('Entries', 'entries'),
        );

        $modelName = isset($modelArr[$this->model_name]) ? $modelArr[$this->model_name] : $this->model_name;
		$changeName = isset($changeArr[$this->action]) ? $changeArr[$this->action] : $this->action;

		$whoCreate = tt('System', 'historyChanges');
		if (isset($this->user) && $this->user && isset($this->user->email)) {
			$whoCreate = $this->user->username. ' ( '.$this->user->email.')';
		}
		
        $str = tt('User', 'historyChanges').' '.$whoCreate;
        $str .= ' ' . $changeName;

		$addDescr = '';
		if ($this->description) {
			switch ($this->description) {
				case 'add_video':
					$addDescr = ' '.tt('video', 'historyChanges').' ';
					break;
				case 'delete_video':
					$addDescr = ' '.tt('video', 'historyChanges').' ';
					break;
				case 'add_panorama':
					$addDescr = ' '.tt('panorama', 'historyChanges').' ';
					break;
				case 'delete_panorama':
					$addDescr = ' '.tt('panorama', 'historyChanges').' ';
					break;
				case 'add_image':
					$addDescr = ' '.tt('image', 'historyChanges').' ';
					break;
				case 'delete_image':
					$addDescr = ' '.tt('image', 'historyChanges').' ';
					break;
				case 'rotate_image':
					$addDescr = ' '.tt('image', 'historyChanges').' ';
					break;
				case 'update_main_image':
					$addDescr = ' '.tt('main photo', 'historyChanges').' ';
					break;
				case 'update_reference':
					$addDescr = ' '.tt('references', 'historyChanges').' ';
					break;
				case 'update_metro_stations':
					$addDescr = ' '.tt('metro_stations', 'historyChanges').' ';
					break;
			}
		} 
		
		$modelAttach = $this->model_name;
		if ($modelAttach == 'UserAds') 
			$modelAttach = 'Apartment';
		
        /*$model = CActiveRecord::model($modelAttach)->findByPk($this->model_id);
        $url = $model ? $model->getUrl() : '';*/
		$url = '';

        if($url)
            $str .= $addDescr.' "' . CHtml::link($modelName . '" #'.$this->model_id, $url, array('target' => '_blank'));
		else 
            $str .= $addDescr.' "' . $modelName . '" #'.$this->model_id;

        if($this->action == 'update'){
            $model = new $modelAttach;
			if ($model->hasAttribute($this->field))
				$str .= ' '.tt('Database field', 'historyChanges').' "'.$model->getAttributeLabel($this->field).'"';
        }

        return $str;
    }
	
	public static function addApartmentInfoToHistory($description = '', $id = '', $action = 'create', $oldValue = '', $newValue = '') {
		$log = new HistoryChanges;
		$log->action = $action;
		$log->description = $description;
		$log->model_name = 'Apartment';
		$log->model_id = $id;
		$log->field = '';
		$log->date_created = new CDbExpression('NOW()');
		$log->user_id = Yii::app()->user->id;
		
		if ($oldValue)
			$log->old_value = $oldValue;
		if ($newValue)
			$log->new_value = $newValue;
		
		$log->save(false);
	}
}