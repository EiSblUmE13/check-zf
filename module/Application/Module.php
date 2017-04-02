<?php
/**
 * Zend Framework (http://framework.zend.com/)
 *
 * @link	  http://github.com/zendframework/ZendSkeletonApplication for the canonical source repository
 * @copyright Copyright (c) 2005-2015 Zend Technologies USA Inc. (http://www.zend.com)
 * @license   http://framework.zend.com/license/new-bsd New BSD License
 */

namespace Application;

if(!function_exists('apache_getenv')) {
	function apache_getenv($str) { return getenv($str) ? getenv($str) : 'development'; }
}

use Zend\Mvc\ModuleRouteListener;
use Zend\Mvc\MvcEvent;

use Zend\ModuleManager\ModuleManager;


use Zend\Session\Config\SessionConfig,
	Zend\Session\Config\StandardConfig,
	Zend\Session\SessionManager,
	Zend\Session\Storage\ArrayStorage as SessionStorage,
	Zend\Session\Container as Container,
	Zend\Di\Di;

use MongoClient;
use Zend\Session\SaveHandler\MongoDB;
use Zend\Session\SaveHandler\MongoDBOptions;
use Zend\Session\Validator\HttpUserAgent;
use Zend\Session\Validator\RemoteAddr;

use Zend\Mail\Transport\Sendmail as Mail;
use Zend\Mail\Message;

use Zend\Log\Logger as LogLogger,
	Zend\Log\Writer\Stream as LogStream,
	Zend\Log\Writer\FirePhp;

use Zend\Console\Request as ConsoleRequest;
use Zend\ModuleManager\Feature\ConfigProviderInterface;

class Module
{

	public function onBootstrap(MvcEvent $e)
	{


		$serviceManager = $e->getApplication()->getServiceManager();
		$eventManager = $e->getApplication()->getEventManager();


		$this->bootstrapSession($e);

		if(!($serviceManager->get('request') instanceof ConsoleRequest)) {
			if(apache_getenv('APP_ENV') != 'production') {
				require_once getcwd() . '/public/Htaccess.php';
			}

			$view     = $serviceManager->get('ZendViewView');
			$strategy = $serviceManager->get('ViewJsonStrategy');
			$view->getEventManager()->attach( $strategy, 100 );

			$serviceManager->get( 'viewhelpermanager' )->setFactory( 'objecthelper', function ($sm) use($e) {
				$viewHelper = new View\Helper\ObjectHelper ( $e );
				return $viewHelper;
			} );

			$eventManager->attach(\Zend\Mvc\MvcEvent::EVENT_ROUTE, array($this, 'onPreRoute'), 90);
			$eventManager->attach(\Zend\Mvc\MvcEvent::EVENT_DISPATCH, array($this, 'onDispatch'), 2);
			$eventManager->attach(\Zend\Mvc\MvcEvent::EVENT_DISPATCH_ERROR, array($this, 'onError'), 1);
			$eventManager->attach(\Zend\Mvc\MvcEvent::EVENT_RENDER_ERROR, array($this, 'onRenderError'), 1);
		}

		$moduleRouteListener = new ModuleRouteListener();
		$moduleRouteListener->attach($eventManager);
	}

	public function onDispatch( $e )
	{

		$servicemanager = $e->getApplication()->getServiceManager();
		$sharedManager = $e->getApplication()->getEventManager()->getSharedManager();

		$router = $servicemanager->get('router');
		$request = $servicemanager->get('request');

		if(!$matchedRoute = $router->match($request)) {
			$matchedRoute = $e->getRouteMatch();
		}
		$sharedManager->attach('Zend\Mvc\Controller\AbstractActionController','dispatch', function($e) use ($servicemanager, $matchedRoute) {
			$controller = $servicemanager->get('ControllerPluginManager')->getController();

			// attache session
			$session = new Container('default');
			$controller->session = $session;
			$controller->layout()->setVariable('session', $session);

			// attache ObjectManager
			$objectmanager = $servicemanager->get('doctrine.documentmanager.odm_default');
			$controller->dm = $objectmanager;
			$controller->layout()->setVariable('dm', $objectmanager);

			// attache AuthenticationService
			$authentication = $servicemanager->get('Zend\Authentication\AuthenticationService');
			$controller->auth = $authentication;
			$controller->layout()->setVariable('auth', $authentication);

			// attache lang
			$lang = $this->lostInTranslation($e);
			$controller->lang = $lang;
			$controller->layout()->setVariable('lang', $lang);

			// attach $rbac
			$plugin = $servicemanager->get('ControllerPluginManager')->get('MyAclPlugin');

			$rbac = $plugin->init($servicemanager);
			$controller->rbac = $rbac;
			$controller->layout()->setVariable('rbac', $rbac);

			$matchedRouteName = $matchedRoute ? $matchedRoute->getMatchedRouteName() : 'home';
			$matchedAction = $matchedRoute ? $matchedRoute->getParam('action', 'index') : 'index';
			if($matchedRoute->getParam('__NAMESPACE__')) {
				$matchedStr = implode("\\", array($matchedRoute->getParam('__NAMESPACE__'),$matchedRoute->getParam('controller')));
			}
			else
				$matchedStr = $matchedRoute->getParam('controller');

			$plugin->doAuthorization($rbac, $e, $matchedRouteName, $matchedAction, $matchedStr);

		}, 2);

	}

