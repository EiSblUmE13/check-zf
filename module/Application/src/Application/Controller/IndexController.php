<?php
// {{{ Header
/**
 *
 * @author joerg.mueller
 * @version $Id:$
 */
// }}}
namespace Application\Controller;

use Zend\Mvc\Controller\AbstractActionController;
use Zend\View\Model\ViewModel, Zend\View\Model\JsonModel;

use Zend\Session\Container as Container;

use Model\DocumentModel, Model\WidgetModel;

class IndexController extends AbstractActionController
{

	public function indexAction()
	{
		$session = new Container('default');
		$this->lang = $this->params()->fromQuery('lang') ? $this->params()->fromQuery('lang') : ($this->lang ? $this->lang : $session->offsetGet('lang'));
		$config = $this->getServiceLocator()->get('Config');


		$viewParams = array(
			'session' => $session,
			'lang' => $this->lang,
			'dm' => $this->dm,
			'serviceLocator' => $this->getServiceLocator(),
			'auth' => $this->auth,
			'rbac' => $this->rbac
		);


		$view = new ViewModel($viewParams);
		return $view;
	}

	public function viewAction()
	{
		$session = new Container('default');
		$this->lang = $this->params()->fromQuery('lang') ? $this->params()->fromQuery('lang') : ($this->lang ? $this->lang : $session->offsetGet('lang'));
		$config = $this->getServiceLocator()->get('Config');


		$viewParams = array(
			'session' => $session,
			'lang' => $this->lang,
			'dm' => $this->dm,
			'serviceLocator' => $this->getServiceLocator(),
			'auth' => $this->auth,
			'rbac' => $this->rbac,
			'pageuri' => $this->params()->fromRoute('pageuri', '/')
		);

		if(substr($viewParams['pageuri'], 0, 1) != '/') $viewParams['pageuri'] = '/' . $viewParams['pageuri'];
		if(substr($viewParams['pageuri'], -1) == '/' && strlen($viewParams['pageuri'])>1) $viewParams['pageuri'] = substr($viewParams['pageuri'], 0, (strlen($viewParams['pageuri'])-1));

		$criteria = array("path.{$this->lang}"=>$viewParams['pageuri'],'visible'=>1,'inlanguage'=>$this->lang);

		if($document = $this->dm->getRepository("Model\DocumentModel")->findOneBy($criteria))
		{
			//
			$headTitleHelper = $this->getServiceLocator()->get('viewHelperManager')->get('headTitle');
			$headScriptHelper = $this->getServiceLocator()->get('viewHelperManager')->get('headLink');

			$title = $document->getSheet()->getTitle($this->lang) ? $document->getSheet()->getTitle($this->lang) : ' ';
			$headTitleHelper->append($title);

			$viewParams['document'] = $document;
		}
		else
			$this->notFoundAction();

		$this->layout()->setVariables($viewParams);
		$view = new ViewModel($viewParams);

		return $view;
	}

	public function dashboardAction()
	{
		$session = new Container('default');
		$this->lang = $this->params()->fromQuery('lang') ? $this->params()->fromQuery('lang') : ($this->lang ? $this->lang : $session->offsetGet('lang'));
		$config = $this->getServiceLocator()->get('Config');


		$viewParams = array(
			'session' => $session,
			'lang' => $this->lang,
			'dm' => $this->dm,
			'sl' => $this->getServiceLocator(),
			'auth' => $this->auth,
			'rbac' => $this->rbac,
			'pageuri' => $this->params()->fromRoute('pageuri', '/')
		);

		if(substr($viewParams['pageuri'], 0, 1) != '/') $viewParams['pageuri'] = '/' . $viewParams['pageuri'];

		var_dump($viewParams['pageuri']);
		die("\n" . __FILE__.__LINE__ . "\n");

		$criteria = array(
			"path.{$this->lang}" => $viewParams['pageuri'],
			"visible" => 1
		);

		if($document = $this->dm->getRepository("Model\DocumentModel")->findOneBy($criteria)) {
			$this->viewAction();
			exit;
		}


		$view = new ViewModel($viewParams);
		$view->setTemplate('application/index/dashboard');
		return $view;
	}

