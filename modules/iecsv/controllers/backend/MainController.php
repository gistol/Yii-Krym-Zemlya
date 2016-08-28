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

class MainController extends ModuleAdminController {
	public $modelName = 'Iecsv';
	public $separator = ';';
	public $separatorElem = '|';
	public $mask = '';
	public $defaultAction = 'admin';
	public $allLangs = '';
	public $defLang = '';
	public $currLang = '';

	public $i18nMaskFields = array(
		'title',
		'description',
		'near',
		'location',
		'exchange'
	);

	public function accessRules(){
		return array(
			array('allow',
				'expression'=> "Yii::app()->user->checkAccess('all_modules_admin')",
			),
			array('deny',
				'users'=>array('*'),
			),
		);
	}

	public function init() {
		parent::init();

		if (isFree()) {
			$this->defLang = Yii::app()->language;
			$this->currLang = Yii::app()->language;
			$this->mask = 'type;priceType;objType;countryName;regionName;cityName;isPricePoa;price;priceTo;numberRooms;floor;floorTotal;square;landSquare;sleeps;title;description;near;location;exchange;bathroom;safety;comfort;kitchen;employment;entertainment;services;terms;photos;lat;lng';
		}
		else {
			$this->allLangs = Lang::model()->findAll(array('condition' => 'active = 1'));
			$this->mask = 'type;priceType;objType;countryName;regionName;cityName;isPricePoa;price;priceTo;numberRooms;floor;floorTotal;square;landSquare;sleeps';

			$this->defLang = Lang::getDefaultLang();
			$this->currLang = Yii::app()->language;

			if ($this->allLangs) {
				foreach ($this->i18nMaskFields as $i18nMaskField) {
					foreach($this->allLangs as $lang) {
						$this->mask .= ";{$i18nMaskField}_{$lang->name_iso}";
					}
				}
			}
			$this->mask .= ';bathroom;safety;comfort;kitchen;employment;entertainment;services;terms;photos;lat;lng';
		}
	}

	public function actionViewImport() {
		$model = $this->loadModel();
		$this->render('view_import', array('model' => $model));
	}

	public function actionImportUpload() {
		$model = new $this->modelName;
		$session = Yii::app()->session;

		$modelUsers = new User('search');
		$modelUsers->resetScope();
		$modelUsers->active();
		$modelUsers->unsetAttributes();  // clear any default values
		if(isset($_GET['User'])){
			$modelUsers->attributes = $_GET['User'];
		}

		if (!isset($_FILES[$this->modelName]) && isset($session['importAds']) && $session['importAds']) {
			$itemsProvider = new CArrayDataProvider($session['importAds'], array(
				'sort' => array(
					'attributes' => array(
						'title',
					),
				),
				'pagination' => array(
					'pageSize' => count($session['importAds']),
				)
			));
			$this->render('view_import_grid', compact('itemsProvider', 'model', 'modelUsers'));
		}
		else {
			if (isset($_FILES[$this->modelName]) && isset($_FILES[$this->modelName]['name'])) {
				$model->import = CUploadedFile::getInstance($model, 'import');

				if ($model->validate()) {
					$fileExt = $model->import->extensionName;

					$fileName = date('Y_m_d_s') . rand(0, 9) . '_import.' . $fileExt;
					$filePath = $model->csvPath.DIRECTORY_SEPARATOR.$fileName;

					$model->import->saveAs($filePath);

					$isZip = false;
					// if zip arhive
					if ($fileExt == 'zip') {
						$isZip = true;

						include_once $model->libraryPath.DIRECTORY_SEPARATOR.'pclzip.lib.php';
						$archive = new PclZip($filePath);
						$list = $archive->extract(PCLZIP_OPT_PATH, $model->csvPath, PCLZIP_OPT_SET_CHMOD, 0777, PCLZIP_OPT_REMOVE_ALL_PATH);
						$v_list = $archive->delete();

						/* if ($v_list == 0) {
						  die("Error : ".$archive->errorInfo(true));
						  } */

						foreach ($list as $item) {
							if (strpos($item["stored_filename"], ".csv")) {
								if ($item["stored_filename"] != $fileName) {
									$fileNameCSV = str_replace(".zip", ".csv", $fileName);
									if (copy($model->csvPath.DIRECTORY_SEPARATOR.$item["stored_filename"], $model->csvPath.DIRECTORY_SEPARATOR.$fileNameCSV)) {
										@unlink($model->csvPath .DIRECTORY_SEPARATOR.$item["stored_filename"]);
									} else {
										Yii::app()->user->setFlash(
											'error', tt('Error copying file. Please try again later and set 0777 for ' . $model->csvPath)
										);
										$this->redirect('viewImport');
									}
									@unlink($model->csvPath.DIRECTORY_SEPARATOR.$item["stored_filename"]);
									@unlink($model->csvPath.DIRECTORY_SEPARATOR.$fileName);

									$fileName = $fileNameCSV;
									$filePath = $model->csvPath.DIRECTORY_SEPARATOR.$fileName;
								}
								break;
							}
						}
					}

					$import = $rowData = array();
					
					// prepare
					$fileContents = file_get_contents($filePath);
					iconv(mb_detect_encoding($fileContents, mb_detect_order(), true), "UTF-8", $fileContents);
					$fileContents = str_replace('""', '', $fileContents);
					#$fileContents = strip_tags($fileContents);
					file_put_contents($filePath, $fileContents);

					//parse csv file
					@setlocale(LC_ALL, 'en_US.utf8');
					if (($handle = fopen($filePath, "r")) !== FALSE) {
						while (($data = fgetcsv($handle, 0, "{$this->separator}")) !== FALSE) {
							$import[] = $data;
						}
						fclose($handle);

						$keys = $import[0];

						// remove BOM from file
						$bom = pack('H*','EFBBBF');
						$keys[0] = preg_replace("/^$bom/", '', $keys[0]);

						unset($import[0]);

						$j = 1;
						foreach ($import as $key => $items) {
							if (count($keys) == count($items)) {
								$import[$key] = array_combine($keys, $items);
								$import[$key]["id"] = $j;
								$j++;
							}
							else {
								continue;
							}
						}

						// insert result into session
						$session['importAds'] = $import;
						$session['isZip'] = $isZip;
					}

					$itemsProvider = new CArrayDataProvider($import, array(
								'sort' => array(
									'attributes' => array(
										'title',
									),
								),
								'pagination' => array(
									'pageSize' => count($import),
								)
							));
					$this->render('view_import_grid', compact('itemsProvider', 'model', 'modelUsers'));
				}
				else {
					Yii::app()->user->setFlash(
						'error', Yii::t('module_iecsv', 'Please select a *.csv or *.zip file for import. Max size of file is {size}.', array('{size}' => $model->fileMaxSizeMessage))
					);
					$this->redirect('viewImport');
				}
			}
			else {
				$this->redirect('viewImport');
			}
		}
	}