	public function onPreRoute( MvcEvent $e )
	{
		$sm = $e->getApplication()->getServiceManager();
		$router = $sm->get('router');

		$translator = $sm->get('translator');
		$translator->setFallbackLocale('de_DE');

		$router->setTranslator($translator);
	}

	public function onError( $e )
	{
		// lets logging
		$env = apache_getenv("APP_ENV");

		$serviceManager = $e->getApplication()->getServiceManager();
		$logger = $serviceManager->get('Logger');

		if ($env != 'production') {
			$writer = new FirePhp();
			$logger->addWriter($writer);

			LogLogger::registerErrorHandler($logger);
		} else {
			$mail = new Message();
			$mail->setFrom ('errors@work.site')->addTo('joerg.mueller@metacope.com')->setSubject('Errors: WORK - ' . $env );

			$writer = new \Zend\Log\Writer\Mail($mail);
			$writer->addFilter(LogLogger::DEBUG);
			$logger->addWriter($writer);
		}

		$sharedManager = $e->getApplication()->getEventManager()->getSharedManager();
		$sharedManager->attach('Zend\Mvc\Application', 'dispatch.debug', function($e) use($serviceManager) {
			if ($e->getParam('exception')) {
				$ex = $e->getParam('exception');
				do {
					$serviceManager->get('Logger')->log( sprintf( "%s:%d %s (%d) [%s]\n", $ex->getFile(), $ex->getLine(), $ex->getMessage(), $ex->getCode(), get_class( $ex ) ) );
				} while ( $ex = $ex->getPrevious() );
			}
		} );
	}

	public function onRenderError($e)
	{
		// must be an error
		if (!$e->isError()) {
			return;
		}

		// Check the accept headers for application/json
		$request = $e->getRequest();
		if (!$request instanceof HttpRequest) {
			return;
		}

		$headers = $request->getHeaders();
		if (!$headers->has('Accept')) {
			return;
		}

		$accept = $headers->get('Accept');
		$match  = $accept->match('application/json');
		if (!$match || $match->getTypeString() == '*/*') {
			// not application/json
			return;
		}

		// make debugging easier if we're using xdebug!
		ini_set('html_errors', 0);

		// if we have a JsonModel in the result, then do nothing
		$currentModel = $e->getResult();
		if ($currentModel instanceof JsonModel) {
			return;
		}

		// create a new JsonModel - use application/api-problem+json fields.
		$response = $e->getResponse();
		$model = new JsonModel(array(
			"httpStatus" => $response->getStatusCode(),
			"title" => $response->getReasonPhrase(),
		));

		// Find out what the error is
		$exception  = $currentModel->getVariable('exception');

		if ($currentModel instanceof ModelInterface && $currentModel->reason) {
			switch ($currentModel->reason) {
				case 'error-controller-cannot-dispatch':
					$model->detail = 'The requested controller was unable to dispatch the request.';
					break;
				case 'error-controller-not-found':
					$model->detail = 'The requested controller could not be mapped to an existing controller class.';
					break;
				case 'error-controller-invalid':
					$model->detail = 'The requested controller was not dispatchable.';
					break;
				case 'error-router-no-match':
					$model->detail = 'The requested URL could not be matched by routing.';
					break;
				default:
					$model->detail = $currentModel->message;
					break;
			}
		}

		if ($exception) {
			if ($exception->getCode()) {
				$e->getResponse()->setStatusCode($exception->getCode());
			}
			$model->detail = $exception->getMessage();

			// find the previous exceptions
			$messages = array();
			while ($exception = $exception->getPrevious()) {
				$messages[] = "* " . $exception->getMessage();
			};
			if (count($messages)) {
				$exceptionString = implode("n", $messages);
				$model->messages = $exceptionString;
			}
		}

		// set our new view model
		$model->setTerminal(true);
		$e->setResult($model);
		$e->setViewModel($model);
	}

