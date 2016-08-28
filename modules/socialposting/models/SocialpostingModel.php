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

class SocialpostingModel extends ParentModel {
	public static function model($className=__CLASS__) {
		return parent::model($className);
	}

	public function tableName() {
		return '{{socialposting}}';
	}

	public function behaviors(){
		return array(
			'AutoTimestampBehavior' => array(
				'class' => 'zii.behaviors.CTimestampBehavior',
				'createAttribute' => null,
				'updateAttribute' => 'date_updated',
			),
		);
	}

	public function rules() {
		return array(
			array('name, value', 'required'),
			array('name, value', 'length', 'max' => 255),
			array('name, value, section', 'safe', 'on' => 'search'),
		);
	}

	public function getTitle(){
		return tt($this->name);
	}

	public function attributeLabels() {
		return array(
			//'title_ru' => SocialpostingModule::t('Name'),
			'value' => SocialpostingModule::t('Value'),
			'section' => tt('Section'),
		);
	}

	public function search() {
		$criteria = new CDbCriteria;
		$criteria->compare('value', $this->value, true);
		$criteria->compare('section', $this->section);

		return new CActiveDataProvider($this, array(
			'criteria' => $criteria,
			'sort' => array(
				'defaultOrder' => 'section',
			),
			'pagination' => array(
				'pageSize' => param('adminPaginationPageSize', 20),
			),
		));
	}

	public static function getAdminValue($model){
		if($model->type == 'bool') {
			$url = Yii::app()->controller->createUrl("activate",
				array(
					'id' => $model->id,
					'action' => ($model->value == 1 ? 'deactivate' : 'activate'),
				));
			$img = CHtml::image(
				Yii::app()->theme->baseUrl.'/images/'.($model->value ? '' : 'in').'active.png',
				Yii::t('common', $model->value ? 'Inactive' : 'Active'),
				array('title' => Yii::t('common', $model->value ? 'Deactivate' : 'Activate'))
			);

			$options = array(
				'onclick' => 'ajaxSetStatus(this, "socialposting-table"); return false;',
			);

			return '<div align="left">'.CHtml::link($img, $url, $options).'</div>';
		} else {
			if(demo()){
				return tc('Hidden in demo mode');
			} else {
				return utf8_substr($model->value, 0, 55);
			}
		}
	}

	public static function getVisible($type){
		return $type == 'text';
	}

	public static function getSocialParamValue($param = '') {
		if ($param) {
			$value = Yii::app()->db->createCommand()
				->select('value')
				->from('{{socialposting}}')
				->where('name = "'.$param.'"')
				->queryScalar();

			return trim($value);
		}
	}

	public static function preparePosting($id = null) {
		$model = false;
		if ($id)
			$model = Apartment::model()->findByPk($id);

		if ($model) {
			$message = '';
			$defaultLang = Lang::getDefaultLang();

			$tmp = 'title_'.$defaultLang;
			if (isset($model->$tmp)) {
				$message .= $model->$tmp;
			}

			// vkontakte
			if (SocialpostingModel::getSocialParamValue('useVkontakte')) {
				$vkApId = SocialpostingModel::getSocialParamValue('vkontakteApplicationId');
				$vkToken = SocialpostingModel::getSocialParamValue('vkontakteToken');
				$vkUsId = SocialpostingModel::getSocialParamValue('vkontakteUserId');

				//if ($vkApId && $vkToken && !is_numeric($model->autoVKPostId)) {
				if ($vkApId && $vkToken && utf8_strlen($model->autoVKPostId) < 1) {
					if ($message) {
						$imageUrl = null;
						$res = Images::getMainThumb(300, 200, $model->images);
						if($res['link']) {
							$imageUrl = $res['link'];
						}

						$post = self::addPostToVK($message, $vkApId, $vkToken, $vkUsId, $model->getUrl(), $imageUrl);

						//logs(var_export($post, false));
						//logs(var_export($post, true));

						$postId = '';
						if ($post && isset($post->response))
							$postId = (isset($post->response->post_id) && $post->response->post_id) ? $post->response->post_id : '';

						$model->autoVKPostId = $postId;
						$model->update(array('autoVKPostId'));

						/*$sql = 'UPDATE {{apartment}} SET autoVKPostId = "'.strip_tags(addslashes($postId)).'" WHERE id = '.$model->id;
						Yii::app()->db->createCommand($sql)->execute();*/
					}
				}
			}

			// twitter
			if (SocialpostingModel::getSocialParamValue('useTwitter')) {
				$twApiKey = SocialpostingModel::getSocialParamValue('twitterApiKey');
				$twApiSecret = SocialpostingModel::getSocialParamValue('twitterApiSecret');
				$twTokenKey = SocialpostingModel::getSocialParamValue('twitterTokenKey');
				$twTokenSecret = SocialpostingModel::getSocialParamValue('twitterTokenSecret');

				//if ($twApiKey && $twApiSecret && $twTokenKey && $twTokenSecret && !is_numeric($model->autoTwitterPostId)) {
				if ($twApiKey && $twApiSecret && $twTokenKey && $twTokenSecret && utf8_strlen($model->autoTwitterPostId) < 1) {
					if ($message) {
						$post = self::addPostToTw($message, $twApiKey, $twApiSecret, $twTokenKey, $twTokenSecret, $model->getUrl());

						//logs(var_export($post, false));
						//logs(var_export($post, true));

						$postId = '';
						if ($post && isset($post->id_str))
							$postId = ($post->id_str) ? $post->id_str : '';

						$model->autoTwitterPostId = strip_tags(addslashes($postId));
						$model->update(array('autoTwitterPostId'));

						/*$sql = 'UPDATE {{apartment}} SET autoTwitterPostId = "'.strip_tags(addslashes($postId)).'" WHERE id = '.$model->id;
						Yii::app()->db->createCommand($sql)->execute();*/
					}
				}
			}
		}
		return true;
	}