	public function actionImportProcess() {
		$model = new $this->modelName;
		$session = Yii::app()->session;

		if (!isset($_POST[$this->modelName]['itemsSelectedImport']) ||
				empty($_POST[$this->modelName]['itemsSelectedImport']) ||
				!isset($session['importAds'])) {
			Yii::app()->user->setFlash(
				'error', tt('Please select ads for import.')
			);
			$this->redirect('importUpload');
		}

		if (!isset($_POST[$this->modelName]['selectedImportUser']) ||
			empty($_POST[$this->modelName]['selectedImportUser'])){
			Yii::app()->user->setFlash(
				'error', tt('Please select user (owner listings).')
			);
			$this->redirect('importUpload');
		}

		$arrSel = explode(',', $_POST[$this->modelName]['itemsSelectedImport']);
		$arrSel = array_map("intval", $arrSel);
		$selectedImportUser = (int) $_POST[$this->modelName]['selectedImportUser'];

		if (!$selectedImportUser) {
			Yii::app()->user->setFlash(
				'error', tt('Please select user (owner listings).')
			);
			$this->redirect('importUpload');
		}

		$importAds = $session['importAds'];

		// get current max sorter
		$sql = 'SELECT MAX(sorter) as maximumSorter FROM {{apartment}}';
		$maxSorter = Yii::app()->db->createCommand($sql)->queryScalar();

		// only selected items
		foreach ($importAds as $key => $values) {
			if (in_array($key, $arrSel)) {
				$maxSorter++;
				$this->addListingFromCSV($importAds[$key], $session['isZip'], $maxSorter, $selectedImportUser);
			}
		}

		// delete all images
		foreach ($importAds as $key => $values) {
			$photos = (!empty($values['photos'])) ? explode($this->separatorElem, $values['photos']) : null;
			if (is_array($photos)) {
				foreach ($photos as $item) {
					if (!$session['isZip']) {
						if (stristr($item, "http")) {
							$pathParts = pathinfo($item);
							$item = $pathParts['basename'];
						}
					}
					if (file_exists($model->csvPath.DIRECTORY_SEPARATOR.$item)) {
						@unlink($model->csvPath.DIRECTORY_SEPARATOR.$item);
					}
				}
			}
		}

		if (isset(Yii::app()->session['importAds']))
			unset(Yii::app()->session['importAds']);

		Yii::app()->user->setFlash(
			'success', tt('Listings are imported. You can edit and activate.')
		);
		$this->redirect(array('/apartments/backend/main/admin'));
		Yii::app()->end();
	}