	public function bootstrapSession(MvcEvent $e) {

		$sessionManager = $e->getApplication()->getServiceManager()->get('Zend\Session\SessionManager');
		$sessionManager->getValidatorChain()->attach('session.validate', array (
			new HttpUserAgent (),
			'isValid'
		) );
		$sessionManager->getValidatorChain()->attach('session.validate', array (
			new RemoteAddr (),
			'isValid'
		) );

		Container::setDefaultManager( $sessionManager );
		$session = new Container( 'default' );
		$session->offsetSet( 'init', true );
		$sessionManager->start();
	}

	public function lostInTranslation($e)
	{

		$env = apache_getenv('APP_ENV');
		$viewHelper = $e->getApplication()->getServiceManager()->get('ViewHelperManager');

		$session = new Container ( 'default' );
		$translator = $e->getApplication()->getServiceManager()->get('translator');

		$config = $e->getApplication()->getServiceManager()->get('Configuration');
		$localesConfig = $config['locales'];
		$localesList = $localesConfig['list'];

		$match = $e->getRouteMatch();
		$lang = $session->offsetExists('lang') ? $session->offsetGet('lang') : $localesConfig['defaults'];
		$lang = $match->getParam('lang', $lang);
		$locale = $localesList[$lang]['locale'];
		$currency = $localesList[$lang]['currency'];

		$translator->setLocale($locale);
		$viewHelper->get('currencyFormat')->setCurrencyCode('EUR')->setLocale($locale);
		$viewHelper->get('dateFormat')->setTimezone($localesList[$lang]['timezone'])->setLocale($locale);

		$session->offsetSet('locale', $locale );
		$session->offsetSet('lang', $lang);
		$session->offsetSet('APP_ENV', $env);

		return $lang;
	}

	public function getServiceConfig() {
		return array (
			'factories' => array (
				'Zend\Authentication\AuthenticationService' => function ($sm) {
					return $sm->get ( 'doctrine.authenticationservice.odm_default' );
				},
				'Logger' => function ($sm) {
					$logger = new \Zend\Log\Logger ();
					if (! file_exists ( getcwd () . '/data/logs' ))
						mkdir ( getcwd () . '/data/logs' );
					$writer = new \Zend\Log\Writer\Stream ( getcwd () . '/data/logs/' . date ( 'Y-m-d' ) . '-debug.log' );
					$writer->addFilter ( \Zend\Log\Logger::DEBUG );
					$logger->addWriter ( $writer );
					return $logger;
				},

				'Zend\Session\SessionManager' => function ($sm) {
					$config = $sm->get ( 'config' );
					if (isset ( $config ['session'] )) {
						$session = $config ['session'];

						$sessionConfig = null;
						if (isset ( $session ['config'] )) {
							$class = isset ( $session ['config'] ['class'] ) ? $session ['config'] ['class'] : 'Zend\Session\Config\SessionConfig';
							$options = isset ( $session ['config'] ['options'] ) ? $session ['config'] ['options'] : array ();
							$sessionConfig = new $class ();
							$sessionConfig->setOptions ( $options );
						}

						$sessionStorage = null;
						if (isset ( $session ['storage'] ) && strlen ( trim ( $session ['storage'] ) )) {
							$class = $session ['storage'];
							$sessionStorage = new $class ();
						}

						$sessionSaveHandler = null;
						if (isset ( $session ['save_handler'] ) && strlen ( trim ( $session ['save_handler'] ) )) {
							$sessionSaveHandler = $sm->get ( $session ['save_handler'] );
						}

						$sessionManager = new SessionManager ( $sessionConfig, $sessionStorage, $sessionSaveHandler );
					} else {
						$sessionManager = new SessionManager ();
					}
					Container::setDefaultManager ( $sessionManager );
					return $sessionManager;
				}
			)
		);
	}

	public function getConfig()
	{
		return include __DIR__ . '/config/module.config.php';
	}

	public function getAutoloaderConfig()
	{
		return array(
			'Zend\Loader\ClassMapAutoloader' => array (
				getcwd () . '/module/autoload_classmap.php'
			),
			'Zend\Loader\StandardAutoloader' => array(
				'namespaces' => array(
					__NAMESPACE__ => __DIR__ . '/src/' . __NAMESPACE__,
				),
			),
		);
	}

	public function getDiagnostics()
	{
		return array(
			'Cache directory exists' => function() {
				return file_exists('data/cache') && is_dir('data/cache');
			}
		);
	}
}



