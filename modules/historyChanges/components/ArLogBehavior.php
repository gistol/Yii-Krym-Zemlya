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


class ArLogBehavior extends CActiveRecordBehavior {
    public $selectedAttributes = array();
    private $_oldAttributes = array();
	
	public $ignoreAttributes = array(
        'date_created',
        'date_updated',
        'date_manual_updated',
		'ip_long',
		//'sorter',
		'salt',
		'count_img',
    );

    public function afterSave($event) {
        if (!$this->Owner->isNewRecord) {
            $newAttributes = $this->Owner->getAttributes();
            $oldAttributes = $this->getOldAttributes();

            foreach ($newAttributes as $name => $value) {
                if($this->ignoreAttributes && in_array($name, $this->ignoreAttributes)){
                    continue;
                }

                if (!empty($oldAttributes))
                    $old = $oldAttributes[$name];
				else
                    $old = '';

				$isHtmlNewValue = isIssetHtml($value);
				$isHtmlOldValue = isIssetHtml($old);
				
				$addRecord = false;
				if ($isHtmlNewValue || $isHtmlOldValue) {
					$value = preg_replace('/\s+/', ' ', html_entity_decode(CHtml::decode($value), ENT_COMPAT, 'UTF-8'));
					$old = preg_replace('/\s+/', ' ', html_entity_decode(CHtml::decode($old), ENT_COMPAT, 'UTF-8'));

					if (utf8_strlen($value) != utf8_strlen($old)) {
						$addRecord = true;
					}
				}
				elseif ($value != $old)
					$addRecord = true;
				
                if ($addRecord) {
					$user = HUser::getModel();
					
                    $log = new HistoryChanges;
                    $description =  (isset($user) && $user) ? $user->getFullTitleOwnerForChangeOwner() : tt('System', 'historyChanges')
                        . tt('User modified', 'historyChanges').' ' . get_class($this->Owner)
                        . '[' . $this->Owner->getPrimaryKey() . '] - '
                        . $this->Owner->getAttributeLabel($name);
                    $log->action = 'update';
                    $log->model_name = get_class($this->Owner);
                    $log->model_id = $this->Owner->getPrimaryKey();
                    $log->field = $name;
                    $log->date_created = new CDbExpression('NOW()');
                    $log->user_id = Yii::app()->user->id;
                    $log->old_value = $this->getValueForAttributeForLog($name, $old);
                    $log->new_value = $this->getValueForAttributeForLog($name, $value);
                    $log->save();
                }
            }
        } 
		else {
			if (in_array(get_class($this->Owner), array('UserAds', 'Apartment')) && isset($this->Owner->active) && $this->Owner->active == Apartment::STATUS_DRAFT)
				return;
			
            $log = new HistoryChanges;
            $log->action = 'create';
            $log->model_name = get_class($this->Owner);
            $log->model_id = $this->Owner->getPrimaryKey();
            $log->field = '';
            $log->date_created = new CDbExpression('NOW()');
            $log->user_id = Yii::app()->user->id;
            $log->save(false);
        }
    }

    private function getValueForAttributeForLog($name, $key){
        if($this->selectedAttributes && in_array($name, array_keys($this->selectedAttributes))){
            return isset($this->selectedAttributes[$name][$key]) ? $key . ' => ' . $this->selectedAttributes[$name][$key] : $key;
        }
		elseif (is_object($key) && $key instanceof CDbExpression) {
			return isset($key->expression) ? $key->expression : '';
		}

        return $key;
    }

    public function afterDelete($event) {
        $log = new HistoryChanges;
        $log->action = 'delete';
        $log->model_name = get_class($this->Owner);
        $log->model_id = $this->Owner->getPrimaryKey();
        $log->field = '';
        $log->date_created = new CDbExpression('NOW()');
        $log->user_id = Yii::app()->user->id;
        $log->save(false);
    }

    public function afterFind($event) {
        $this->setOldAttributes($this->Owner->getAttributes());
    }

    public function getOldAttributes() {
        return $this->_oldAttributes;
    }

    public function setOldAttributes($value) {
        $this->_oldAttributes = $value;
    }

    public function getUrl() {
        return '';
    }
}