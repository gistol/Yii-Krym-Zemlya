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

class PhpAuthManager extends CPhpAuthManager{
	public function init(){
		// Иерархию ролей расположим в файле auth.php в директории config приложения
		if($this->authFile===null){
			$this->authFile=Yii::getPathOfAlias('application.modules.rbac.config.auth').'.php';
		}

		parent::init();

		// Для гостей у нас и так роль по умолчанию guest.
		if(!Yii::app()->user->isGuest && Yii::app()->user->role){
			// Связываем роль, заданную в БД с идентификатором пользователя,
			// возвращаемым UserIdentity.getId().
			if(!$this->isAssigned(Yii::app()->user->role, Yii::app()->user->id)) {
				if($this->assign(Yii::app()->user->role, Yii::app()->user->id)) {
					//Yii::app()->authManager->save();
				}
			}
			//$this->assign(Yii::app()->user->role, Yii::app()->user->id);
		}
	}
}