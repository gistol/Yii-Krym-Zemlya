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

class MainController extends ModuleUserController{
	public $modelName = 'Sitemap';
	public $defaultAction = 'index';
	public $changefreq = 'daily';
	public $priority = '0.5';
	//public $dateFormat = 'Y-m-dTH:i:sP';
	public $dateFormat = 'c';
	public $isXml = 0;
	public $app;
	public $userLang;
	public $defaultLang;
	public $activeLangs;
	
	public function init() {
		if (!oreInstall::isInstalled() && !(Yii::app()->controller->module && Yii::app()->controller->module->id == 'install')) {
			$this->redirect(array('/install'));
		}
			
		setLang();
		
		$this->app = Yii::app();
		$this->showSearchForm = false;	
		$this->userLang = $this->defaultLang = Yii::app()->language;
		$this->activeLangs = array($this->defaultLang => $this->defaultLang);

		if(!isFree()){
			$this->defaultLang = Lang::getDefaultLang();
			$this->activeLangs = Lang::getActiveLangs();
		}

		parent::init();
	}

	public function actionIndex() {
		if (!$this->isXml) {
			Sitemap::publishAssets();
		}
		$map = $this->generateMap($this->isXml);
		$this->render('index', array('map' => $map));
	}

	public function actionViewXml() {
		Controller::disableProfiler();
		$this->isXml = 1;
		
		$map = $this->generateMap($this->isXml);

		if (is_array($map) && count($map) > 0) {
			header('Content-type: text/xml');
			header('Pragma: public');
			header('Cache-control: private');
			header('Expires: -1');
			
			$resSiteMap = Yii::app()->cache->get("siteMapXml{$this->userLang}");
			if($resSiteMap !== false){
				echo $resSiteMap;
				Yii::app()->end();
			}

			$xml = new SimpleXMLElement('<?xml version="1.0" encoding="UTF-8"?><urlset/>');
			$xml->addAttribute('xmlns', 'http://www.sitemaps.org/schemas/sitemap/0.9');
						
			foreach ($map as $item){
				if (isset($item['url'])) {
					$this->prepareItem($item, $xml);
				}
				if (isset($item['subsection']) && count($item['subsection']) > 0) {
					foreach ($item['subsection'] as $value) {
						if (isset($value['url'])) {
							$this->prepareItem($value, $xml);
						}
						if (isset($value['apartments']) && count($value['apartments']) > 0) {
							foreach ($value['apartments'] as $apartment) {
								if (isset($apartment['url'])) {
									$this->prepareItem($apartment, $xml);
								}
							}
						}
					}
				}
			}
			
			$res = $xml->asXML();
			
			if (param('cachingTime'))
				Yii::app()->cache->set("siteMapXml{$this->userLang}", $res, 60*60*param('cachingTime'));
			
			echo $res;
			Yii::app()->end();
		}
		else {
			echo 'no elements';
		}

	}

	public function prepareItem($item = array(), $xml = null) {
		if (!empty($item) && $xml) {
			if (is_string($item['url'])) {
				$elem = $xml->addChild('url');
				
				$elem->addChild('loc', $item['url']);
				if (isset($item['lastmod'])){
					$elem->addChild('lastmod', $item['lastmod']);
				}
				$elem->addChild('changefreq', $this->changefreq);
				$elem->addChild('priority', $this->priority);
			}
			elseif (is_array($item['url'])) {
				foreach ($item['url'] as $keyUrl => $valUrl) {					
					if (isset($item['url'][$keyUrl]) && !empty($item['url'][$keyUrl])) {
						$elem = $xml->addChild('url');
						$elem->addChild('loc', $item['url'][$keyUrl]);
						
						if (isset($item['lastmod'][$keyUrl]))  {
							$elem->addChild('lastmod', $item['lastmod'][$keyUrl]);
						}
						$elem->addChild('changefreq', $this->changefreq);
						$elem->addChild('priority', $this->priority);
					}	
				}
			}
		}
	}

