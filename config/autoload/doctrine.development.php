<?php

return array(
	'doctrine' => array(
		'connection' => array(
			'odm_default' => array(
				'server' => '127.0.0.1',
				'dbname' => 'prod_hhogzf2',
				'user' => 'hhogzf2User',
				'password' => 'hhogzf2User',
				'options' => array('socketTimeoutMS' => 200, 'connectTimeoutMS' => 22000)
			)
		),
		'configuration' => array(
			'odm_default' => array(
				'metadata_cache' => 'array',
				'driver' => 'odm_default',
				'generate_proxies' => true,
				'proxy_dir' => 'data/DoctrineMongoODMModule/Proxy',
				'proxy_namespace' => 'DoctrineMongoODMModule\Proxy',
				'generate_hydrators' => true,
				'hydrator_dir' => 'data/DoctrineMongoODMModule/Hydrator',
				'hydrator_namespace' => 'DoctrineMongoODMModule\Hydrator',
				'default_db' => 'prod_hhogzf2',
// 				'retryConnect' => 1,
				'filters' => array()
			)
		),
		'driver' => array(
			'odm_driver' => array(
				'class' => 'Doctrine\ODM\MongoDB\Mapping\Driver\AnnotationDriver',
				'cache' => 'filesystem',
				'paths' => array(
					getcwd() . '/module/Model'
				)
			),
			'odm_default' => array(
				'drivers' => array(
					'Model' => 'odm_driver'
				)
			)
		),
		'documentmanager' => array(
			'odm_default' => array(
				'connection' => 'odm_default',
				'configuration' => 'odm_default',
				'eventmanager' => 'odm_default'
			)
		),
		'authentication' => array(
			'odm_default' => array(
				'objectManager' => 'doctrine.documentmanager.odm_default',
				'identityClass' => '\Model\UserModel',
				'identityProperty' => 'nickname',
				'credentialProperty' => 'password',
				'credential_callable' => function (\Model\UserModel $user, $password)
				{
					if($user->getPassword() === $password && $user->getVisible() === 1) {
						return true;
					}
					return false;
				}
			)
		)
	)
);