	private function addListingFromCSV($value, $isZip, $maxSorter, $selectedImportUser) {
		if (is_array($value)) {
			$model = new $this->modelName;

			$type = (!empty($value['type'])) ? $value['type'] : Apartment::TYPE_DEFAULT;
			$priceType = (!empty($value['priceType'])) ? $value['priceType'] : '';
			$objType = (!empty($value['objType'])) ? $value['objType'] : min(Apartment::getObjTypesArray());

			$countryName = (!empty($value['countryName'])) ? trim($value['countryName']) : null;
			$regionName = (!empty($value['regionName'])) ? trim($value['regionName']) : null;
			$cityName = (!empty($value['cityName'])) ? trim($value['cityName']) : null;

			$countryId = $countryInfo = $regionId = $regionInfo = $cityId = $cityInfo = 0;
			if (issetModule('location')) {
				if ($countryName) {
					if (isFree()) {
						$countryInfo = Country::model()->findByAttributes(array('name_'.Yii::app()->language => $countryName));
					}
					else {
						Yii::app()->setLanguage($this->defLang);
						$countryInfo = Country::model()->findByAttributes(array('name_'.Yii::app()->language => $countryName));
						Yii::app()->setLanguage($this->currLang);
					}

					if ($countryInfo && isset($countryInfo->id)) {
						$countryId = $countryInfo->id;
					}
				}
				if ($regionName) {
					if (isFree()) {
						$regionInfo = Region::model()->findByAttributes(array('name_'.Yii::app()->language => $regionName));
					}
					else {
						Yii::app()->setLanguage($this->defLang);
						$regionInfo = Region::model()->findByAttributes(array('name_'.Yii::app()->language => $regionName));
						Yii::app()->setLanguage($this->currLang);
					}

					if ($regionInfo && isset($regionInfo->id)) {
						$regionId = $regionInfo->id;
					}
				}
				if ($cityName) {
					if (isFree()) {
						$cityInfo = City::model()->findByAttributes(array('name_'.Yii::app()->language => $cityName));
					}
					else {
						Yii::app()->setLanguage($this->defLang);
						$cityInfo = City::model()->findByAttributes(array('name_'.Yii::app()->language => $cityName));
						Yii::app()->setLanguage($this->currLang);
					}

					if ($cityInfo && isset($cityInfo->id)) {
						$cityId = $cityInfo->id;
					}
				}
			}
			else {
				if ($cityName) {
					Yii::import('application.modules.apartmentCity.models.ApartmentCity');
					if (isFree()) {
						$cityInfo = ApartmentCity::model()->findByAttributes(array('name_'.Yii::app()->language => $cityName));
					}
					else {
						Yii::app()->setLanguage($this->defLang);
						$cityInfo = ApartmentCity::model()->findByAttributes(array('name_'.Yii::app()->language => $cityName));
						Yii::app()->setLanguage($this->currLang);
					}

					if ($cityInfo && isset($cityInfo->id)) {
						$cityId = $cityInfo->id;
					}
				}
			}

			// if type for sale - set price type only for sale
			if ($type == Apartment::TYPE_SALE) {
				$priceType = Apartment::PRICE_SALE;
			}

			$isPricePoa = (isset($value['isPricePoa'])) ? $value['isPricePoa'] : 0;
			$price = (!empty($value['price'])) ? $value['price'] : '';
			$priceTo = (!empty($value['priceTo'])) ? $value['priceTo'] : '';

			$numberRooms = (!empty($value['numberRooms'])) ? $value['numberRooms'] : '';
			$floor = (!empty($value['floor'])) ? $value['floor'] : '';
			$floor_total = (!empty($value['floorTotal'])) ? $value['floorTotal'] : '';
			$square = (!empty($value['square'])) ? $value['square'] : '';
			$landSquare = (!empty($value['landSquare'])) ? $value['landSquare'] : '';
			$sleeps = (!empty($value['sleeps'])) ? $this->deleteChars($value['sleeps']) : '';

			if (isFree()) {
				$title = (!empty($value['title'])) ? $this->deleteChars($value['title']) : '';
				$description = (!empty($value['description'])) ? $this->deleteChars($value['description']) : '';
				$near = (!empty($value['near'])) ? $this->deleteChars($value['near']) : '';
				$address = (!empty($value['location'])) ? $this->deleteChars($value['location']) : '';
				$exchange = (!empty($value['exchange'])) ? $this->deleteChars($value['exchange']) : '';
			}
			else {
				if ($this->allLangs) {
					foreach ($this->i18nMaskFields as $i18nMaskField) {
						foreach($this->allLangs as $lang) {
							$title[$lang->name_iso] = $this->deleteChars($value['title_'.$lang->name_iso]);
							$description[$lang->name_iso] = $this->deleteChars($value['description_'.$lang->name_iso]);
							$near[$lang->name_iso] = $this->deleteChars($value['near_'.$lang->name_iso]);
							$address[$lang->name_iso] = $this->deleteChars($value['location_'.$lang->name_iso]);
							$exchange[$lang->name_iso] = $this->deleteChars($value['exchange_'.$lang->name_iso]);
						}
					}
				}
			}

			$lat = (!empty($value['lat'])) ? $value['lat'] : '';
			$lng = (!empty($value['lng'])) ? $value['lng'] : '';

			// references
			$adRef = array();
			$adRef['bathroom'] = (!empty($value['bathroom'])) ? explode($this->separatorElem, $value['bathroom']) : null;
			$adRef['safety'] = (!empty($value['safety'])) ? explode($this->separatorElem, $value['safety']) : null;
			$adRef['comfort'] = (!empty($value['comfort'])) ? explode($this->separatorElem, $value['comfort']) : null;
			$adRef['kitchen'] = (!empty($value['kitchen'])) ? explode($this->separatorElem, $value['kitchen']) : null;
			$adRef['employment'] = (!empty($value['employment'])) ? explode($this->separatorElem, $value['employment']) : null;
			$adRef['entertainment'] = (!empty($value['entertainment'])) ? explode($this->separatorElem, $value['entertainment']) : null;
			$adRef['services'] = (!empty($value['services'])) ? explode($this->separatorElem, $value['services']) : null;
			$adRef['terms'] = (!empty($value['terms'])) ? explode($this->separatorElem, $value['terms']) : null;

			$photos = (!empty($value['photos'])) ? explode($this->separatorElem, $value['photos']) : null;
			$countImg = (is_array($photos) && count($photos) > 0) ? count($photos) : 0;

			// insert into apartments table
			if (isFree()) {
				$sql = 'INSERT INTO {{apartment}} (type, obj_type_id, loc_country, loc_region, loc_city, city_id, date_updated, date_created, activity_always, is_price_poa, price, price_to, num_of_rooms, floor,
									floor_total, square, land_square, window_to, title_'.Yii::app()->language.', description_'.Yii::app()->language.',
									description_near_'.Yii::app()->language.', exchange_to_'.Yii::app()->language.',
									living_conditions, services, address_'.Yii::app()->language.', berths, active, lat, lng,
									rating, is_special_offer, is_free_to, price_type, sorter, owner_active, owner_id, count_img)

								VALUES (:type, :objType, :locCountryId, :locRegionId, :locCityId, :cityId, NOW(), NOW(), :activityAlways, :isPricePoa, :price, :priceTo,:numberRooms, :floor,
									:floorTotal, :square, :landSquare, :windowTo, :title, :description,
									:descriptionNear, :exchangeTo,
									:livingConditions, :services, :address, :berths, :active, :lat, :lng,
									:rating, "", "", :priceType, :maxSorter, :ownerActive, :ownerId, :countImg) ';
				$command = Yii::app()->db->createCommand($sql);

				$command->bindValue(":type", $type, PDO::PARAM_INT);
				$command->bindValue(":objType", $objType, PDO::PARAM_INT);
				$command->bindValue(":locCountryId", $countryId, PDO::PARAM_INT);
				$command->bindValue(":locRegionId", $regionId, PDO::PARAM_INT);
				$command->bindValue(":locCityId", $cityId, PDO::PARAM_INT);
				$command->bindValue(":cityId", $cityId, PDO::PARAM_INT);
				$command->bindValue(":activityAlways", 1, PDO::PARAM_INT);
				$command->bindValue(":isPricePoa", $isPricePoa, PDO::PARAM_INT);
				$command->bindValue(":price", $price, PDO::PARAM_STR);
				$command->bindValue(":priceTo", $priceTo, PDO::PARAM_STR);
				$command->bindValue(":numberRooms", $numberRooms, PDO::PARAM_INT);
				$command->bindValue(":floor", $floor, PDO::PARAM_INT);
				$command->bindValue(":floorTotal", $floor_total, PDO::PARAM_INT);
				$command->bindValue(":square", $square, PDO::PARAM_INT);
				$command->bindValue(":landSquare", $landSquare, PDO::PARAM_INT);
				$command->bindValue(":windowTo", 0, PDO::PARAM_INT);
				$command->bindValue(":title", $title, PDO::PARAM_STR);
				$command->bindValue(":description", $description, PDO::PARAM_STR);
				$command->bindValue(":descriptionNear", $near, PDO::PARAM_STR);
				$command->bindValue(":exchangeTo", $exchange, PDO::PARAM_STR);
				$command->bindValue(":livingConditions", 0, PDO::PARAM_INT);
				$command->bindValue(":services", 0, PDO::PARAM_INT);
				$command->bindValue(":address", $address, PDO::PARAM_STR);
				$command->bindValue(":berths", $sleeps, PDO::PARAM_STR);
				$command->bindValue(":active", 0, PDO::PARAM_INT);
				$command->bindValue(":lat", $lat, PDO::PARAM_STR);
				$command->bindValue(":lng", $lng, PDO::PARAM_STR);
				$command->bindValue(":rating", 0, PDO::PARAM_INT);
				$command->bindValue(":priceType", $priceType, PDO::PARAM_INT);
				$command->bindValue(":maxSorter", $maxSorter, PDO::PARAM_INT);
				$command->bindValue(":ownerActive", 1, PDO::PARAM_INT);
				$command->bindValue(":ownerId", $selectedImportUser, PDO::PARAM_INT);
				$command->bindValue(":countImg", $countImg, PDO::PARAM_INT);

				$command->execute();
				$lastId = Yii::app()->db->getLastInsertID();
			}
			else {
				$fieldsSQL = $placeholdersSQL = $valuesSQL = array();

				if ($this->allLangs) {
					foreach($this->allLangs as $lang) {
						$fieldsSQL[] = 'title_'.$lang->name_iso;
						$fieldsSQL[] = 'description_'.$lang->name_iso;
						$fieldsSQL[] = 'description_near_'.$lang->name_iso;
						$fieldsSQL[] = 'address_'.$lang->name_iso;
						$fieldsSQL[] = 'exchange_to_'.$lang->name_iso;

						$placeholdersSQL[] = ':title_'.$lang->name_iso;
						$placeholdersSQL[] = ':description_'.$lang->name_iso;
						$placeholdersSQL[] = ':description_near_'.$lang->name_iso;
						$placeholdersSQL[] = ':address_'.$lang->name_iso;
						$placeholdersSQL[] = ':exchange_to_'.$lang->name_iso;

						$valuesSQL[':title_'.$lang->name_iso] = $this->deleteChars($title[$lang->name_iso]);
						$valuesSQL[':description_'.$lang->name_iso] = $this->deleteChars($description[$lang->name_iso]);
						$valuesSQL[':description_near_'.$lang->name_iso] = $this->deleteChars($near[$lang->name_iso]);
						$valuesSQL[':address_'.$lang->name_iso] = $this->deleteChars($address[$lang->name_iso]);
						$valuesSQL[':exchange_to_'.$lang->name_iso] = $this->deleteChars($exchange[$lang->name_iso]);
					}
				}

				$sql = 'INSERT INTO {{apartment}} (
									type, obj_type_id, loc_country, loc_region, loc_city, city_id, date_updated, date_created, activity_always, is_price_poa, price, price_to, num_of_rooms, floor,
									floor_total, square, land_square, window_to,
									living_conditions, services, berths, active, lat, lng,
									rating, is_special_offer, is_free_to, price_type, sorter, owner_active, owner_id, count_img,
									'.implode(", ",$fieldsSQL).'
									)
								VALUES (
									:type, :objType, :locCountryId, :locRegionId, :locCityId, :cityId, NOW(), NOW(), :activityAlways, :isPricePoa, :price, :priceTo,:numberRooms, :floor,
									:floorTotal, :square, :landSquare, :windowTo,
									:livingConditions, :services, :berths, :active, :lat, :lng,
									:rating, "", "", :priceType, :maxSorter, :ownerActive, :ownerId, :countImg,
									'.implode(", ",$placeholdersSQL).'
									) ';
				$command = Yii::app()->db->createCommand($sql);

				$command->bindValue(":type", $type, PDO::PARAM_INT);
				$command->bindValue(":objType", $objType, PDO::PARAM_INT);
				$command->bindValue(":locCountryId", $countryId, PDO::PARAM_INT);
				$command->bindValue(":locRegionId", $regionId, PDO::PARAM_INT);
				$command->bindValue(":locCityId", $cityId, PDO::PARAM_INT);
				$command->bindValue(":cityId", $cityId, PDO::PARAM_INT);
				$command->bindValue(":activityAlways", 1, PDO::PARAM_INT);
				$command->bindValue(":isPricePoa", $isPricePoa, PDO::PARAM_INT);
				$command->bindValue(":price", $price, PDO::PARAM_STR);
				$command->bindValue(":priceTo", $priceTo, PDO::PARAM_STR);
				$command->bindValue(":numberRooms", $numberRooms, PDO::PARAM_INT);
				$command->bindValue(":floor", $floor, PDO::PARAM_INT);
				$command->bindValue(":floorTotal", $floor_total, PDO::PARAM_INT);
				$command->bindValue(":square", $square, PDO::PARAM_INT);
				$command->bindValue(":landSquare", $landSquare, PDO::PARAM_INT);
				$command->bindValue(":windowTo", 0, PDO::PARAM_INT);
				$command->bindValue(":livingConditions", 0, PDO::PARAM_INT);
				$command->bindValue(":services", 0, PDO::PARAM_INT);
				$command->bindValue(":berths", $sleeps, PDO::PARAM_STR);
				$command->bindValue(":active", 0, PDO::PARAM_INT);
				$command->bindValue(":lat", $lat, PDO::PARAM_STR);
				$command->bindValue(":lng", $lng, PDO::PARAM_STR);
				$command->bindValue(":rating", 0, PDO::PARAM_INT);
				$command->bindValue(":priceType", $priceType, PDO::PARAM_INT);
				$command->bindValue(":maxSorter", $maxSorter, PDO::PARAM_INT);
				$command->bindValue(":ownerActive", 1, PDO::PARAM_INT);
				$command->bindValue(":ownerId", $selectedImportUser, PDO::PARAM_INT);
				$command->bindValue(":countImg", $countImg, PDO::PARAM_INT);

				foreach($valuesSQL as $name => $value){
					$command->bindValue($name, $value, PDO::PARAM_STR);
				}

				$command->execute();
				$lastId = Yii::app()->db->getLastInsertID();
			}

