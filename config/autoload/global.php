<?php

if(!function_exists('apache_getenv')) {
	function apache_getenv($str) { return getenv($str) ? getenv($str) : 'development'; }
}

$env = apache_getenv("APP_ENV");

return array(
	'locales' => array(
		'defaults' => 'en',
		'list' => array(
			'en' => array(
				'short' => 'en',
				'locale' => 'en_US.UTF-8',
				'name' => 'English',
				'currency' => 'USD',
				'timezone' => 'America/New_York',
				'fallbackurllang' => 'en'
			),
			'de' => array(
				'short' => 'de',
				'locale' => 'de_DE.UTF-8',
				'name' => 'Deutsch',
				'currency' => 'EUR',
				'timezone' => 'Europe/Berlin',
				'fallbackurllang' => 'de'
			),
			'it' => array(
				'short' => 'it',
				'locale' => 'it_IT.UTF-8',
				'name' => 'Italiano',
				'currency' => 'EUR',
				'timezone' => 'Europe/Rome',
				'fallbackurllang' => 'en'
			),
			'zh' => array(
				'short' => 'zh',
				'locale' => 'zh_CN.UTF-8',
				'name' => '中文',
				'currency' => 'RMB',
				'timezone' => 'Asia/Hong_Kong',
				'fallbackurllang' => 'en'
			),
			'ja' => array(
				'short' => 'ja',
				'locale' => 'ja_JP.UTF-8',
				'name' => '日本語',
				'currency' => 'JPY',
				'timezone' => 'Asia/Tokyo',
				'fallbackurllang' => 'en'
			)
		)
	),
	'session' => array(
		'config' => array(
			'class' => 'Zend\Session\Config\SessionConfig',
			'options' => array(
				'remember_me_seconds' => 60 * 60 * 24 * 365 * 10,
				'use_cookies' => true,
				'cookie_httponly' => true,
				"name" => "mSID",
				"cookie_lifetime" => 60 * 60 * 24 * 365
			)
		),
		'validators' => array(
			'Zend\Session\Validator\RemoteAddr',
			'Zend\Session\Validator\HttpUserAgent'
		)
	),
	'service_manager' => array(
		'abstract_factories' => array(
			'Zend\Cache\Service\StorageCacheAbstractServiceFactory',
			'Zend\Log\LoggerAbstractServiceFactory'
		),
		'aliases' => array(
			'translator' => 'MvcTranslator'
		)
	),
	'controller_plugins' => array(
		'invokables' => array(
		   'MyAclPlugin' => 'Application\Plugin\MyAclPlugin'
		)
	),
	'translator' => array(
		'locale' => 'de_DE',
		'translation_file_patterns' => array(
			array(
				'type' => 'gettext',
				'base_dir' => getcwd() . '/module/Application/language',
				'pattern' => '%s.mo'
			)
		)
	),
	'router' => array(
		'router_class' => 'Zend\Mvc\Router\Http\TranslatorAwareTreeRouteStack',
		'routes' => array()
	)
);
