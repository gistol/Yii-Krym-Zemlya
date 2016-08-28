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

class Messages extends ParentModel {
	public $username;

	const STATUS_UNREAD_USER = 0;
	const STATUS_READ_USER = 1;

	const MESSAGE_INACTIVE = 0;
	const MESSAGE_ACTIVE = 1;

	const MESSAGE_NOT_DELETED = 0;
	const MESSAGE_DELETED = 1;

	public $uploadPath;
	public $supportExt = 'jpg, png, gif, doc, docx, pdf';
	public $supportExtForUploader = 'jpg|png|gif|doc|docx|pdf';
	public $fileMaxSize = 2097152; /* 1024 * 1024 * 2 - 2 MB */

	public function init() {
		$fileMaxSize['postSize'] = toBytes(ini_get('post_max_size'));
		$fileMaxSize['uploadSize'] = toBytes(ini_get('upload_max_filesize'));

		$this->fileMaxSize = min($fileMaxSize);

		$this->preparePaths();
		parent ::init();
	}

	public function preparePaths() {
		$this->uploadPath = Yii::getPathOfAlias('webroot.uploads.messages');
	}

	public static function model($className=__CLASS__) {
		return parent::model($className);
	}

	public function tableName() {
		return '{{messages}}';
	}

	public function scopes() {
		return array(
			'unReadUser'=>array(
				'condition' => $this->getTableAlias().'.is_read = "'.self::STATUS_UNREAD_USER.'" AND '.$this->getTableAlias().'.is_deleted = "'.self::MESSAGE_NOT_DELETED.'"'
			),
			'isReadUser'=>array(
				'condition' => $this->getTableAlias().'.is_read = "'.self::STATUS_READ_USER.'" AND '.$this->getTableAlias().'.is_deleted = "'.self::MESSAGE_NOT_DELETED.'"'
			),
		);
	}

	public function criteriaUser($idUserFrom = 0, $idUserTo = null) {
		$idUser = (int) $idUserFrom;
		$idUserTo = (int) $idUserTo;
		if (!$idUserTo)
			$idUserTo = Yii::app()->user->id;

		$owner = $this->getOwner();
		$criteria = $owner->getDbCriteria();
		$alias = $owner->getTableAlias();

		$criteria->mergeWith(array(
			'condition' => $alias.'.id_userTo = '.$idUserTo. ' AND '.$alias.'.id_userFrom = '. $idUserFrom,
		));

		return $owner;
	}

	public function behaviors(){		
		$arr = array();
		$arr['AutoTimestampBehavior'] = array(
			'class' => 'zii.behaviors.CTimestampBehavior',
			'createAttribute' => 'date_send',
			'updateAttribute' => 'date_updated',
		);

		return $arr;
	}

	public function rules() {
		return array(
			array('message', 'required'),
			array('message', 'length', 'min' => 3),
			array('message', 'filter', 'filter' => array(new CHtmlPurifier(), 'purify')),
			array('id, id_userFrom, id_userTo, is_read, date_send, date_read, status, is_deleted', 'safe', 'on' => 'search'),
		);
	}

	public function relations() {
		return array(
			'userInfoFrom' => array(self::BELONGS_TO, 'User', 'id_userFrom'),
			'userInfoTo' => array(self::BELONGS_TO, 'User', 'id_userTo'),
			'messagesFiles' => array(self::HAS_MANY, 'MessagesFiles', 'id_message')
		);
	}

	public function attributeLabels() {
		return array(
			'id' => tt('ID', 'messages'),
			'id_userFrom' => tt('Sender ID', 'messages'),
			'id_userTo' => tt('Recipient ID', 'messages'),
			'message' => tt('Message', 'messages'),
			'is_read' => tt('Read', 'messages'),
			'date_send' => tt('Sending date', 'messages'),
			'date_read' => tt('Reading date', 'messages'),
			'file' => tt('Attach file', 'messages'),
			'status' => tt('Status', 'messages'),
			'files' => tt('Files', 'messages'),
			'from_listing_id' => tt('fromListingId', 'messages'),
		);
	}

	public function beforeSave(){
		$this->status = self::MESSAGE_ACTIVE;

		return parent::beforeSave();
	}