			// insert references
			foreach ($adRef as $key => $value) {
				switch ($key) {
					case 'comfort':
						$refId = 1;
						break;
					case 'bathroom':
						$refId = 2;
						break;
					case 'kitchen':
						$refId = 3;
						break;
					case 'employment':
						$refId = 4;
						break;
					case 'safety':
						$refId = 5;
						break;
					case 'entertainment':
						$refId = 7;
						break;
					case 'terms':
						$refId = 9;
						break;
					case 'services':
						$refId = 10;
						break;
				}

				if (is_array($value) && count($value) > 0) {
					foreach ($value as $item) {
						// get reference id by name
						if (isFree()) {
							//$sql = "SELECT id FROM {{apartment_reference_values}} WHERE title_" . Yii::app()->language . " = '" . $item . "' AND reference_category_id = '" . $refId . "'";
							//$valId = Yii::app()->db->createCommand($sql)->queryRow();
							$valId = Yii::app()->db->createCommand()
								->select('id')
								->from('{{apartment_reference_values}}')
								->where('title_' . Yii::app()->language . ' = :title AND reference_category_id = :catId', array(':title' => $item, ':catId' => $refId))
								->queryRow();
						}
						else {
							Yii::app()->setLanguage($this->defLang);

							//$sql = "SELECT id FROM {{apartment_reference_values}} WHERE title_" . Yii::app()->language . " = '" . $item . "' AND reference_category_id = '" . $refId . "'";
							//$valId = Yii::app()->db->createCommand($sql)->queryRow();

							$valId = Yii::app()->db->createCommand()
								->select('id')
								->from('{{apartment_reference_values}}')
								->where('title_' . Yii::app()->language . ' = :title AND reference_category_id = :catId', array(':title' => $item, ':catId' => $refId))
								->queryRow();

							Yii::app()->setLanguage($this->currLang);
						}

						if (isset($valId['id']) && !empty($valId['id'])) {
							$sql = 'INSERT INTO {{apartment_reference}} (reference_id, reference_value_id, apartment_id)
								VALUES (:refId, :refValId, :apId) ';
							$command = Yii::app()->db->createCommand($sql);
							$command->bindValue(":refId", $refId, PDO::PARAM_INT);
							$command->bindValue(":refValId", $valId['id'], PDO::PARAM_INT);
							$command->bindValue(":apId", $lastId, PDO::PARAM_INT);
							$command->execute();
						}
					}
				}
			}

