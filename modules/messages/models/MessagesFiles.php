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

class MessagesFiles extends CActiveRecord {
	public static function model($className=__CLASS__) {
		return parent::model($className);
	}

	public function tableName() {
		return '{{messages_files}}';
	}

	public function rules() {
		return array(
			array('id_message, file_path, orig_file_path', 'required'),
			array('id_message', 'numerical', 'integerOnly' => true),
			array('id, id_message, file_path, orig_file_path', 'safe', 'on' => 'search'),
		);
	}

	public function relations() {
		return array(
			'messages' => array(self::BELONGS_TO, 'Messages', 'id_message'),
		);
	}

	public function attributeLabels() {
		return array(
			'id' => 'ID',
			'id_message' => 'ID позиции(наименования)',
			'file_path' => 'Изображение',
		);
	}

	public function search() {
		$criteria = new CDbCriteria;

		$criteria->compare('id', $this->id);
		$criteria->compare('id_message', $this->id_message);

		return new CActiveDataProvider($this, array(
			'criteria' => $criteria,
			'sort' => array(
				'defaultOrder' => 'sorter ASC',
			),
			'pagination' => array(
				'pageSize' => param('adminPaginationPageSize', 20),
			)
		));
	}

	public function behaviors(){
		return array(
			'AutoTimestampBehavior' => array(
				'class' => 'zii.behaviors.CTimestampBehavior',
				'createAttribute' => 'date_updated',
				'updateAttribute' => 'date_updated',
			),
		);
	}

	public function beforeDelete() {
		$sql = 'SELECT file_path FROM {{messages_files}} WHERE id="'.$this->id.'"';
		$item = Yii::app()->db->createCommand($sql)->queryScalar();

		$message = new Messages();

		if ($item) {
			if (file_exists($message->uploadPath.'/'.$item)) {
				unlink($message->uploadPath.'/'.$item);
			}
		}
		return parent::beforeDelete();
	}
}