	public function afterSave() {
		if ($this->id_userTo) {
			$user = User::model()->findByPk($this->id_userTo);

			if ($user) {
				$sql = 'SELECT id FROM '.Yii::app()->session->sessionTableName.' WHERE user_id = '.$user->id.' AND expire > '.time();
				$res = Yii::app()->db->createCommand($sql)->queryScalar();

				# полагаем, что пользователь оффлайн - отправляем уведомление на почту
				if(!$res) {
					$user->messageEmailSend = $this->message;

					$notifier = new Notifier;
					$notifier->raiseEvent('onNewPrivateMessage', $user, array('user' => $user));

					/*if (!$resSend) {
						$errorsSend[] = $userModel->email;
					}*/
				}
			}
		}

		return parent::afterSave();
	}
	
	protected function afterFind() {		
		$dateFormat = param('dateFormat', 'd.m.Y H:i:s');
		$this->date_send = date($dateFormat, strtotime($this->date_send));
		$this->date_read = date($dateFormat, strtotime($this->date_read));
		
		parent::afterFind();
	}

	public function search(){
		$criteria = new CDbCriteria();

		$criteria->compare($this->getTableAlias().'.id', $this->id);

		if ($this->id_userFrom) {
			$criteria->addCondition('userInfoFrom.username LIKE "%'.$this->id_userFrom.'%"');
		}

		if ($this->id_userTo) {
			$criteria->addCondition('userInfoTo.username LIKE "%'.$this->id_userTo.'%"');
		}

		$criteria->compare($this->getTableAlias().'.message', $this->message, true);
		$criteria->compare($this->getTableAlias().'.is_read', $this->is_read);

		if ($this->date_send)
			$criteria->compare($this->getTableAlias().'.date_send', $this->date_send, true);

		//$criteria->order = $this->getTableAlias().'.id DESC';

		$criteria->compare($this->getTableAlias().'.status', $this->status);

		$criteria->addCondition($this->getTableAlias().'.is_deleted ='.Messages::MESSAGE_NOT_DELETED);

		$criteria->with = array('userInfoFrom', 'userInfoTo');

		return new CActiveDataProvider($this, array(
			'criteria'=>$criteria,
			'pagination'=>array(
				'pageSize'=>param('adminPaginationPageSize', 20),
			),
			'sort'=>array(
				'defaultOrder'=> $this->getTableAlias().'.id DESC',
			)
		));
	}

	public function beforeDelete() {
		$sql = 'SELECT id, file_path FROM {{messages_files}} WHERE id_message="'.$this->id.'"';
		$items = Yii::app()->db->createCommand($sql)->queryAll();

		$message = new Messages();

		if ($items) {
			foreach ($items as $item) {
				if (file_exists($message->uploadPath.'/'.$item['file_path'])) {
					unlink($message->uploadPath.'/'.$item['file_path']);
					MessagesFiles::model()->deleteByPk($item['id']);
				}
			}
		}

		return parent::beforeDelete();
	}

	public static function getAllContactUsers($idUserTo = null) {
		$allUsers = $pages = null;

		if ($idUserTo) {
			$idUsers = array();

			$sql = 'SELECT DISTINCT(id_userFrom) FROM {{messages}}
				WHERE id_userTo = '.(int) Yii::app()->user->id.' AND status = '.self::MESSAGE_ACTIVE.' AND is_deleted = '.Messages::MESSAGE_NOT_DELETED;
			$result = Yii::app()->db->createCommand($sql)->queryAll();

			if($result) {
				foreach ($result as $item) {
					$idUsers[] = $item['id_userFrom'];
				}
			}

			$sql = 'SELECT DISTINCT(id_userTo) FROM {{messages}}
				WHERE id_userFrom = '.(int) $idUserTo.' AND status = '.self::MESSAGE_ACTIVE.' AND is_deleted = '.Messages::MESSAGE_NOT_DELETED;
			$result = Yii::app()->db->createCommand($sql)->queryAll();

			if($result) {
				foreach ($result as $item) {
					$idUsers[] = $item['id_userTo'];
				}
			}

			if ($idUsers) {
				$idUsers = array_unique($idUsers);

				$criteria = new CDbCriteria();
				$criteria->compare('t.id', $idUsers);

				$allUsers = User::model()->with(array('messagesFrom' => array('order' => 'messagesFrom.date_send DESC', 'together' => true)))->findAll($criteria);

				$pages = new CPagination(count($allUsers));
				$pages->pageSize = 1;
				$pages->applyLimit($criteria);
			}
		}

		return array(
			'pages' => $pages,
			'allUsers' => $allUsers,
		);
	}

