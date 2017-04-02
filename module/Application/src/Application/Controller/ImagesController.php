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

use Zend\Mvc\MvcEvent, Zend\Session\Container as Container;


use Model\Image,
	Model\ImageAttributes;

class ImagesController extends AbstractActionController
{

	public function indexAction()
	{
		$session = new Container('default');
		$this->lang = $this->params()->fromQuery('lang') ? $this->params()->fromQuery('lang') : ($this->lang ? $this->lang : $session->offsetGet('lang'));
		$this->config = $config = $this->getServiceLocator()->get('Config');

		$viewParams = array(
			'lang' => $this->lang,
			'dm' => $this->dm,
			'rbac' => $this->rbac,
			'session' => $session,
			'config' => $config
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
		$this->config = $config = $this->getServiceLocator()->get('Config');

		$viewParams = array(
			'lang' => $this->lang,
			'dm' => $this->dm,
			'rbac' => $this->rbac,
			'session' => $session,
			'config' => $config
		);
		$this->layout()->setVariables($viewParams);



		$view = new ViewModel($viewParams);
		$view->setTerminal(true);
		return $view;
	}

	public function dropzoneAction()
	{
		$session = new Container('default');
		$this->lang = $this->params()->fromQuery('lang') ? $this->params()->fromQuery('lang') : ($this->lang ? $this->lang : $session->offsetGet('lang'));
		$this->config = $config = $this->getServiceLocator()->get('Config');

		$viewParams = array(
			'lang' => $this->lang,
			'dm' => $this->dm,
			'rbac' => $this->rbac,
			'session' => $session,
			'config' => $config
		);
		$this->layout()->setVariables($viewParams);
		$viewParams['image'] = $image = $this->dm->getRepository("Model\Image")->find($this->params()->fromRoute('id'));


		$viewModel = new ViewModel($viewParams);
		$viewModel->setTerminal(true);
		return $viewModel;
	}

	public function uploadAction()
	{
		$request = $this->getRequest();
		$lang = $this->lang;
		$dm = $this->dm;

		$tempFile = $_FILES['file']['tmp_name'];
		$targetPath = getcwd() . '/data/uploads/';
		$targetFile =  $targetPath. $_FILES['file']['name'];
		move_uploaded_file($tempFile,$targetFile);

		$image = new Image();
		$image->setName($_FILES['file']['name']);
		$image->setFile($targetFile);

		if($this->auth && $this->auth->hasIdentity())
			$image->setOwner($this->identity());
		$image->setFolder($this->params()->fromPost('folder'));

		$attributes = new ImageAttributes();

		$image->setAttributes($attributes);

		$dm->persist($image);
		$dm->flush();

		if($this->params()->fromQuery('widget')) {
			$id = $this->params()->fromQuery('widget');
			$widget_repository = $dm->getRepository("Model\WidgetModel");
			$widget = $widget_repository->createQueryBuilder("Model\WidgetModel")
				->field('images')->set($image)
				->field("id")->equals($id)
				->getQuery()
				->execute();
		}

		print $image->getId();
		exit;
	}

	public function attributesAction()
	{
		$session = new Container('default');
		$this->lang = $this->params()->fromQuery('lang') ? $this->params()->fromQuery('lang') : ($this->lang ? $this->lang : $session->offsetGet('lang'));
		$this->config = $config = $this->getServiceLocator()->get('Config');

		$requestparams = array_merge($this->params()->fromQuery(),$this->params()->fromPost(),$this->params()->fromRoute());
		$requestparams = array_merge($requestparams, $requestparams['attributes']);

		$request = $this->getRequest();
		$translator = $this->getServiceLocator()->get("translator");

		if($request->isPost()) {
			$post = $request->getPost();
			$image = $this->dm->getRepository("Model\Image")->find($post->id);

			$attributes = new ImageAttributes($image->getAttributes()->toArray());

			$attributes->setTitle($requestparams['title'], $this->lang)
						->setAlt($requestparams['alt'], $this->lang)
						->setCopyright($requestparams['copyright'])
						->setTag(isset($requestparams['tag']) && strlen($requestparams['tag']) ? explode(',',$requestparams['tag']) : array());


			$owner = $requestparams['owner'];
			if(isset($owner) && !empty($owner)) {
				$owner = $this->dm->getRepository("Model\UserModel")->findOneByToken($requestparams['owner']);
				$image->setOwner($owner);
			}
			else {
			    if($this->auth->hasIdentity())
			        $image->setOwner($this->identity());
			}

			if(!empty($requestparams['expire'])) {
				$attributes->setExpire(new \DateTime($requestparams['expire']));
			}
			else {
				$attributes->setExpire(null);
			}
			$image->setAttributes($attributes);

			$this->dm->persist($image);
			$this->dm->flush();

			$content  = $translator->translate("Edit success");
			$content .= '<script>
							$("#helper").unbind("hidden.bs.modal");
							showModal("#helper");</script>';
			return new JsonModel(array(
					'#helperLabel' => array('content'=>$translator->translate("Tip"), 'type'=>'update'),
					'#helperContent' => array('content'=>$content, 'type'=>'update')
			));

		}
		$content  = $translator->translate("Edit failed");
		$content .= '<script>
						$("#helper").unbind("hidden.bs.modal");
						showModal("#helper");</script>';
		return new JsonModel(array(
				'#helperLabel' => array('content'=>$translator->translate("Tip"), 'type'=>'update'),
				'#helperContent' => array('content'=>$content, 'type'=>'update')
		));

	}

	public function imageAction()
	{
		$session = new Container('default');
		$this->lang = $this->params()->fromQuery('lang') ? $this->params()->fromQuery('lang') : ($this->lang ? $this->lang : $session->offsetGet('lang'));
		$config = $this->getServiceLocator()->get('Config');

		$viewParams = array(
			'lang' => $this->lang,
			'dm' => $this->dm,
			'rbac' => $this->rbac,
			'session' => $session
		);
		$this->layout()->setVariables($viewParams);

		return new ViewModel($viewParams);

	}

	public function imageeditAction()
	{
		$session = new Container('default');
		$this->lang = $this->params()->fromQuery('lang') ? $this->params()->fromQuery('lang') : ($this->lang ? $this->lang : $session->offsetGet('lang'));
		$config = $this->getServiceLocator()->get('Config');

		$viewParams = array(
			'lang' => $this->lang,
			'dm' => $this->dm,
			'rbac' => $this->rbac,
			'session' => $session
		);
		$this->layout()->setVariables($viewParams);
		$viewParams['image'] = $image = $this->dm->getRepository("Model\Image")->find($this->params()->fromRoute('id'));


		$viewModel = new ViewModel($viewParams);

		if(strpos($_SERVER['HTTP_HOST'], 'dbkt.site') !== false) {
// 			$viewModel->setTemplate('application/images/imageedit-local');
		}

		$viewModel->setTerminal(true);
		return $viewModel;
	}

	public function metaAction()
	{

		$session = new Container('default');
		$this->lang = $this->params()->fromQuery('lang') ? $this->params()->fromQuery('lang') : ($this->lang ? $this->lang : $session->offsetGet('lang'));
		$config = $this->getServiceLocator()->get('Config');

		$viewParams = array(
			'lang' => $this->lang,
			'dm' => $this->dm,
			'rbac' => $this->rbac,
			'session' => $session,
			'config' => $config
		);
		$this->layout()->setVariables($viewParams);
		$viewParams['image'] = $image = $this->dm->getRepository("Model\Image")->find($this->params()->fromRoute('id'));

		$viewModel = new ViewModel($viewParams);
		$viewModel->setTerminal(true);
		return $viewModel;
	}

	public function cropAction() {

		$image = $this->dm->getRepository("Model\Image")->find($this->params()->fromQuery('i'));
		$_src = file_get_contents('http://' . $_SERVER['HTTP_HOST'] . $this->params()->fromQuery('s'));

		$img_r = imagecreatefromstring($_src);
		$dst_r = imagecreatetruecolor($this->params()->fromQuery('w'), $this->params()->fromQuery('h'));
		imagecopyresampled($dst_r, $img_r, 0, 0, $this->params()->fromQuery('x'), $this->params()->fromQuery('y'), $this->params()->fromQuery('w'), $this->params()->fromQuery('h'), $this->params()->fromQuery('w'), $this->params()->fromQuery('h'));

		ob_start();
		imagejpeg($dst_r, null, 90);
		$img = ob_get_clean();

		imagedestroy($dst_r);
		imagedestroy($img_r);

		$file = getcwd() . '/data/cache/'.$image->getName().'-Copy';
		file_put_contents($file, $img);

		$n = new Image();
		$n->setName($image->getName().'-Copy');
		$n->setFile($file);

		$attributes = new ImageAttributes();
		$n->setAttributes($image->getAttributes());

		$this->dm->persist($n);
		$this->dm->flush();

		return new JsonModel();
	}

	public function removeAction()
	{

		$ids = $this->params()->fromQuery('ids') ? $this->params()->fromQuery('ids') : ($this->params()->fromQuery('id') ? array($this->params()->fromQuery('id')) : ($this->params()->fromRoute('id') ? array($this->params()->fromRoute('id')) : array()));

		$qb = $this->dm->createQueryBuilder("Model\Image")
				->remove()
				->field('id')->in($ids)
				->getQuery()
				->execute();

		return new JsonModel();
	}

	public function seekerAction()
	{
		$session = new Container('default');
		$this->lang = $this->params()->fromQuery('lang') ? $this->params()->fromQuery('lang') : ($this->lang ? $this->lang : $session->offsetGet('lang'));
		$config = $this->getServiceLocator()->get('Config');

		$viewParams = array(
			'lang' => $this->lang,
			'dm' => $this->dm,
			't' => $this->params()->fromQuery('t'),
			'rbac' => $this->rbac,
			'session' => $session
		);
		$this->layout()->setVariables($viewParams);

		$view = new ViewModel($viewParams);
		$view->setTerminal(true);

		return $view;
	}

	public function isbackgroundAction()
	{
		if($token = $this->params()->fromRoute('token')) {
			$image = $this->dm->getRepository("Model\Image")->findOneBy(array('token'=>$token));
			$image->setIsbackground( ($image->getIsbackground() == 1 ? 0 : 1) );
			$this->dm->flush($image);

			return new JsonModel(array('success'=>true,'isbackground'=>$image->getIsbackground()));
		}
		return new JsonModel(array('success'=>false,'message'=>'token not found'));
	}

}