			// get and upload photos
			if (is_array($photos) && count($photos) > 0) {
				$arrFiles = $arrImgs = array();
				$IecsvFiles = array();

				foreach ($photos as $key => $item) {
					$item = trim($item);
					
					/*preg_match("/([^\"']?.*(png|jpg|gif|jpeg|bmp|tiff|tif))/", $item, $output_array);
					
					if (is_array($output_array) && count($output_array))
						$item = $output_array[0];*/
					
					if (!$isZip) {
						if (stristr($item, "http")) {
							$pathParts = pathinfo($item);

							$file = $pathParts['basename'];
							$fileExt = $pathParts['extension'];

							$photoPath = $model->csvPath.DIRECTORY_SEPARATOR.$file;

							// get file by cUrl
							if (function_exists('curl_version')) {
								$ch = curl_init();

								$ch = curl_init();
								curl_setopt($ch, CURLOPT_URL, $item);
								$fp = fopen($photoPath, 'wb');
								curl_setopt($ch, CURLOPT_FILE, $fp);
								curl_setopt($ch, CURLOPT_HEADER, 0);
								curl_exec($ch);
								curl_close($ch);
								fclose($fp);
							} else { // no CUrl, try differently
								file_put_contents($photoPath, file_get_contents($item));
							}

							// reset file name - remove host. only filename and extension.
							$item = $file;
						}
					} else { // image from zip arhive
						$photoPath = $model->csvPath.DIRECTORY_SEPARATOR.$item;
					}

					if (file_exists($photoPath)) {
						$IecsvFiles[$key]['name'] = $item;
						$IecsvFiles[$key]['tmp_name'] = $photoPath;
					}
				}

				if (count($IecsvFiles) > 0) {
					$apartment = Apartment::model()->findByPk($lastId);

					$path = Yii::getPathOfAlias('webroot.uploads.objects.'.$apartment->id.'.'.Images::ORIGINAL_IMG_DIR);
					$pathMod = Yii::getPathOfAlias('webroot.uploads.objects.'.$apartment->id.'.'.Images::MODIFIED_IMG_DIR);

					$oldUMask = umask(0);
					if(!is_dir($path)){
						@mkdir($path, 0777, true);
					}
					if(!is_dir($pathMod)){
						@mkdir($pathMod, 0777, true);
					}
					umask($oldUMask);

					$result['error'] = '';

					if(is_writable($path) && is_writable($pathMod)){
						touch($path.DIRECTORY_SEPARATOR.'index.htm');
						touch($pathMod.DIRECTORY_SEPARATOR.'index.htm');

						foreach($IecsvFiles as $IecsvFile) {
							if (isset($IecsvFile['name']) && $IecsvFile['name']) {
								if (copy($model->csvPath.DIRECTORY_SEPARATOR.$IecsvFile['name'], $path.DIRECTORY_SEPARATOR.$IecsvFile['name'])) {

									$resize = new CImageHandler();
									if($resize->load($path.DIRECTORY_SEPARATOR.$IecsvFile['name'])){
										$resize->thumb(param('maxImageWidth', 1024), param('maxImageHeight', 768), Images::KEEP_PHOTO_PROPORTIONAL)
											->save();

										$image = new Images();
										$image->id_object = $apartment->id;
										$image->id_owner = $apartment->owner_id;
										$image->file_name = $IecsvFile['name'];

										$image->save();
									}
								}
								else {
									$result['error'] = 'No copy';
								}
							}
						}
					}
					else {
						$result['error'] = 'Access denied.';
					}
				}
			}
		}
	}

	public function actionViewExport() {
		$model = new $this->modelName('searchExport');
		$model->resetScope();
		$model->scenario = 'search';

		$model->unsetAttributes();  // clear any default values
		if(isset($_GET[$this->modelName])){
			$model->attributes=$_GET[$this->modelName];
		}

		$this->render('view_export', array(
			'model' => $model,
		));
	}

	public function actionExport() {
		$model = new $this->modelName;
		$photosExport = array();

		if (isset($_POST['ajax']) && $_POST['ajax'] === 'export-form') {
			echo CActiveForm::validate($model);
			Yii::app()->end();
		}

		if (isset($_POST[$this->modelName])) {
			$model->attributes = $_POST[$this->modelName];
			$arrSel = '';

			if (isset($_POST[$this->modelName]['itemsSelectedExport']) && $_POST[$this->modelName]['itemsSelectedExport']) {
				$arrSel = explode(',', $_POST[$this->modelName]['itemsSelectedExport']);
				$arrSel = array_map("intval", $arrSel);
			}

			if (!$arrSel || !count($arrSel)) {
				Yii::app()->user->setFlash(
					'error', tt('Please select ads for export.')
				);

				$this->redirect('viewExport');
			} else {
				$backUrl = $this->createUrl('viewExport', array($this->modelName.'_sel' => $arrSel));

				$isZip = isset($_POST[$this->modelName]['isZip']) ? $_POST[$this->modelName]['isZip'] : 0;

				$fileName = date('Y_m_d_s') . rand(0, 9) . '_export.csv';
				$filePath = $model->csvPath.DIRECTORY_SEPARATOR.$fileName;

				$fileLine = array();
				$fileLine[0] = $this->mask;

				$i = 1;
				foreach ($arrSel as $v) {
					// get needed info
					$apartment = Apartment::model()
							->cache(param('cachingTime', 1209600), HApartment::getFullDependency($v))
							->with('windowTo', 'images')
							->findByPk($v);

					// set values
					$type = $apartment->type;
					$priceType = $apartment->price_type;
					$objType = $apartment->obj_type_id;

					$countryName = $regionName = $cityName = '';
					if (issetModule('location')) {
						// country
						if (isFree()) {
							if ($apartment->loc_country) {
								$countryInfo = Country::model()->findByPk($apartment->loc_country);
								if ($countryInfo && isset($countryInfo->name))
									$countryName = $countryInfo->name;
							}
						}
						else {
							if ($apartment->loc_country) {
								Yii::app()->setLanguage($this->defLang);
								$countryInfo = Country::model()->findByPk($apartment->loc_country);

								if ($countryInfo && isset($countryInfo->name)) {
									$countryName = $countryInfo->name;
								}

								Yii::app()->setLanguage($this->currLang);
							}
						}
						// region
						if (isFree()) {
							if ($apartment->loc_region) {
								$regionInfo = Region::model()->findByPk($apartment->loc_region);
								if ($regionInfo && isset($regionInfo->name))
									$regionName = $regionInfo->name;
							}
						}
						else {
							if ($apartment->loc_region) {
								Yii::app()->setLanguage($this->defLang);
								$regionInfo = Region::model()->findByPk($apartment->loc_region);

								if ($regionInfo && isset($regionInfo->name)) {
									$regionName = $regionInfo->name;
								}

								Yii::app()->setLanguage($this->currLang);
							}
						}
						// city
						if (isFree()) {
							if ($apartment->loc_city) {
								$cityInfo = City::model()->findByPk($apartment->loc_city);
								if ($cityInfo && isset($cityInfo->name))
									$cityName = $cityInfo->name;
							}
						}
						else {
							if ($apartment->loc_city) {
								Yii::app()->setLanguage($this->defLang);

								$cityInfo = City::model()->findByPk($apartment->loc_city);

								if ($cityInfo && isset($cityInfo->name)) {
									$cityName = $cityInfo->name;
								}

								Yii::app()->setLanguage($this->currLang);
							}
						}
					}
					else {
						if (isFree()) {
							if ($apartment->city_id) {
								Yii::import('application.modules.apartmentCity.models.ApartmentCity');
								$cityInfo = ApartmentCity::model()->findByPk($apartment->city_id);
								if ($cityInfo && isset($cityInfo->name))
									$cityName = $cityInfo->name;
							}
						}
						else {
							if ($apartment->city_id) {
								Yii::app()->setLanguage($this->defLang);

								Yii::import('application.modules.apartmentCity.models.ApartmentCity');
								$cityInfo = ApartmentCity::model()->findByPk($apartment->city_id);

								if ($cityInfo && isset($cityInfo->name)) {
									$cityName = $cityInfo->name;
								}

								Yii::app()->setLanguage($this->currLang);
							}
						}
					}

					$isPricePoa = $apartment->is_price_poa;
					$price = $apartment->price;
					$priceTo = $apartment->price_to;

					$numberRooms = $apartment->num_of_rooms;
					$floor = $apartment->floor;
					$floorTotal = $apartment->floor_total;
					$square = $apartment->square;
					$landSquare = $apartment->land_square;
					$sleeps = $apartment->berths;


					// get title, description, near, address, exchange
					$title = $description = $near = $address = $exchangeTo = '';

					if (isFree()) {
						$titleField = 'title_'.Yii::app()->language;
						$title = $this->clearHtml($apartment->$titleField);

						$descrField = 'description_'.Yii::app()->language;
						$description = $this->clearHtml($apartment->$descrField);

						$descrNearField = 'description_near_'.Yii::app()->language;
						$near = $this->clearHtml($apartment->$descrNearField);

						$addressField = 'address_'.Yii::app()->language;
						$address = $this->clearHtml($apartment->$addressField);

						$exchangeToField = 'exchange_to_'.Yii::app()->language;
						$exchangeTo = $this->clearHtml($apartment->$exchangeToField);
					}
					else {
						if ($this->allLangs) {
							$title = $description = $near = $address = $exchangeTo = array();
							foreach($this->allLangs as $lang) {
								$titleField = 'title_'.$lang->name_iso;
								$descrField = 'description_'.$lang->name_iso;
								$descrNearField = 'description_near_'.$lang->name_iso;
								$addressField = 'address_'.$lang->name_iso;
								$exchangeToField = 'exchange_to_'.$lang->name_iso;

								$title[$lang->name_iso] = $this->clearHtml($apartment->$titleField);
								$description[$lang->name_iso] = $this->clearHtml($apartment->$descrField);
								$near[$lang->name_iso] = $this->clearHtml($apartment->$descrNearField);
								$address[$lang->name_iso] = $this->clearHtml($apartment->$addressField);
								$exchangeTo[$lang->name_iso] = $this->clearHtml($apartment->$exchangeToField);
							}
						}
					}

					// get coords
					$lat = $apartment->lat;
					$lng = $apartment->lng;

					// get photos
					$photos = $apartment->images();

					if ($photos) {
						foreach ($photos as $key => $value) {
							if ($isZip) {
								$photos[$key] = $value->file_name;
								$photosExport["{$apartment->id}_{$key}"] = $model->csvPath.DIRECTORY_SEPARATOR.$value->file_name;
								copy(Yii::getPathOfAlias('webroot.uploads.objects.'.$apartment->id.'.original').DIRECTORY_SEPARATOR.$value->file_name, $model->csvPath.DIRECTORY_SEPARATOR.$value->file_name);
							} else {
								$photos[$key] = Yii::app()->getBaseUrl(true).'/uploads/objects/'.$apartment->id.'/original/'.$value->file_name;
							}
						}
					}

					// get reference info
					$refInfo = $this->getReferenceInfo($v);

					$comfort = $refInfo['comfort'];
					$bathroom = $refInfo['bathroom'];
					$kitchen = $refInfo['kitchen'];
					$employment = $refInfo['employment'];
					$safety = $refInfo['safety'];
					$entertainment = $refInfo['entertainment'];
					$terms = $refInfo['terms'];
					$services = $refInfo['services'];

					// insert
					$fileLine[$i] = $type .$this->separator. $priceType .$this->separator . $objType .$this->separator;
					$fileLine[$i] .= $countryName .$this->separator. $regionName .$this->separator. $cityName .$this->separator. $isPricePoa .$this->separator. $price .$this->separator;
					$fileLine[$i] .= $priceTo .$this->separator. $numberRooms .$this->separator. $floor .$this->separator;
					$fileLine[$i] .= $floorTotal .$this->separator. $square .$this->separator. $landSquare .$this->separator. $this->deleteChars($sleeps) .$this->separator;

					if (isFree()) {
						$fileLine[$i] .= $this->deleteChars($title) . $this->separator . $this->deleteChars($description) . $this->separator;
						$fileLine[$i] .= $this->deleteChars($near) . $this->separator . $this->deleteChars($address) . $this->separator;
						$fileLine[$i] .= $this->deleteChars($exchangeTo) . $this->separator;
					}
					else {
						array_walk($title, array($this, 'deleteChars'));
						$fileLine[$i] .= implode($this->separator, $title).$this->separator;

						array_walk($description, array($this, 'deleteChars'));
						$fileLine[$i] .= implode($this->separator, $description).$this->separator;

						array_walk($near, array($this, 'deleteChars'));
						$fileLine[$i] .= implode($this->separator, $near).$this->separator;

						array_walk($address, array($this, 'deleteChars'));
						$fileLine[$i] .= implode($this->separator, $address).$this->separator;

						array_walk($exchangeTo, array($this, 'deleteChars'));
						$fileLine[$i] .= implode($this->separator, $exchangeTo).$this->separator;
					}

					$fileLine[$i] .= $bathroom . $this->separator . $safety . $this->separator;
					$fileLine[$i] .= $comfort . $this->separator . $kitchen . $this->separator;
					$fileLine[$i] .= $employment . $this->separator . $entertainment . $this->separator;
					$fileLine[$i] .= $services . $this->separator . $terms . $this->separator;

					$fileLine[$i] .= is_array($photos) ? implode($this->separatorElem, $photos) . $this->separator : '' . $this->separator;
					$fileLine[$i] .= $lat . $this->separator . $lng;

					$i++;
				}


				// write in file
				$handle = fopen($filePath, "w+");

				foreach ($fileLine as $item) {
					fputs($handle, $item);
					fputs($handle, "\r\n");
				}

				fclose($handle);

				if ($isZip) {
					include_once $model->libraryPath.DIRECTORY_SEPARATOR.'pclzip.lib.php';

					$arrFile = $photosExport;
					$arrFile[] = $model->csvPath.DIRECTORY_SEPARATOR.$fileName;
					$archive = new PclZip($model->csvPath.DIRECTORY_SEPARATOR.str_replace(".csv", "", $fileName) . ".zip");
					$list = $archive->create($arrFile, PCLZIP_OPT_REMOVE_ALL_PATH);

					if ($list == 0) {
						Yii::app()->user->setFlash(
							'error', $archive->errorInfo(true)
						);
						//$this->redirect('viewExport');
						$this->redirect($backUrl);
					}

					// unlink all files
					foreach ($arrFile as $item) {
						@unlink($item);
					}

					$fileName = str_replace('.csv', '.zip', $fileName);
					$filePath = $model->csvPath.DIRECTORY_SEPARATOR.$fileName;

					header("Content-Type: application/zip");
				} else {
					header("Content-Type: application/csv");
				}

				header("Content-Disposition: attachment; filename={$fileName}");
				header("Content-Transfer-Encoding: binary");
				header("Pragma: no-cache");
				header("Expires: 0");

				readfile($filePath);
				Yii::app()->end();
			}
		}
	}

	private function deleteChars($item1 = '') {
		if ($item1) {
			$item1 = trim($item1);
			$item1 = strip_tags($item1);

			$item1 = str_replace(
				array("\r\n", "\n", ";"),
				array(' '),
				$item1);

			//$item1 = truncateText($item1, 30);
		}
		return $item1;
	}

	private function clearHtml($item1 = '') {
		if ($item1) {
			$item1 = strip_tags($item1);
			$item1 = html_entity_decode($item1, ENT_QUOTES, 'UTF-8');

			$item1 = str_replace(
				array("\r\n", "\n", ";", "|"),
				array(' '),
				$item1);
			$item1 = trim($item1);


			//$item1 = truncateText($item1, 30);
		}
		return $item1;
	}

	private function getReferenceInfo($pk = '') {
		$comfort = $bathroom = $kitchen = $employment = $safety = $entertainment = $terms = $services = '';

		if (!isFree()) {
			Yii::app()->setLanguage($this->defLang);
			$apartmentInfo = HApartment::getFullInformation($pk);
			Yii::app()->setLanguage($this->currLang);
		}
		else {
			$apartmentInfo = HApartment::getFullInformation($pk);
		}

		if (is_array($apartmentInfo)) {
			foreach ($apartmentInfo as $key => $value) {
				switch ($key) {
					case 1:
						$comfort = implode($this->separatorElem, $value['values']);
						break;
					case 2:
						$bathroom = implode($this->separatorElem, $value['values']);
						break;
					case 3:
						$kitchen = implode($this->separatorElem, $value['values']);
						break;
					case 4:
						$employment = implode($this->separatorElem, $value['values']);
						break;
					case 5:
						$safety = implode($this->separatorElem, $value['values']);
						break;
					case 7:
						$entertainment = implode($this->separatorElem, $value['values']);
						break;
					case 9:
						$terms = implode($this->separatorElem, $value['values']);
						break;
					case 10:
						$services = implode($this->separatorElem, $value['values']);
						break;
					default:
						break;
				}
			}
		}

		return array(
			'comfort' => $comfort,
			'bathroom' => $bathroom,
			'kitchen' => $kitchen,
			'employment' => $employment,
			'safety' => $safety,
			'entertainment' => $entertainment,
			'terms' => $terms,
			'services' => $services,
		);
	}
}