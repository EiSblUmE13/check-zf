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
use Zend\View\Model\ViewModel;
use Zend\View\Model\JsonModel;

use Zend\Mvc\MvcEvent,
	Application\Form\ClientEditForm;

use Model\Client,
	Model\Country;

class ClientController extends AbstractActionController
{

	public function indexAction()
	{
		$session = new Container('default');
		$this->lang = $this->params()->fromQuery('lang') ? $this->params()->fromQuery('lang') : ($this->lang ? $this->lang : $session->offsetGet('lang'));
		$config = $this->getServiceLocator()->get('Config');

		$viewParams = array(
			'lang' => $this->lang,
			'tld' => $this->tld,
			'dm' => $this->dm,
			'rbac' => $this->rbac
		);
		$this->layout()->setVariables($viewParams);

		$viewModel = new ViewModel($viewParams);
		$viewModel->setTerminal(true);
		return $viewModel;
	}

	public function editAction()
	{
		$session = new Container('default');
		$this->lang = $this->params()->fromQuery('lang') ? $this->params()->fromQuery('lang') : ($this->lang ? $this->lang : $session->offsetGet('lang'));
		$config = $this->getServiceLocator()->get('Config');

		$viewParams = array(
			'lang' => $this->lang,
			'tld' => $this->tld,
			'dm' => $this->dm,
			'acl' => $this->acl
		);
		$this->layout()->setVariables($viewParams);
		$translator = $this->getServiceLocator()->get("translator");

		$viewParams['form'] = $form = new ClientEditForm(array('translator'=>$translator,'dm'=>$this->dm));

		if($this->params()->fromRoute('id')) {
			$viewParams['client'] = $client = $this->dm->getRepository("Model\Client")->find($this->params()->fromRoute('id'));
		}

		$request = $this->getRequest();
		if($request->isPost()) {
			$form->setData($request->getPost());

			if($form->isValid()) {

				$data = $form->getData();

				$client->setName($data['name']);
				$client->setFullname($data['fullname']);
				$client->setDescription($data['description']);

				$client->setStylesheet($data['stylesheet']);
				$client->setLayout($data['layout']);

				if(!$country = $this->dm->getRepository("Model\Country")->find($data['country'])) {
					$country = $this->dm->getRepository("Model\Country")->findOneBy(array('iso'=>'DE'));
				}

				$client->setCountry($country);

				$this->dm->persist($client);
				$this->dm->flush();

				$content  = $translator->translate("Edit success");
				$content .= '<script>
							$("#helper").unbind("hidden.bs.modal");
							showModal("#helper");</script>';
				return new JsonModel(array(
						'client' => $client->toArray(),
						'#helperLabel' => array('content'=>$translator->translate("Tip"), 'type'=>'update'),
						'#helperContent' => array('content'=>$content, 'type'=>'update')
				));

			}
		}

		$viewModel = new ViewModel($viewParams);
		$viewModel->setTerminal(true);
		return $viewModel;
	}

	public function createAction()
	{

		$country = $this->dm->getRepository("Model\Country")->findOneBy(array('iso'=>'DE'));

		$data = array(
			'name' => 'demo-'.microtime(),
			'fullname' => 'demo-'.microtime(),
			'country' => $country,
			'stylesheet' => '.styleClass { color: \'#333\'; }'
		);
		$client = new Client($data);

		if($this->params()->fromRoute('id')) {
			$parent = $this->dm->getRepository("Model\Client")->find($this->params()->fromRoute('id'));
			$client->setParent($parent);
		}
		$client->setLayout();

		$this->dm->persist($client);
		$this->dm->flush();

		return new JsonModel();

	}

	public function removeAction()
	{
		if($this->params()->fromRoute('id')) {
			$client = $this->dm->getRepository("Model\Client")->find($this->params()->fromRoute('id'));
			$qb = $this->dm->createQueryBuilder("Model\Client");
			$qb->remove()
				->field('id', $this->params()->fromRoute('id'))
				->getQuery()
				->execute();

			$content  = $translator->translate("Edit success");
			$content .= '<script>
							$("#helper").unbind("hidden.bs.modal");
							showModal("#helper");</script>';
			return new JsonModel(array(
					'client' => $client->toArray(),
					'#helperLabel' => array('content'=>$translator->translate("Tip"), 'type'=>'update'),
					'#helperContent' => array('content'=>$content, 'type'=>'update')
			));
		}
	}
}