	public static function getAllMessagesUser($idFrom = '') {
		$allMessages = $pages = null;

		if ($idFrom) {
			$criteria = new CDbCriteria();

			$criteria->addCondition('(id_userFrom = :idFrom AND id_userTo = :idUserOwner) OR (id_userFrom = :idUserOwner AND id_userTo = :idFrom) AND status =:status AND is_deleted = :is_deleted');
			$criteria->addCondition('status = "'.self::MESSAGE_ACTIVE.'"');
			$criteria->params[':idFrom'] = $idFrom;
			$criteria->params[':idUserOwner'] = Yii::app()->user->id;
			$criteria->params[':status'] = self::MESSAGE_ACTIVE;
			$criteria->params[':is_deleted'] = self::MESSAGE_NOT_DELETED;

			$criteria->order = 'id DESC';

			$pages = new CPagination(count(Messages::model()->findAll($criteria)));
			$pages->pageSize = param('userPaginationPageSize', 20);
			$pages->applyLimit($criteria);

			$allMessages = Messages::model()->findAll($criteria);
		}

		return array(
			'pages' => $pages,
			'allMessages' => $allMessages,
		);
	}


	public static function getMesStatusesArray() {
		$status = array();

		$status[self::STATUS_UNREAD_USER] = tt('Unread user', 'messages');
		$status[self::STATUS_READ_USER] = tt('Read user', 'messages');

		return $status;
	}

	public static function getMessageStatus($data){
		if (isset($data->is_read)) {
			$status = self::getMesStatusesArray();

			if (array_key_exists($data->is_read, $status))
				return  $status[$data->is_read];
		}
		return false;
	}

	public static function getSenderName($data, $withLink = false, $backend = false) {
		$name = '-';

		if ($data->userInfoFrom->role == 'admin') {
			$name = tt('Administrator', 'messages');
		}
		else {
			if ($data->userInfoFrom) {
				$name = $data->userInfoFrom->username;
				if ($withLink) {
					if ($backend)
						$name = CHtml::link($name, Yii::app()->createUrl('/users/backend/main/view', array('id' => $data->userInfoFrom->id)));
					else
						$name = CHtml::link($name, Yii::app()->createUrl('/users/view/index', array('id' => $data->userInfoFrom->id)));
				}
				return $name;
			}
		}

		return $name;
	}

	public static function getRecipientName($data, $withLink = false, $backend = false) {
		$name = '';

		if ($data->userInfoTo) {
			$name = $data->userInfoTo->username;
			if ($withLink) {
				if ($backend)
					$name = CHtml::link($name, Yii::app()->createUrl('/users/backend/main/view', array('id' => $data->userInfoTo->id)));
				else
					$name = CHtml::link($name, Yii::app()->createUrl('/users/view/index', array('id' => $data->userInfoTo->id)));
			}
			return $name;
		}

		return $name;
	}

	public static function messageFormat($model = '') {
		$text = '';

		if ($model->allowHtml)
			$text = $model->message;
		else
			$text = CHtml::encode($model->message);

		return $text;
	}

	public static function getFiles($data) {
		$return = '';
		if ($data && isset($data->messagesFiles)) {
			if ($data->messagesFiles) {
				foreach ($data->messagesFiles as $file) {
					$return .= CHtml::link($file->orig_file_path, Yii::app()->createAbsoluteUrl('messages/main/downloadFile', array('fileId' => $file->file_id))).'<br />';
				}
			}
		}
		return $return;
	}

	public static function getCountUnread($userId = null) {
		if ($userId) {
			$sql = "SELECT COUNT(id) FROM {{messages}}
					WHERE is_read=".self::STATUS_UNREAD_USER."
					AND status = ".self::MESSAGE_ACTIVE."
					AND is_deleted = ".self::MESSAGE_NOT_DELETED."
					AND id_userTo = '".(int) $userId."'
					ORDER BY id";

			return (int) Yii::app()->db->createCommand($sql)->queryScalar();
		}
	}

	public static function getCountUnreadFromUser($userId = null) {
		if ($userId) {
			$sql = "SELECT COUNT(id) FROM {{messages}}
					WHERE is_read=".self::STATUS_UNREAD_USER."
					AND status = ".self::MESSAGE_ACTIVE."
					AND is_deleted = ".self::MESSAGE_NOT_DELETED."
					AND id_userFrom = '".(int) $userId."'
					AND id_userTo = '".(int) Yii::app()->user->id."'
					GROUP BY id_userFrom
					ORDER BY id";

			return (int) Yii::app()->db->createCommand($sql)->queryScalar();
		}

		return false;
	}
}