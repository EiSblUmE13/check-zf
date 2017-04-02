<?php

/**
 * Zend Framework (http://framework.zend.com/)
 *
 * @link http://github.com/zendframework/ZendSkeletonApplication for the canonical source repository
 * @copyright Copyright (c) 2005-2014 Zend Technologies USA Inc. (http://www.zend.com)
 * @license http://framework.zend.com/license/new-bsd New BSD License
 */


return array(

	'controllers' => array(
		'invokables' => array(
			'Application\Controller\Index' => 'Application\Controller\IndexController',
			'Application\Controller\Page' => 'Application\Controller\PageController',
			'Application\Controller\Ajax' => 'Application\Controller\AjaxController',
			'Application\Controller\Document' => 'Application\Controller\DocumentController',
			'Application\Controller\Widget' => 'Application\Controller\WidgetController',
			'Application\Controller\Client' => 'Application\Controller\ClientController',
			'Application\Controller\User' => 'Application\Controller\UserController',
			'Application\Controller\Images' => 'Application\Controller\ImagesController',
			'Application\Controller\Asset' => 'Application\Controller\AssetController',
			'Application\Controller\Developer' => 'Application\Controller\DeveloperController'
		)
	),

	'router' => array(
		'routes' => array(
			'home' => array(
				'type' => 'Segment',
				'options' => array(
					'route' => '/[:lang[/:pageuri]]',
					'constraints' => array(
						'pageuri' => '[a-zA-Z0-9-_,\.\/]+',
						'lang' => '[a-z]{2}'
					),
					'defaults' => array(
						'__NAMESPACE__' => 'Application\Controller',
						'controller' => 'Index',
						'action' => 'view'
					)
				)
			),
			'asset' => array(
				'type' => 'Segment',
				'options' => array(
					'route' => '/index/asset/:action',
					'defaults' => array(
						'__NAMESPACE__' => 'Application\Controller',
						'controller' => 'Asset',
						'action' => 'index',
						'lang' => 'de'
					)
				)
			),
			'login' => array(
				'type' => 'Literal',
				'options' => array(
					'route' => '/login',
					'defaults' => array(
						'__NAMESPACE__' => 'Application\Controller',
						'controller' => 'Index',
						'action' => 'login',
						'lang' => 'de'
					)
				),
				'may_terminate' => true,
				'child_routes' => array(
					'wildcard' => array(
						'type' => 'Wildcard'
					)
				)
			),
			'logout' => array(
				'type' => 'Literal',
				'options' => array(
					'route' => '/logout',
					'defaults' => array(
						'__NAMESPACE__' => 'Application\Controller',
						'controller' => 'Index',
						'action' => 'logout',
						'lang' => 'de'
					)
				)
			),
			'page' => array(
				'type' => 'Segment',
				'options' => array(
					'route' => '/page[/:controller[/:action]]',
					'defaults' => array(
						'__NAMESPACE__' => 'Application\Controller',
						'controller' => 'Document',
						'action' => 'index',
						'lang' => 'de'
					)
				),
				'may_terminate' => true,
				'child_routes' => array(
					'wildcard' => array(
						'type' => 'Wildcard'
					)
				)
			)
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
	'view_manager' => array(
		'doctype' => 'HTML5',
		'not_found_template' => 'error/404',
		'exception_template' => 'error/index',
		'template_map' => array(
			'layout/layout' => __DIR__ . '/../view/layout/layout.phtml',
			'application/index/index' => __DIR__ . '/../view/application/index/index.phtml',
			'error/404' => __DIR__ . '/../view/error/404.phtml',
			'error/index' => __DIR__ . '/../view/error/index.phtml'
		),
		'template_path_stack' => array(
			__DIR__ . '/../view'
		),
		// This will be used as the default suffix for template scripts resolving, it defaults to 'phtml'.
		'default_template_suffix' => 'phtml',

		// Set the template name for the site's layout.
		//
		// By default, the MVC's default Rendering Strategy uses the
		// template name "layout/layout" for the site's layout.
		// Here, we tell it to use the "site/layout" template,
		// which we mapped via the TemplateMapResolver above.
		'layout' => 'layout/layout',

		// By default, the MVC registers an "exception strategy", which is
		// triggered when a requested action raises an exception; it creates
		// a custom view model that wraps the exception, and selects a
		// template. We'll set it to "error/index".
		//
		// Additionally, we'll tell it that we want to display an exception
		// stack trace; you'll likely want to disable this by default.
		'display_exceptions' => true,
		'exception_template' => 'error/index',

		// Another strategy the MVC registers by default is a "route not
		// found" strategy. Basically, this gets triggered if (a) no route
		// matches the current request, (b) the controller specified in the
		// route match cannot be found in the service locator, (c) the controller
		// specified in the route match does not implement the DispatchableInterface
		// interface, or (d) if a response from a controller sets the
		// response status to 404.
		//
		// The default template used in such situations is "error", just
		// like the exception strategy. Here, we tell it to use the "error/404"
		// template (which we mapped via the TemplateMapResolver, above).
		//
		// You can opt in to inject the reason for a 404 situation; see the
		// various `Application\:\:ERROR_*`_ constants for a list of values.
		// Additionally, a number of 404 situations derive from exceptions
		// raised during routing or dispatching. You can opt-in to display
		// these.
		'display_not_found_reason' => false,
		'not_found_template' => 'error/404',
		'strategies' => array(
		    'ViewJsonStrategy'
		),
	),
	// Placeholder for console routes
	'console' => array(
		'router' => array(
			'routes' => array()
		)
	),
	'view_helpers' => array(
		'invokables' => array(
			'ObjectHelper' => 'Application\View\Helper\ObjectHelper'
		)
	)
);
