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

use  Model\ClientModel
	, Model\UserModel
	, Model\UserSheetModel
	, Model\Image
	, Model\DocumentModel
	, Model\DocumentSheetModel;

class DeveloperController extends AbstractActionController
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
		$this->translator = $translator = $this->getServiceLocator()->get('translator');


		return new ViewModel($viewParams);
	}

	public function uAction()
	{
		$session = new Container('default');
		$this->lang = $this->params()->fromQuery('lang') ? $this->params()->fromQuery('lang') : ($this->lang ? $this->lang : $session->offsetGet('lang'));
		$config = $this->getServiceLocator()->get('Config');

		$viewParams = array(
			'lang' => $this->lang,
			'dm' => $this->dm,
		);
		$this->translator = $translator = $this->getServiceLocator()->get('translator');

		$client = $this->dm->getRepository("Model\ClientModel")->findOneBy(array('token'=>$this->params()->fromQuery('t')));

		$n = $this->params()->fromQuery('n');
		$e = $this->params()->fromQuery('e');
		$p = $this->params()->fromQuery('p');
		$r = $this->params()->fromQuery('r');
		$g = $this->params()->fromQuery('g', 'mr');

		$s = new UserSheetModel();
		$s->setGender($g);
		$s->setCity($this->params()->fromQuery('city','Frankfurt am Main'));
		$s->setStreetnr($this->params()->fromQuery('streetnr','Schäfergasse 33'));
		$s->setZipcode($this->params()->fromQuery('zipcode','60313'));

		$u = new UserModel();
		$u->setNickname($n);
		$u->setEmail($e);
		$u->setPassword(md5($p));
		$u->setRole($r);
		$u->setVisible(1);

		$u->setClient($client);
		$u->setSheet($s);

		$this->dm->persist($u);
		$this->dm->flush();

		return new JsonModel($u->toArray());

	}

	public function cAction()
	{
		$session = new Container('default');
		$this->lang = $this->params()->fromQuery('lang') ? $this->params()->fromQuery('lang') : ($this->lang ? $this->lang : $session->offsetGet('lang'));
		$config = $this->getServiceLocator()->get('Config');

		$viewParams = array(
			'lang' => $this->lang,
			'dm' => $this->dm,
		);
		$this->translator = $translator = $this->getServiceLocator()->get('translator');


		$s = $this->params()->fromQuery('s');
		$c = new ClientModel();
		$c->setShortname($s);

		$this->dm->persist($c);
		$this->dm->flush();

		return new JsonModel($c->toArray());
	}

	public function uitAction()
	{
		$qb = $this->dm->createQueryBuilder('Mode\Image');
		$images = $qb->distinct('folder')->getQuery()->execute();

		$i=0;
		foreach($images as $image) {
			$image->setToken(\Model\UserModel::newPassword(16,null,0));
			$this->dm->persist($image);

			if( $i%6==0 ) $this->dm->flush();

			$i += 1;
		}
		$this->dm->flush();

		return new JsonModel(array('c'=>$images->count));
	}

	public function uuAction()
	{
		$config = $this->getServiceLocator()->get('Config');

		$parent = $this->dm->getRepository("Model\DocumentModel")->findOneBy(array('path.de'=>$this->params()->fromQuery('path','/')));

		$qb = $this->dm->createQueryBuilder('Model\DocumentModel');
		$qb->field('parent')->references($parent);
		$documents = $qb->getQuery()->execute();

		$return = [];
		while($document = $documents->getNext()) {
			foreach($config['locales']['list'] as $lang => $data) {
				$fallbackurllang = $config['locales']['list'][$lang]['fallbackurllang'];
				$sheet = $document->getSheet();
				$path = $document->generateUrl($fallbackurllang, null, $sheet);
				$path = preg_replace("/[\/]+/s", '/', $path);
				$document->setPath($path, $lang);
				$this->dm->flush($document);
				$return[] = $document->getPath('de');
			}
		}

		return new JsonModel($return);
	}

	public function ulAction()
	{
		$session = new Container('default');
		$this->lang = $this->params()->fromQuery('lang') ? $this->params()->fromQuery('lang') : ($this->lang ? $this->lang : $session->offsetGet('lang'));
		$config = $this->getServiceLocator()->get('Config');

		$viewParams = array(
			'lang' => $this->lang,
			'dm' => $this->dm,
		);
		$this->translator = $translator = $this->getServiceLocator()->get('translator');

		$return = array();
		$template = file_get_contents(getcwd().'/data/templates/defaults/default.tpl');
		$entrypath = $this->params()->fromQuery('entry', '/');
		if($parent = $this->dm->getRepository("Model\DocumentModel")->findOneBy(array("path.{$this->lang}"=>$entrypath))) {

			foreach($config['locales']['list'] as $lang => $data) {
				$parent->setLayout($template, $lang);
			}
			$this->dm->flush($parent);

			$childlist = $this->dm->createQueryBuilder('Model\DocumentModel')
								->field('parent')->references($parent)
								->getQuery()
								->execute();

			foreach($childlist as $document) {

				foreach($config['locales']['list'] as $lang => $data) {
					$document->setLayout($template, $lang);
				}
				$this->dm->flush($document);

				$return[] = $document->getStructname($this->lang);
			}
		}

		return new JsonModel($return);
	}

	public function importDocumentStructAction()
	{
		$config = $this->getServiceLocator()->get('Config');
		$_db_online = new \MongoClient( "mongodb://prod_hhog:prod_hhog@213.239.207.14:27020,78.46.106.73:27018,46.4.90.117:27019", array('replicaSet' => 'dxReplica'));
		$_adapter_prod = $_db_online->prod_hhog;

		$lang = $this->params()->fromQuery('lang', 'en');
		$entry_path = $this->params()->fromQuery('entry');
		$client = $this->dm->getRepository("Model\ClientModel")->findOneBy(array('shortname'=>'hhog'));
		$dparent = $this->dm->getRepository("Model\DocumentModel")->findOneBy(array("token"=>$this->params()->fromQuery('parent')));

		$template = file_get_contents(getcwd().'/data/templates/defaults/default.tpl');

		if($entry_path) {

			$criteria = array(
				"path.{$lang}" => "{$entry_path}"
			);

			if($parent = $_adapter_prod->document->findOne($criteria)) {

				$children = $_adapter_prod->document->find(array('parent_id'=>(string)$parent['_id']));
				$children = iterator_to_array($children);

				$return=[];
				while ($child = array_shift($children)) {

					$document = new \Model\DocumentModel();
					$sheet = new \Model\DocumentSheetModel();


					foreach($config['locales']['list'] as $lang => $data) {
						$document->setLayout($template, $lang);
					}
					$document->setInlanguage($child['inlanguage']);

					foreach($child['dname'] as $k => $v) {
						$document->setStructname($v, $k);
					}
					$document->setStructname($child['dname']['en'], 'de');

					$document->setSort(1);
					$document->setVisible(1);
					$document->setIsdocument(1);
					$document->setClient($client);
					$document->setParent($dparent);

// 					$document->setGeoreverse('Frankfurt am Main');

					foreach($child['title'] as $k => $v) {
						$sheet->setTitle($v, $k);
					}
					$sheet->setTitle($child['title']['en'], 'de');

					$document->setSheet($sheet);

					$this->dm->persist($document);
					$this->dm->flush();
				}
				return new JsonModel($return);
			}
			else return new JsonModel(array('status'=>'error','message'=>'entry document not found'));
		}
		else
			return new JsonModel(array('status'=>'error','message'=>'no entry point'));
	}


	/**
	 * updateImageRandomPoint
	 */
	public function uirpAction()
	{
		$files = $this->dm->getRepository("Model\Image")->findAll();
		$i=0;
		foreach($files as $file) {
			$file->setRandompoint(array(rand(-180, 180),rand(-180, 180)));

			$this->dm->persist($file);
			if($i>0 && $i%7==0) {
				$this->dm->flush();
			}
		}
		$this->dm->flush();

		return new JsonModel();
	}

}