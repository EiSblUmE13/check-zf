<?php
// {{{ Header
/**
 *
 * @author joerg.mueller
 * @version $Id:$
 */
// }}}

namespace Application\View\Helper;

use Zend\View\Helper\AbstractHelper;
use Zend\Mvc\MvcEvent;

class ObjectHelper extends AbstractHelper
{

	protected $routeMatch;
	protected $router;
	protected $e;
	protected $dm;
	public $acl;

	public function __construct( MvcEvent $e )
	{
		$this->dm = $e->getApplication()->getServiceManager()->get('doctrine.documentmanager.odm_default');
		$this->acl = $e->getApplication()->getServiceManager()->get('ControllerPluginManager')->get('MyAclPlugin');
		$this->acl = $this->acl->init();

		$this->e = $e;
		if($e->getRouteMatch())
			$this->routeMatch = $e->getRouteMatch();

	}

	public function __invoke()
	{
		return $this;
	}

	public function getDocumentManager()
	{
		return $this->dm;
	}

	public function getRouteInfo()
	{
		if ($this->routeMatch) {
			$controller = $this->routeMatch->getParams();
			return $controller;
		}
		else return 'unknow controller';
	}

	public function getRequest()
	{
		return $this->e->getApplication()->getServiceManager()->get('request');
	}

	public function getServiceLocator()
	{
		return $this->e->getApplication()->getServiceManager();
	}
}