	private function generateMap($isXml = 0) {
		
		$resAllMap = Yii::app()->cache->get("resAllMap{$this->userLang}{$this->isXml}");
		if($resAllMap !== false)
			return $resAllMap;
		
		$map = $excludeFromInfoPages = array();
		$defaultLastMod = date($this->dateFormat, time());
		$articleAll = $menuAll = $entriesAll = $infoPagesAll = '';

		// apartments module
		if (issetModule('apartments')) {
			if ($isXml) {
				$dependencyApartment = new CDbCacheDependency('SELECT MAX(date_updated) FROM {{apartment}}');
			}
		}

		// article module
		if (issetModule('articles')) {
			$articlePage = Menu::model()->findByPk(Menu::ARTICLES_ID);

			if ($articlePage && $articlePage->active == 1) {
				Yii::import('application.modules.articles.models.Article');

				$dependencyArticle = new CDbCacheDependency('SELECT MAX(date_updated) FROM {{articles}}');
				$articleAll = Article::model()->cache(param('cachingTime', 1209600), $dependencyArticle)->findAll(array(
					'condition' => 'active = 1',
				));

				if ($isXml) {
					$sql = 'SELECT MAX(date_updated) as date_updated FROM {{articles}}';
					$maxUpdatedArticles = Yii::app()->db->createCommand($sql)->queryRow();
					$maxUpdatedArticles = isset($maxUpdatedArticles['date_updated']) ? date($this->dateFormat, strtotime($maxUpdatedArticles['date_updated'])) : $defaultLastMod;
				}
			}
		}

		// menumanager module
		if (issetModule('menumanager')) {
			$dependencyInfoPages = new CDbCacheDependency('SELECT MAX(date_updated) as date_updated FROM {{menu}}');
			$menuAll = Menu::model()->cache(param('cachingTime', 1209600), $dependencyInfoPages)->findAll(array(
				'order' => 'number',
				'condition' => 'active = 1 AND (special = 0 OR id = 5)',
			));

			if ($isXml) {
				$sql = 'SELECT MAX(date_updated) as date_updated FROM {{menu}}';
				$maxUpdatedMenu = Yii::app()->db->createCommand($sql)->queryRow();
				$maxUpdatedMenu = isset($maxUpdatedMenu['date_updated']) ? date($this->dateFormat, strtotime($maxUpdatedMenu['date_updated'])) : $defaultLastMod;
			}
			
			if ($menuAll) {
				foreach($menuAll as $menuItem) {
					if ($menuItem->type == Menu::LINK_NEW_INFO && $menuItem->pageId && $menuItem->special != 1) {
						$excludeFromInfoPages[] = $menuItem->pageId;
					}
				}
			}
		}
		
		// infopages module
		if (issetModule('menumanager')) {
			$dependencyInfoPages = new CDbCacheDependency('SELECT MAX(date_updated) as date_updated FROM {{infopages}}');
			
			$condition = 'active = '.InfoPages::STATUS_ACTIVE.' AND id != 1';
			if ($excludeFromInfoPages && count($excludeFromInfoPages)) {
				$condition .= ' AND id NOT IN('.  implode(',', $excludeFromInfoPages).') ';
			}
			
			$infoPagesAll = InfoPages::model()->cache(param('cachingTime', 1209600), $dependencyInfoPages)->findAll(array(
				'condition' => $condition,
			));

			if ($isXml) {
				$sql = 'SELECT MAX(date_updated) as date_updated FROM {{infopages}}';
				$maxUpdatedInfo = Yii::app()->db->createCommand($sql)->queryRow();
				$maxUpdatedInfo = isset($maxUpdatedInfo['date_updated']) ? date($this->dateFormat, strtotime($maxUpdatedInfo['date_updated'])) : $defaultLastMod;
			}
		}

		// entries module
		if (issetModule('entries')) {
			$entriesCategories = EntriesCategory::model()->findAll();
			
			if ($entriesCategories && is_array($entriesCategories)) {
				$entries = Entries::model()->cache(param('cachingTime', 1209600), new CDbCacheDependency('SELECT MAX(date_updated) FROM {{entries}}'))->findAll();
				$langs = Lang::getActiveLangs();
				
				if ($this->activeLangs && is_array($this->activeLangs)) {
					foreach ($this->activeLangs as $keyLang => $valLang) {
						$this->app->setLanguage($valLang);
						
						foreach($entriesCategories as $category) {
							$entriesAll[$keyLang]['categories'][$category->id]['name'] = $category->getName();
							$entriesAll[$keyLang]['categories'][$category->id]['url'] = $category->getUrl();
							
							if ($entries && is_array($entries)) {
								$i = 0;
								foreach($entries as $entry) {
									if ($entry->category_id == $category->id) {
										$entriesAll[$keyLang]['categories'][$category->id]['items'][$i]['name'] = $entry->getStrByLang('title');
										$entriesAll[$keyLang]['categories'][$category->id]['items'][$i]['url'] = $entry->getUrl();
										$entriesAll[$keyLang]['categories'][$category->id]['items'][$i]['date_updated'] = $entry->date_updated;
									}
									$i++;
								}
							}
						}
					}
					$this->app->setLanguage($this->userLang);
				}

				if ($isXml) {
					$sql = 'SELECT MAX(date_updated) as date_updated FROM {{entries}}';
					$maxUpdatedEntries = Yii::app()->db->createCommand($sql)->queryRow();
					$maxUpdatedEntries = isset($maxUpdatedEntries['date_updated']) ? date($this->dateFormat, strtotime($maxUpdatedEntries['date_updated'])) : $defaultLastMod;
				}
			}
		}


		####################################### index page #######################################
		if ($isXml) {
			if ($this->activeLangs && is_array($this->activeLangs)) {
				foreach ($this->activeLangs as $keyLang => $valLang) {
					$this->app->setLanguage($valLang);

					$map['index_page']['title'][$keyLang] = tt('index_page');
					$map['index_page']['url'][$keyLang] = Yii::app()->createAbsoluteUrl('/');
					$map['index_page']['lastmod'][$keyLang] = (isset($indexPageInfo) && isset($indexPageInfo->date_updated)) ? date($this->dateFormat, strtotime($indexPageInfo->date_updated)) : $defaultLastMod;
				}
			}
			$this->app->setLanguage($this->defaultLang);
		}
		else {
			$map['index_page']['title'] = tt('index_page');
			$map['index_page']['url'] = Yii::app()->createAbsoluteUrl('/');
		}


		####################################### contact form and booking form #######################################
		if ($isXml) {
			if ($this->activeLangs && is_array($this->activeLangs)) {
				foreach ($this->activeLangs as $keyLang => $valLang) {
					$this->app->setLanguage($valLang);

					$map['contact_form']['title'][$keyLang] = tt('contact_form');
					$map['contact_form']['url'][$keyLang] = Yii::app()->createAbsoluteUrl('contactform/main/index');
					$map['contact_form']['lastmod'][$keyLang] = (isset($indexPageInfo) && isset($indexPageInfo->date_updated)) ? date($this->dateFormat, strtotime($indexPageInfo->date_updated)) : $defaultLastMod;

					$map['booking_form']['title'][$keyLang] = tt('booking_form');
					$map['booking_form']['url'][$keyLang] = Yii::app()->createAbsoluteUrl('booking/main/mainform');
					$map['booking_form']['lastmod'][$keyLang] = (isset($indexPageInfo) && isset($indexPageInfo->date_updated)) ? date($this->dateFormat, strtotime($indexPageInfo->date_updated)) : $defaultLastMod;
				}
			}
			$this->app->setLanguage($this->defaultLang);
		}
		else {
			$map['contact_form']['title'] = tt('contact_form');
			$map['contact_form']['url'] = Yii::app()->createAbsoluteUrl('contactform/main/index');

			$map['booking_form']['title'] = tt('booking_form');
			$map['booking_form']['url'] = Yii::app()->createAbsoluteUrl('booking/main/mainform');
		}

		####################################### search #######################################
		if ($isXml) {
			if ($this->activeLangs && is_array($this->activeLangs)) {
				foreach ($this->activeLangs as $keyLang => $valLang) {
					$this->app->setLanguage($valLang);

					$map['quick_search']['title'][$keyLang] = tt('quick_search');
					$map['quick_search']['url'][$keyLang] = Yii::app()->createAbsoluteUrl('quicksearch/main/mainsearch');

					$sql = 'SELECT MAX(date_updated) as date_updated FROM {{apartment}}';
					$maxUpdatedApartment = Yii::app()->db->createCommand($sql)->queryRow();
					$maxUpdatedApartment = isset($maxUpdatedApartment['date_updated']) ? date($this->dateFormat, strtotime($maxUpdatedApartment['date_updated'])) : $defaultLastMod;

					$map['quick_search']['lastmod'][$keyLang] = $maxUpdatedApartment;
				}
			}
			$this->app->setLanguage($this->defaultLang);
		}
		else {
			$map['quick_search']['title'] = tt('quick_search');
			$map['quick_search']['url'] = Yii::app()->createAbsoluteUrl('quicksearch/main/mainsearch');
		}


		####################################### search subtypes #######################################
		$types = SearchForm::apTypes();
		if (is_array($types) && isset($types['propertyType'])) {
			$i = 0;
			foreach ($types['propertyType'] as $key => $value) {
				if ($key > 0) {
					if ($isXml) {
						$apartmentsByType = null;

						$result = null;
						$titles = array();
						if ($this->activeLangs && is_array($this->activeLangs)) {
							foreach ($this->activeLangs as $keyLang => $valLang) {
								$titles[] = 'title_'.$valLang;
							}
						}

						if (count($titles)) {
							$titles = implode(',', $titles);

							$sql = 'SELECT id, '.$titles.', date_updated FROM {{apartment}} WHERE price_type = ' . $key . ' AND active = ' . Apartment::STATUS_ACTIVE . ' AND owner_active = ' . Apartment::STATUS_ACTIVE . ' ORDER BY date_updated DESC';
							$result = Yii::app()->db->createCommand($sql)->queryAll();
						}

						if ($result) {
							foreach($result as $item) {
								$apartmentsByType[$item['id']] = $item;
							}
						}

						$k = 0;
						if (is_array($apartmentsByType) && count($apartmentsByType) > 0) {
							$urls = array();
							if ($this->activeLangs && is_array($this->activeLangs)) {
								foreach ($this->activeLangs as $keyLang => $valLang) {
									$urls[] = 'url_'.$valLang;
								}
							}

							if (issetModule('seo') && count($urls)) {
								$urls = implode(',', $urls);

								$sql = 'SELECT model_id, '.$urls.' FROM {{seo_friendly_url}} WHERE model_name="Apartment" GROUP BY model_id ORDER BY id ASC';
								$resultSEO = Yii::app()->db->createCommand($sql)->queryAll();

								if ($resultSEO) {
									foreach($resultSEO as $item) {
										if (isset($apartmentsByType[$item['model_id']])) {
											$apartmentsByType[$item['model_id']] = CMap::mergeArray($apartmentsByType[$item['model_id']], $item);
										}
									}
								}
							}

							foreach ($apartmentsByType as $value) {
								if ($this->activeLangs && is_array($this->activeLangs)) {
									foreach ($this->activeLangs as $keyLang => $valLang) {
										$this->app->setLanguage($valLang);

										if (isset($value['title_'.$valLang]) && $value['title_'.$valLang])
											$map['quick_search']['subsection'][$i]['apartments'][$k]['title'][$keyLang] = $value['title_'.$valLang];

										if (isset($value['url_'.$valLang]) && $value['url_'.$valLang])
											$map['quick_search']['subsection'][$i]['apartments'][$k]['url'][$keyLang] = Yii::app()->createAbsoluteUrl('/apartments/main/view', array('url' => $value['url_'.$valLang] . (param('urlExtension') ? '.html' : '')));
										else
											$map['quick_search']['subsection'][$i]['apartments'][$k]['url'][$keyLang] = Yii::app()->createAbsoluteUrl('/apartments/main/view', array('id' => $value['id']));

										if (isset($value['date_updated']) && $value['date_updated'])
											$map['quick_search']['subsection'][$i]['apartments'][$k]['lastmod'][$keyLang] = date($this->dateFormat, strtotime($value['date_updated']));
									}
								}
								$this->app->setLanguage($this->defaultLang);
								$k++;
							}
						}
					}
					else {
						//$map['apartment_types'][$i]['title'] = mb_convert_case($value, MB_CASE_TITLE, "UTF-8");
						$map['quick_search']['subsection'][$i]['title'] = $value;
						$map['quick_search']['subsection'][$i]['url'] = Yii::app()->createAbsoluteUrl('quicksearch/main/mainsearch', array('apType' => $key));
					}
					$i++;
				}
			}
		}

		####################################### search object types #######################################
		$objTypes = Apartment::getObjTypesArray();
		if (is_array($objTypes)) {
			$i = 1;
			if (array_key_exists('subsection',$map['quick_search'])) {
				if ($isXml) {
					$countSubsection = count($map['quick_search']['subsection']);
					if ($this->activeLangs && is_array($this->activeLangs)) {
						foreach ($this->activeLangs as $keyLang => $valLang) {
							foreach ($objTypes as $key => $value) {
								$this->app->setLanguage($valLang);

								$map['quick_search']['subsection'][$countSubsection+$i]['title'][$keyLang] = $value;
								$map['quick_search']['subsection'][$countSubsection+$i]['url'][$keyLang] = Yii::app()->createAbsoluteUrl('quicksearch/main/mainsearch', array('objType' => $key));
								$i++;
							}
						}
						$this->app->setLanguage($this->defaultLang);
					}
				}
				else {
					$countSubsection = count($map['quick_search']['subsection']);
					foreach ($objTypes as $key => $value) {
						$map['quick_search']['subsection'][$countSubsection+$i]['title'] = $value;
						$map['quick_search']['subsection'][$countSubsection+$i]['url'] = Yii::app()->createAbsoluteUrl('quicksearch/main/mainsearch', array('objType' => $key));
						$i++;
					}
				}
			}
			// no in xml because all links to listings generated above in search subtypes section
			// duplication link is not needed.
		}


		####################################### special offers  #######################################
		if (issetModule('specialoffers')) {
			$specialOfferPage = Menu::model()->findByPk(Menu::SPECIALOFFERS_ID);

			if ($specialOfferPage && $specialOfferPage->active == 1) {
				$i = 0;

				if ($isXml) {
					$map['special_offers']['title'] = tt('special_offers');
					$map['special_offers']['url'] = Yii::app()->createAbsoluteUrl('specialoffers/main/index');
					$map['special_offers']['lastmod'] = $maxUpdatedApartment;

					$specialOffers = Apartment::model()->cache(param('cachingTime', 1209600), $dependencyApartment)->findAllByAttributes(array('is_special_offer' => 1), 'active = :active AND owner_active = :ownerActive', array(':active' => Apartment::STATUS_ACTIVE, ':ownerActive' => Apartment::STATUS_ACTIVE));
					$k = 0;
					if (is_array($specialOffers) && count($specialOffers) > 0) {
						foreach ($specialOffers as $value) {
							if ($this->activeLangs && is_array($this->activeLangs)) {
								foreach ($this->activeLangs as $keyLang => $valLang) {
									$this->app->setLanguage($valLang);

									$map['special_offers']['subsection'][$k]['title'][$keyLang] = $value->getStrByLang('title');
									$map['special_offers']['subsection'][$k]['url'][$keyLang] = $value->getUrl();
									$map['special_offers']['subsection'][$k]['lastmod'][$keyLang] = date($this->dateFormat, strtotime($value['date_updated']));
								}
							}
							$this->app->setLanguage($this->defaultLang);
							$k++;
						}
					}
				}
				else {
					$map['special_offers']['title'] = tt('special_offers');
					$map['special_offers']['url'] = Yii::app()->createAbsoluteUrl('specialoffers/main/index');
				}
			}
		}


		####################################### get all menu pages  #######################################
		if (is_array($menuAll) && $menuAll > 0) {
			$i = 0;

			if ($isXml) {
				if ($this->activeLangs && is_array($this->activeLangs)) {
					foreach ($this->activeLangs as $keyLang => $valLang) {
						$this->app->setLanguage($valLang);

						$map['section_infopage']['title'][$keyLang] = tt('section_infopage');
						$map['section_infopage']['url'][$keyLang] = null;
						$map['section_infopage']['lastmod'][$keyLang] = $maxUpdatedMenu;

						foreach ($menuAll as $value) {
							// убираем из карты сайта типы "Простая ссылка" и "Простая ссылка в выпад. списке"

							if ($value['type'] != Menu::LINK_NEW_MANUAL && $value['type'] != Menu::LINK_NONE) {
								$title = $value->getTitle();
								if ($title && $value['id'] != 1) {
									$map['section_infopage']['subsection'][$i]['title'][$keyLang] = $title;

									if($value['type'] == Menu::LINK_NEW_INFO){
										$href = $value->getUrl();
									} else {
										if($value['id'] == Menu::SITEMAP_ID){ // sitemap
											$href = Yii::app()->controller->createAbsoluteUrl('/sitemap/main/index');
										}
									}

									$map['section_infopage']['subsection'][$i]['url'][$keyLang] = $href;
									$map['section_infopage']['subsection'][$i]['lastmod'][$keyLang] = date($this->dateFormat, strtotime($value['date_updated']));

									$i++;
								}
							}
						}
					}
				}
				$this->app->setLanguage($this->defaultLang);
			}
			else {
				$map['section_infopage']['title'] = tt('section_infopage');
				$map['section_infopage']['url'] = null;

				foreach ($menuAll as $value) {
					$title = $value->getTitle();
					if ($title && $value['id'] != Menu::MAIN_PAGE_ID && $value['type'] != Menu::LINK_NONE) {
						$map['section_infopage']['subsection'][$i]['title'] = $title;

						$href = '';
						if($value['type'] == Menu::LINK_NEW_INFO){
							$href = $value->getUrl();
						} else {
							if($value['id'] == Menu::SITEMAP_ID){ // sitemap
								$href = Yii::app()->controller->createAbsoluteUrl('/sitemap/main/index');
							}
							else {
								$href = $value->getUrl();
							}
						}

						if ($href)
							$map['section_infopage']['subsection'][$i]['url'] = $href;

						$i++;
					}
				}
			}
		}
		
		
		####################################### get all infopages  #######################################
		if (is_array($infoPagesAll) && $infoPagesAll > 0) {
			$issetSectionInfoPages = (isset($map['section_infopage']) && isset($map['section_infopage']['subsection'])) ? true : false;
			$i = ($issetSectionInfoPages) ? count($map['section_infopage']['subsection']) + 1 : 0;

			if ($isXml) {
				if ($this->activeLangs && is_array($this->activeLangs)) {
					foreach ($this->activeLangs as $keyLang => $valLang) {
						$this->app->setLanguage($valLang);

						if (!$issetSectionInfoPages) {
							$map['section_infopage']['title'][$keyLang] = tt('section_infopage');
							$map['section_infopage']['url'][$keyLang] = null;
							$map['section_infopage']['lastmod'][$keyLang] = $maxUpdatedInfo;
						}

						foreach ($infoPagesAll as $value) {
								$title = $value->getTitle();
								$href = $value->getUrl();
								if ($title && $href) {
									$map['section_infopage']['subsection'][$i]['title'][$keyLang] = $title;
									$map['section_infopage']['subsection'][$i]['url'][$keyLang] = $href;
									$map['section_infopage']['subsection'][$i]['lastmod'][$keyLang] = date($this->dateFormat, strtotime($value['date_updated']));									

									$i++;
								}
						}
					}
				}
				$this->app->setLanguage($this->defaultLang);
			}
			else {
				if (!$issetSectionInfoPages) {
					$map['section_infopage']['title'] = tt('section_infopage');
					$map['section_infopage']['url'] = null;
				}

				foreach ($infoPagesAll as $value) {
					$title = $value->getTitle();
					$href = $value->getUrl();
					if ($title && $href) {
						$map['section_infopage']['subsection'][$i]['title'] = $title;
						$map['section_infopage']['subsection'][$i]['url'] = $href;

						$i++;
					}
				}
			}
		}

		####################################### get all entries #######################################
		if (is_array($entriesAll) && count($entriesAll) > 0) {			
			if ($isXml) {
				if ($this->activeLangs && is_array($this->activeLangs)) {					
					foreach ($this->activeLangs as $keyLang => $valLang) {
						$this->app->setLanguage($valLang);
						
						if(isset($entriesAll[$valLang]) && is_array($entriesAll[$valLang])) {
							foreach ($entriesAll[$valLang] as $valuesArr) {
								if(is_array($valuesArr)) {
									foreach ($valuesArr as $key => $values) {
										$map['section_entries_'.$key]['title'][$keyLang] = $values['name'];
										$map['section_entries_'.$key]['url'][$keyLang] = $values['url'];
										$map['section_entries_'.$key]['lastmod'][$keyLang] = $maxUpdatedEntries;

										if(isset($values['items']) && count($values['items'])) {
											$i = 0;
											foreach ($values['items'] as $item) {
												if (isset($item['url']) && isset($item['name'])) {
													$map['section_entries_'.$key]['subsection'][$i]['title'][$keyLang] = $item['name'];
													$map['section_entries_'.$key]['subsection'][$i]['url'][$keyLang] = $item['url'];
													$map['section_entries_'.$key]['subsection'][$i]['lastmod'][$keyLang] = date($this->dateFormat, strtotime($item['date_updated']));

													$i++;
												}
											}
										}
										else {
											unset($map['section_entries_'.$key]);
										}
									}
								}
							}
						}	
					}
				}				
				$this->app->setLanguage($this->defaultLang);
			}
			else {
				foreach($entriesAll as $entryLang => $entries) {					
					if($entryLang == $this->userLang && is_array($entriesAll[$entryLang])) {
						foreach ($entriesAll[$entryLang] as $valuesArr) {
							if(is_array($valuesArr)) {
								foreach ($valuesArr as $key => $values) {
									$map['section_entries_'.$key]['title'] = $values['name'];
									$map['section_entries_'.$key]['url'] = $values['url'];
									
									if(isset($values['items']) && count($values['items'])) {
										$i = 0;
										foreach ($values['items'] as $item) {
											if (isset($item['url']) && isset($item['name'])) {
												$map['section_entries_'.$key]['subsection'][$i]['title'] = $item['name'];
												$map['section_entries_'.$key]['subsection'][$i]['url'] = $item['url'];
												
												$i++;
											}
										}
									}
									else {
										unset($map['section_entries_'.$key]);
									}
								}
							}
						}
					}
				}
			}
		}
		
		####################################### get all article #######################################
		if (is_array($articleAll) && count($articleAll) > 0) {
			$i = 0;

			if ($isXml) {
				if ($this->activeLangs && is_array($this->activeLangs)) {
					foreach ($this->activeLangs as $keyLang => $valLang) {
						$this->app->setLanguage($valLang);

						$map['section_article']['title'] = tt('section_article');
						$map['section_article']['url'] = Yii::app()->createAbsoluteUrl('articles/main/index');
						$map['section_article']['lastmod'] = $maxUpdatedArticles;

						foreach ($articleAll as $value) {
							$title = $value->getPage_title();
							if ($title) {
								$map['section_article']['subsection'][$i]['title'] = $title;
								$map['section_article']['subsection'][$i]['url'] = $value->getUrl();
								$map['section_article']['subsection'][$i]['lastmod'] = date($this->dateFormat, strtotime($value['date_updated']));
								$i++;
							}
						}
					}
				}
				$this->app->setLanguage($this->defaultLang);
			}
			else {
				$map['section_article']['title'] = tt('section_article');
				$map['section_article']['url'] = Yii::app()->createAbsoluteUrl('articles/main/index');

				foreach ($articleAll as $value) {
					$title = $value->getPage_title();
					if ($title) {
						$map['section_article']['subsection'][$i]['title'] = $title;
						$map['section_article']['subsection'][$i]['url'] = $value->getUrl();
						$i++;
					}
				}
			}
		}

		####################################### reviews  #######################################
		if (issetModule('reviews')) {
			$reviewsPage = Menu::model()->findByPk(Menu::REVIEWS_ID);

			if ($reviewsPage && $reviewsPage->active == 1) {
				$i = 0;

				if ($isXml) {
					$sql = 'SELECT MAX(date_updated) as date_updated FROM {{reviews}}';
					$maxUpdatedReviews = Yii::app()->db->createCommand($sql)->queryScalar();

					if ($this->activeLangs && is_array($this->activeLangs)) {
						foreach ($this->activeLangs as $keyLang => $valLang) {
							$this->app->setLanguage($valLang);

							$map['reviews']['title'][$keyLang] = tt('Reviews', 'reviews');
							$map['reviews']['url'][$keyLang] = Yii::app()->createAbsoluteUrl('reviews/main/index');
							$map['reviews']['lastmod'][$keyLang] = date($this->dateFormat, strtotime($maxUpdatedReviews));
						}
					}
					$this->app->setLanguage($this->defaultLang);

				}
				else {
					$map['reviews']['title'] = tt('Reviews', 'reviews');
					$map['reviews']['url'] = Yii::app()->createAbsoluteUrl('reviews/main/index');
				}
			}
		}

		if (param('cachingTime'))
			Yii::app()->cache->set("resAllMap{$this->userLang}{$this->isXml}", $map, 60*60*param('cachingTime'));
		
		return $map;
	}
}