	public static function addPostToVK($message = '', $vkApId, $vkToken, $vkUsId, $url = '', $imageUrl) {
		$vkPosting = new VKAutoPosting($vkToken, $vkUsId);

		$params = array();
		$params['friends_only'] = 0;
		$attachments = $url;
		$params['message'] = $message;

		if ($imageUrl) {
			$groupId = trim($vkUsId, '-');

			$response = $vkPosting->method("photos.getWallUploadServer", array('group_id' => $groupId));

			$uploadUrl = $albumId = $userId = null;
			if ($response) {
				if (isset($response->response) && isset($response->response->upload_url)) {
					$uploadUrl = $response->response->upload_url;
				}
				if (isset($response->response) && isset($response->response->aid)) {
					$albumId = $response->response->aid;
				}
				if (isset($response->response) && isset($response->response->mid)) {
					$userId = $response->response->mid;
				}
			}

			if ($uploadUrl) {
				$imageUrl = Yii::getPathOfAlias('webroot').DIRECTORY_SEPARATOR.substr(strstr($imageUrl, '/uploads/'), 1, strlen($imageUrl));
				//@exec("curl -X POST -F 'photo=@$imageUrl' '$uploadUrl'", $output);

				$ch = curl_init();
				curl_setopt($ch, CURLOPT_URL, $uploadUrl);
				curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
				curl_setopt($ch, CURLOPT_POST, true);
				curl_setopt($ch, CURLOPT_POSTFIELDS, array("file1" => "@".$imageUrl));
				$output = curl_exec($ch);

				if (isset($output) && $output) {
					$response = json_decode($output);

					$response = $vkPosting->method('photos.saveWallPhoto', array(
						'group_id' => $groupId,
						'photo' => $response->photo,
						'server' => $response->server,
						'hash' => $response->hash,
					));

					if ($response && isset($response->response)) {
						$attachments .= ','.$response->response[0]->id;
					}
				}
			}
		}

		$fromGroup = (int) self::getSocialParamValue('vkontakteFromGroup');
		if($fromGroup == 1){
			$params['from_group'] = 1;
		}

		if ($vkUsId)
			$params['owner_id'] = $vkUsId;

		$params['attachments'] = $attachments;

		$post = $vkPosting->method("wall.post", $params);

		return $post;
	}

	public static function addPostToTw($message = '', $twApiKey, $twApiSecret, $twTokenKey, $twTokenSecret, $url = '') {
		Yii::import('application.modules.socialposting.components.TwitterOAuth');

		$connection = new TwitterOAuth($twApiKey, $twApiSecret, $twTokenKey, $twTokenSecret);
		$content = $connection->get('account/verify_credentials');

		if ($url)
			$message .= '. '.$url;

		$post = $connection->post('statuses/update', array('status' => $message));

		return $post;
	}
}