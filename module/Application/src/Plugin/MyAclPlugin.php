<?PHP
// {{{ Header
/**
 *
 * @author joerg.mueller
 * @version $Id:$
 */
// }}}

namespace Application\Plugin;

use Zend\Mvc\Controller\Plugin\AbstractPlugin,
	Zend\Session\Container as Container;

use Zend\Permissions\Rbac\Rbac, Zend\Permissions\Rbac\Role;

class MyAclPlugin extends AbstractPlugin
{

	protected $acl;

	public function init()
	{
		$this->rbac = new Rbac();
		$lastrole = false;

		$roles = array(
			 'guest' => array('guest')
			,'user' => array('user')
			,'employee' => array('employee','create','edit','remove','Application\Controller\News','Application\Controller\Document','Application\Controller\Media')
			,'editor' => array('editor','publish','unpublish','Application\Controller\User')
			,'chiefeditor' => array('chiefeditor')
			,'clientmanager' => array('clientmanager','Application\Controller\Client')
			,'clientleader' => array('clientleader','Application\Controller\Document')
			,'admin' => array('admin','Application\Controller\Developer')
			,'developer' => array('developer')
			,'urml' => array('urml')
		);

		$roles = new \ArrayIterator($roles);
		foreach($roles as $offset => $entry) {

			$role = new Role($offset);

			while($permission = array_shift($entry)) $role->addPermission($permission);

			if($lastrole) $role->addChild($lastrole);

			$this->rbac->addRole($role);
			$lastrole = $role;
		}

		return $this->rbac;
	}

	public function doAuthorization($rbac, $e, $matchedRouteName, $matchedAction, $matchedStr)
	{

		$sm = $e->getApplication()->getServiceManager();
		$session = new \Zend\Session\Container('default');
		$env = apache_getenv('APP_ENV') ? apache_getenv('APP_ENV') : 'development';
		$lang = $session->offsetExists('lang') ? $session->offsetGet('lang') : 'de';

		$router = $sm->get('router');
		$controller = $e->getTarget();
		$role = $session->offsetExists('role') ? $session->offsetGet('role') : 'guest';

		return true;
		if($rbac->getRole($role)->hasPermission($matchedAction) == false
			&& $rbac->getRole($role)->hasPermission($matchedRouteName) == false
			&& $rbac->getRole($role)->hasPermission($matchedStr) == false) {

			$url = $router->assemble(array('lang'=>$lang), array('name'=>'home')).'#login';

			$response = $sm->get('response');
			$response->setStatusCode(302);


			/*
			 * redirect to login
			*/
			$response->getHeaders()->addHeaderLine('Location', $url);
		}

	}
}