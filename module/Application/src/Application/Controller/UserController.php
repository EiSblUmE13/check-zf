<?PHP
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

use Zend\Mvc\MvcEvent;
use Application\Form\UserEditForm;

use Model\UserModel,
	Model\UserSheetModel;


use Zend\Permissions\Rbac\Rbac, Zend\Permissions\Rbac\Role;

class UserController extends AbstractActionController
{
	public function indexAction()
	{
		$session = new Container('default');
		$this->lang = $this->params()->fromQuery('lang') ? $this->params()->fromQuery('lang') : ($this->lang ? $this->lang : $session->offsetGet('lang'));
		$config = $this->getServiceLocator()->get('Config');

		$viewParams = array(
			'lang' => $this->lang,
			'dm' => $this->dm,
		);
		$this->layout()->setVariables($viewParams);

		$viewModel = new ViewModel($viewParams);
		$viewModel->setTerminal(true);
		return $viewModel;
	}

	public function setAction()
	{
		$session = new Container('default');
		$this->lang = $this->params()->fromQuery('lang') ? $this->params()->fromQuery('lang') : ($this->lang ? $this->lang : $session->offsetGet('lang'));
		$config = $this->getServiceLocator()->get('Config');

		$viewParams = array(
			'lang' => $this->lang,
			'dm' => $this->dm,
			'auth' => $this->auth
		);
		$this->layout()->setVariables($viewParams);
		$this->translator = $translator = $this->getServiceLocator()->get('translator');

		/*
		 * if token by query or by post ... edit
		 * if id per route new document
		 */
		$requestparams = array_merge($this->params()->fromQuery(),$this->params()->fromPost());

		if(isset($requestparams['token']) && strlen(trim($requestparams['token']))) {
			$user = $this->dm->getRepository("Model\UserModel")->findOneBy(array('token'=>$requestparams['token']));
		}
		else {

			if($this->params()->fromRoute('id')) {
				$parent = $this->dm->getRepository("Model\UserModel")->find($this->params()->fromRoute('id'));
			}

			/*
			 * userSheet
			 */
			$usersheet = new UserSheetModel();
			$usersheet->setFirstname('demo');
			$usersheet->setName('user');
			$usersheet->setGender('mr');
			$usersheet->setCity('Heilbad Heiligenstadt');
			$usersheet->setZipcode('37308');
			$usersheet->setStreetnr('Marktplatz 1');

			/*
			 * user
			 */
			$user = new UserModel();
			$user->setNickname('demoNickname'.time());
			$user->setPassword($user->getNickname());
			$user->setEmail($user->getNickname().'@demouser.com');

			$user->setSheet($usersheet);


			if($parent) {
				$user->setParent($parent);
			}

			if(isset($requestparams['create'])) {
				$this->dm->persist($user);
				$this->dm->flush();
				return new JsonModel($user->toArray());
			}

		}

		$request = $this->getRequest();
		if($request->isPost() == true) {
			$fromPost = $this->params()->fromPost();

			$fromPost['groups'] = isset($fromPost['groups']) && is_array($fromPost['groups'])
								? $fromPost['groups']
								: array();

			$sheet = $user->getSheet();
			$sheet->setFirstname($fromPost['firstname']);
			$sheet->setName($fromPost['name']);
			$sheet->setZipcode($fromPost['zipcode']);
			$sheet->setCity($fromPost['city']);
			$sheet->setStreetnr($fromPost['streetnr']);
			$sheet->setGender($fromPost['gender']);
			$sheet->setTeaminfo($fromPost['teaminfo']);

			if(isset($fromPost['password']) && strlen(trim($fromPost['password']))) {
				$user->setPassword(md5($fromPost['password']));
			}
			$user->setEmail($fromPost['email']);
			$user->setNickname($fromPost['nickname']);
			$user->setRole($fromPost['role']);
			$user->setGroups($fromPost['groups']);

			$user->setSheet($sheet);

			$this->dm->flush($user);

			$content  = $translator->translate("Update Success");
			$content .= ' <script>showModal("#helper");</script>';
			return new JsonModel(array(
				'#helperLabel' => array('content'=>$translator->translate("Info"), 'type'=>'update'),
				'#helperContent' => array('content'=>$content, 'type'=>'update'),
				'user' => $user->toArray()
			));
		}

		$viewParams['user'] = $user;
		$this->layout()->setVariables($viewParams);

		$view = new ViewModel($viewParams);
		$view->setTerminal(true);
		return $view;

	}

	public function removeAction()
	{
		$translator = $this->getServiceLocator()->get("translator");

		if($this->params()->fromRoute('id')) {
			$user = $this->dm->getRepository("Model\UserModel")->find($this->params()->fromRoute('id'));
			$qb = $this->dm->createQueryBuilder("Model\UserModel");
			$qb->remove()->field('id')->equals($user->getId())->getQuery()->execute();
		}

		$content  = $translator->translate("Remove success");
		$content .= '<script>
							$("#helper").unbind("hidden.bs.modal");
							showModal("#helper");</script>';
		return new JsonModel(array(
				'user' => $user->toArray(),
				'#helperLabel' => array('content'=>$translator->translate("Tip"), 'type'=>'update'),
				'#helperContent' => array('content'=>$content, 'type'=>'update')
		));
	}

	public function moveIntoAction()
	{
		$translator = $this->getServiceLocator()->get("translator");

		$content  = $translator->translate("Move Into success");
		$content .= '<script>
							$("#helper").unbind("hidden.bs.modal");
							showModal("#helper");</script>';
		return new JsonModel(array(
				'#helperLabel' => array('content'=>$translator->translate("Tip"), 'type'=>'update'),
				'#helperContent' => array('content'=>$content, 'type'=>'update')
		));
	}

	public function visibleAction()
	{
		$translator = $this->getServiceLocator()->get("translator");

		if($this->params()->fromRoute('id')) {
			$user = $this->dm->getRepository("Model\UserModel")->find($this->params()->fromRoute('id'));
			$user->setVisible($user->getVisible() == 1 ? 0 : 1);
			$this->dm->flush($user);
		}

		$content  = $translator->translate("(In)Visible success");
		$content .= '<script>
							$("#helper").unbind("hidden.bs.modal");
							showModal("#helper");</script>';
		return new JsonModel(array(
					'user' => $user->toArray(),
					'#helperLabel' => array('content'=>$translator->translate("Tip"), 'type'=>'update'),
					'#helperContent' => array('content'=>$content, 'type'=>'update')
				));
	}
}