	public function loginAction()
	{

		$session = new Container('default');
		$this->lang = $this->params()->fromQuery('lang') ? $this->params()->fromQuery('lang') : ($this->lang ? $this->lang : $session->offsetGet('lang'));
		$config = $this->getServiceLocator()->get('Config');

		$viewParams = array(
			'session' => $session,
			'lang' => $this->lang,
			'dm' => $this->dm,
			'rbac' => $this->rbac
		);
		$this->layout()->setVariables($viewParams);

		$translator = $this->getServiceLocator()->get("translator");
		$data = array_merge($this->params()->fromQuery(), $this->params()->fromPost(), $this->params()->fromRoute());
		$request = $this->getRequest();

		$view = new ViewModel($viewParams);
		$view->setTemplate('application/index/login/full');

		if ($request->isPost() || (isset($data['u']) && isset($data['p']))) {
			$login = isset($data['u']) ? $data['u'] : (isset($data['login']) ? $data['login'] : false);
			$password = isset($data['p']) ? $data['p'] : (isset($data['password']) ? $data['password'] : false);
			if (! $this->auth->hasIdentity() && $login && $password) {
				$adapter = $this->auth->getAdapter();

				if (false !== strpos($login, '@')) {
					$adapter->getOptions()->setIdentityProperty('email');
				}
				else {
					$adapter->getOptions()->setIdentityProperty('nickname');
				}

				$adapter->setIdentityValue($login);
				$adapter->setCredentialValue(md5($password));
				$authResult = $this->auth->authenticate();

				if ($authResult->isValid()) {

					$identity = $authResult->getIdentity();
					$identity->setLastlogin(new \DateTime());

					$session = new Container('default');
					$session->offsetSet('role', $identity->getRole());

					$this->dm->flush($identity);

					$this->auth->getStorage()->write($identity);

					if (isset($data['remember']) && $data['remember'] == 'remember') {

						$sessionManager = new \Zend\Session\SessionManager();
						$sessionManager->rememberMe();
					}

					if($request->isXmlHttpRequest()) {

						$view->setTerminal(true);

						$content = sprintf($translator->translate('Welcome %s back'), $identity->getNickname());

						$content .= "<script>showModal('#helper');$('#helper').on('hidden.bs.modal', function(){
										window.location.reload()
									})</script>";

						return new JsonModel(array(
							'#helperLabel' => array('content'=>$translator->translate('Login success'),'type'=>'update'),
							'#helperContent' => array('content'=>$content,'type'=>'update')
						));
					}
					else $this->redirect()->toUrl('/');
				}
			}
			$identity = $this->identity();


			if($request->isXmlHttpRequest()) {
				$view->setTerminal(true);
				$content = sprintf($translator->translate('Welcome %s you are currently loggedin.'), $identity->getNickname());

				$content .= "<script>showModal('#helper');$('#helper').on('hidden.bs.modal', function(){
								window.location.href='/'
							})</script>";

				return new JsonModel(array(
					'#helperLabel' => array('content'=>$translator->translate('Login success'),'type'=>'update'),
					'#helperContent' => array('content'=>$content,'type'=>'update')
				));
			}
			else $this->redirect()->toUrl('/');
		}
		return $view;
	}

	public function logoutAction()
	{
		$session = new Container('default');
		$this->lang = $this->params()->fromQuery('lang') ? $this->params()->fromQuery('lang') : ($this->lang ? $this->lang : $session->offsetGet('lang'));
		$config = $this->getServiceLocator()->get('Config');

		$viewParams = array(
			'session' => $session,
			'lang' => $this->lang,
			'rbac' => $this->rbac,
			'auth' => $this->auth
		);
		$this->layout()->setVariables($viewParams);

		if($this->auth->hasIdentity()) {
			$identity = $this->auth->getIdentity();
			$this->auth->clearIdentity();
		}
		$sessionManager = new \Zend\Session\SessionManager();
		$sessionManager->forgetMe();

		$session->offsetSet('role', 'guest');
		$this->redirect()->toUrl('/');
	}

}
