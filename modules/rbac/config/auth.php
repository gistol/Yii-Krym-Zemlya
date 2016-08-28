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

return array(
	'upload_from_wysiwyg' => array(
		'type' => CAuthItem::TYPE_OPERATION,
		'description' => 'загрузка изображений из wysiwyg',
		'bizRule' => null,
		'data' => null,
	),
	'backend_access' => array(
		'type' => CAuthItem::TYPE_OPERATION,
		'description' => 'Доступ к определённым действиям админки ( для модераторов и администраторов )',
		'bizRule' => null,
		'data' => null,
	),
	'apartments_admin' => array(
		'type' => CAuthItem::TYPE_OPERATION,
		'description' => 'управление объявлениями',
		'bizRule' => null,
		'data' => null,
	),
	'comments_admin' => array(
		'type' => CAuthItem::TYPE_OPERATION,
		'description' => 'управление комментариями',
		'bizRule' => null,
		'data' => null,
	),
	'apartmentsComplain_admin' => array(
		'type' => CAuthItem::TYPE_OPERATION,
		'description' => 'управление жалобами к объявлениям',
		'bizRule' => null,
		'data' => null,
	),
	'bookingtable_admin' => array(
		'type' => CAuthItem::TYPE_OPERATION,
		'description' => 'бронирование недвижимости',
		'bizRule' => null,
		'data' => null,
	),
	'users_admin' => array(
		'type' => CAuthItem::TYPE_OPERATION,
		'description' => 'управление пользователями',
		'bizRule' => null,
		'data' => null,
	),
	'reviews_admin' => array(
		'type' => CAuthItem::TYPE_OPERATION,
		'description' => 'управление отзывами',
		'bizRule' => null,
		'data' => null,
	),
	'clients_admin' => array(
		'type' => CAuthItem::TYPE_OPERATION,
		'description' => 'управление клиентами',
		'bizRule' => null,
		'data' => null,
	),
	'entries_admin' => array(
		'type' => CAuthItem::TYPE_OPERATION,
		'description' => 'управление новостями',
		'bizRule' => null,
		'data' => null,
	),
	'entries_news_product_admin' => array(
		'type' => CAuthItem::TYPE_OPERATION,
		'description' => 'управление новостями продукта',
		'bizRule' => null,
		'data' => null,
	),
	'entries_category_admin' => array(
		'type' => CAuthItem::TYPE_OPERATION,
		'description' => 'управление разделами новостей',
		'bizRule' => null,
		'data' => null,
	), 
	'articles_admin' => array(
		'type' => CAuthItem::TYPE_OPERATION,
		'description' => 'управление вопрос-ответ',
		'bizRule' => null,
		'data' => null,
	),
	'menumanager_admin' => array(
		'type' => CAuthItem::TYPE_OPERATION,
		'description' => 'управление верхним меню',
		'bizRule' => null,
		'data' => null,
	),
	'infopages_admin' => array(
		'type' => CAuthItem::TYPE_OPERATION,
		'description' => 'управление инфостраницами',
		'bizRule' => null,
		'data' => null,
	),
	'messages_admin' => array(
		'type' => CAuthItem::TYPE_OPERATION,
		'description' => 'управление рассылкой',
		'bizRule' => null,
		'data' => null,
	),
	'payment_admin' => array(
		'type' => CAuthItem::TYPE_OPERATION,
		'description' => 'управление платежами',
		'bizRule' => null,
		'data' => null,
	),
	'paidservices_admin' => array(
		'type' => CAuthItem::TYPE_OPERATION,
		'description' => 'управление платными услугами',
		'bizRule' => null,
		'data' => null,
	),
	'all_reference_admin' => array(
		'type' => CAuthItem::TYPE_OPERATION,
		'description' => 'управление всеми справочниками',
		'bizRule' => null,
		'data' => null,
	),
	'all_settings_admin' => array(
		'type' => CAuthItem::TYPE_OPERATION,
		'description' => 'управление всеми настройками',
		'bizRule' => null,
		'data' => null,
	),
	'all_lang_and_currency_admin' => array(
		'type' => CAuthItem::TYPE_OPERATION,
		'description' => 'управление языками и валютой',
		'bizRule' => null,
		'data' => null,
	),
	'all_modules_admin' => array(
		'type' => CAuthItem::TYPE_OPERATION,
		'description' => 'управление всеми модулями',
		'bizRule' => null,
		'data' => null,
	),
	'tariff_plans_admin' => array(
		'type' => CAuthItem::TYPE_OPERATION,
		'description' => 'управление тарифными планами',
		'bizRule' => null,
		'data' => null,
	),
	'blockip_admin' => array(
		'type' => CAuthItem::TYPE_OPERATION,
		'description' => 'Добавление IP в чёрный список',
		'bizRule' => null,
		'data' => null,
	),
	'change_apartment_visits' => array(
		'type' => CAuthItem::TYPE_OPERATION,
		'description' => 'Изменение кол-ва просмотров',
		'bizRule' => null,
		'data' => null,
	),
	'stats_admin' => array(
		'type' => CAuthItem::TYPE_OPERATION,
		'description' => 'Просмотр статистики',
		'bizRule' => null,
		'data' => null,
	),
	'service_site_admin' => array(
		'type' => CAuthItem::TYPE_OPERATION,
		'description' => 'обслуживание сайта',
		'bizRule' => null,
		'data' => null,
	),
	'metro_stations_admin' => array(
		'type' => CAuthItem::TYPE_OPERATION,
		'description' => 'управление станциями метро',
		'bizRule' => null,
		'data' => null,
	),
	'historyChanges_admin' => array(
		'type' => CAuthItem::TYPE_OPERATION,
		'description' => 'управление историей изменений',
		'bizRule' => null,
		'data' => null,
	),
	


	/****************************************************************************************/

	'guest' => array(
		'type' => CAuthItem::TYPE_ROLE,
		'description' => 'Гость',
		'bizRule' => null,
		'data' => null
	),
	'registered' => array(
		'type' => CAuthItem::TYPE_ROLE,
		'description' => 'Зарегистрированный пользователь',
		'children' => array(
			'guest', // унаследуемся от гостя
		),
		'bizRule' => null,
		'data' => null
	),
	'moderator' => array(
		'type' => CAuthItem::TYPE_ROLE,
		'description' => 'Модератор',
		'children' => array(
			'registered', // унаследуемся от зарегистрированного пользователя
			'upload_from_wysiwyg',
			'backend_access',
			'apartments_admin',
			'comments_admin',
			'apartmentsComplain_admin',
			'bookingtable_admin',
			'users_admin',
			'reviews_admin',
			'clients_admin',
			'entries_admin',
			'entries_category_admin',
			'articles_admin',
			'blockip_admin',
			'change_apartment_visits',
			'stats_admin',
			'paidservices_admin',
		),
		'bizRule' => null,
		'data' => null
	),
	'admin' => array(
		'type' => CAuthItem::TYPE_ROLE,
		'description' => 'Администратор',
		'children' => array(
			'moderator', // унаследуемся от модератора
			'menumanager_admin',
			'infopages_admin',
			'messages_admin',
			'payment_admin',
			'all_reference_admin',
			'all_settings_admin',
			'all_lang_and_currency_admin',
			'all_modules_admin',
			'tariff_plans_admin',
			'service_site_admin',
			'metro_stations_admin',
			'historyChanges_admin',
			'entries_news_product_admin',
		),
		'bizRule' => null,
		'data' => null
	),
);