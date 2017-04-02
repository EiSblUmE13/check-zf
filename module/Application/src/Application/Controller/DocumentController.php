<?PHP
// {{{ Header
/**
 *
 * @author joerg.mueller
 * @version $Id:$
 */
// }}}
namespace Application\Controller;
use Zend\Mvc\Controller\AbstractActionController,
	Zend\Session\Container,
	Zend\Mvc\MvcEvent;

use Zend\View\Model\ViewModel;
use Zend\View\Model\JsonModel;

use Model\DocumentModel,
	Model\DocumentSheetModel,
	Model\Coordinates;


use Application\Form\DocumentForm,
	Application\Form\DocumentSheetForm;

class DocumentController extends AbstractActionController
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
			'sl' => $this->getServiceLocator(),
			'auth' => $this->auth,
			'rbac' => $this->rbac
		);
		$this->layout()->setVariables($viewParams);

		$view = new ViewModel($viewParams);
		$view->setTerminal(true);

		return $view;
	}

	public function setAction()
	{
		$session = new Container('default');
		$this->lang = $this->params()->fromQuery('lang') ? $this->params()->fromQuery('lang') : ($this->lang ? $this->lang : $session->offsetGet('lang'));
		$this->config = $config = $this->getServiceLocator()->get('Config');
		$fallbackurllang = $this->config['locales']['list'][$this->lang]['fallbackurllang'];

		$viewParams = array(
			'lang' => $this->lang,
			'dm' => $this->dm,
			'config' => $config,
			'rbac' => $this->rbac,
			'session' => $session
		);
		$this->layout()->setVariables($viewParams);
		$this->translator = $translator = $this->getServiceLocator()->get('translator');


		$viewParams['documentform'] = new DocumentForm('DocumentModel', array('c'=>$this));
		$viewParams['sheetform'] = new DocumentSheetForm('DocumentSheetModel', array('c'=>$this));

		/*
		 * if token by query or by post ... edit
		 * if id per route new document with parent
		 */
		$requestparams = array_merge($this->params()->fromQuery(),$this->params()->fromPost());
		if($this->params()->fromRoute('token')) {
			$requestparams['token'] = $this->params()->fromRoute('token');
		}

		if(isset($requestparams['token']) && strlen(trim($requestparams['token']))) {
			$document = $this->dm->getRepository("Model\DocumentModel")->findOneBy(array('token'=>$requestparams['token']));
			$cl = $document->getChildList(array('lang'=>$this->lang),$this->dm);
		}
		else {

			$parent = false;
			if($this->params()->fromRoute('id')) {
				$parent = $this->dm->getRepository("Model\DocumentModel")->find($this->params()->fromRoute('id'));
				$cl = $parent->getChildList(array('lang'=>$this->lang),$this->dm);
				$cc = $cl->count()+1;
			}
			else {
				$qb = $this->dm->createQueryBuilder("Model\DocumentModel");
				$qb->addOr($qb->expr()->field('parent')->exists(false));
				$qb->addOr($qb->expr()->field('parent')->equals(null));
				$d=$qb->getQuery()->execute();
				$cc = $d->count()+1;
			}

			$documentsheet 	= new DocumentSheetModel();
			$documentsheet->setTitle('Demotitle'.$cc);
			$documentsheet->setTitle('Demotitle'.$cc, 'en');
			$documentsheet->setKeywords([]);
			$documentsheet->setKeywords([], 'en');

			$document = new DocumentModel();
			$document->setSheet($documentsheet);

			$document->setSort($cc);
			$document->setStructname('Demotitle'.$cc, 'de');
			$document->setStructname('Demotitle'.$cc, 'en');
			$document->setPath($document->generateUrl('de'), 'de');
			$document->setPath($document->generateUrl('en'), 'en');

			$document->setGeoreverse('Frankfurt am Main');

			$client = false;
			$owner = false;


			if($parent) {
				$client = $parent->getClient();
				$document->setLayout($parent->getLayout('de'));
				$document->setLayout($parent->getLayout('en'), 'en');
				$document->setParent($parent);
			}
			else {
				$tpl = file_get_contents(getcwd().'/data/templates/defaults/default.tpl');
				$document->setLayout($tpl);
				$document->setLayout($tpl, 'en');
			}
			foreach(array_keys($config['locales']['list']) as $l => $entry) {
				$document->setInlanguage($l);
			}

			if($this->auth->hasIdentity()) {
				$owner = $this->identity();
				$document->setOwner($owner);

				if(!$client)
					$client = $owner->getClient();
			}
			else if($this->params()->fromQuery('t')) {
				$client = $this->dm->getRepository("Model\ClientModel")->findOneBy(array('token'=>$this->params()->fromQuery('t')));

			}
			$document->setClient($client);

			if(isset($requestparams['create'])) {

				$this->dm->persist($document);
				$this->dm->flush();

				return new JsonModel($document->toArray());
			}
		}


		$request = $this->getRequest();
		if($request->isPost()) {
			$fromPost = $this->params()->fromPost();

			$viewParams['documentform']->setInputFilter($document->getInputFilter());
			$viewParams['documentform']->setData($fromPost);

			$viewParams['sheetform']->setInputFilter($document->getInputFilter());
			$viewParams['sheetform']->setData($fromPost);

			if(isset($fromPost['cleantemplate']) && !empty($fromPost['cleantemplate'])) {
				$document->clearLayoutCache($this->lang);
			}

			$sheet = $document->getSheet();
			$document->setStructname($fromPost['structname'], $this->lang);

			if(isset($fromPost['title']) && strlen(trim($fromPost['title'])))
				$sheet->setTitle($fromPost['title'], $this->lang);
			else
				$sheet->setTitle('', $this->lang);

			if(isset($fromPost['description']) && strlen(trim($fromPost['description'])))
				$sheet->setDescription($fromPost['description'], $this->lang);
			else
				$sheet->setDescription('', $this->lang);

			if(isset($fromPost['keywords']) && strlen(trim($fromPost['keywords'])))
				$sheet->setKeywords(explode(',', $fromPost['keywords']), $this->lang);
			else
				$sheet->setKeywords(array(), $this->lang);

			if(isset($fromPost['indexfollow']) && strlen(trim($fromPost['indexfollow'])))
				$sheet->setIndexfollow($fromPost['indexfollow'], $this->lang);
			else
				$sheet->setIndexfollow('', $this->lang);

			/*
			 * rebuild path if path is empty
			*/
			if(!isset($fromPost['path']) || !strlen(trim($fromPost['path']))) {
				$path = $document->generateUrl($fallbackurllang, null, $sheet);
				$path = preg_replace("/[\/]+/s", '/', $path);
				$document->setPath($path, $this->lang);
			}
			else {
				if(isset($fromPost['path']) && strlen(trim($fromPost['path'])) && $fromPost['path'] != $document->getPath($this->lang)) {
					$document->setPath($fromPost['path'], $this->lang);
				}
			}
			$document->setVisible(intval($fromPost['visible']));
			$document->setBgimage($fromPost['bgimage']);
			$document->setStructicon($fromPost['structicon']);
			$document->setSort(intval($fromPost['sort']));
			$document->setLayout($fromPost['layout'], $this->lang);
			$document->setInlanguage($fromPost['inlanguage']);
			$document->setDocumentclass($fromPost['documentclass']);
			$document->setGeoreverse($fromPost['georeverse']);

			$document->setSheet($sheet);

			if($this->auth->hasIdentity())
				$document->addAuthor($this->identity());

			$this->dm->flush($document);

			$content  = $translator->translate("Update Success");
			$content .= ' <script>showModal("#helper");</script>';
			return new JsonModel(array(
				'#helperLabel' => array('content'=>$translator->translate("Info"), 'type'=>'update'),
				'#helperContent' => array('content'=>$content, 'type'=>'update'),
				'document' => $document->toArray()
			));

		}
		$viewParams['document'] = $document;

		$viewParams['documentform']->setData($document->toArray());
		$viewParams['documentform']->get('structname')->setValue($document->getStructname($this->lang));
		$viewParams['documentform']->get('path')->setValue($document->getPath($this->lang));
		$viewParams['documentform']->get('layout')->setValue($document->getLayout($this->lang));

		$viewParams['sheetform']->setData($document->toArray());
		$viewParams['sheetform']->get('title')->setValue($document->getSheet()->getTitle($this->lang));
		$viewParams['sheetform']->get('description')->setValue($document->getSheet()->getDescription($this->lang));
		$viewParams['sheetform']->get('indexfollow')->setValue($document->getSheet()->getIndexfollow($this->lang));
		$viewParams['sheetform']->get('keywords')->setValue(implode(',', $document->getSheet()->getKeywords($this->lang)));

		$view = new ViewModel($viewParams);
		$view->setTerminal(true)
				->setTemplate('application/document/document');
		return $view;
	}

	public function removeAction()
	{
		$session = new Container('default');
		$this->lang = $this->params()->fromQuery('lang') ? $this->params()->fromQuery('lang') : ($this->lang ? $this->lang : $session->offsetGet('lang'));
		$config = $this->getServiceLocator()->get('Config');


		$qb = $this->dm->createQueryBuilder("Model\DocumentModel")
					->remove()
					->field('id')->equals($this->params()->fromRoute("id"))
					->getQuery()
					->execute();

		return new JsonModel();
	}


	public function visibleAction()
	{
		$session = new Container('default');
		$this->lang = !isset($this->lang) || empty($this->lang) ? $session->offsetGet('lang') : $this->lang;

		$document = $this->dm->getRepository("Model\DocumentModel")->find($this->params()->fromRoute("id"));
		$document->setVisible($document->getVisible()==1?0:1);

		$this->dm->flush($document);

		return new JsonModel(array(
			'document'=>$document->toArray()
		));
	}

	public function publishAction()
	{
		$session = new Container('default');
		$this->lang = !isset($this->lang) || empty($this->lang) ? $session->offsetGet('lang') : $this->lang;

		ini_set('set_time_limit', 60*60*24);
		putenv("APP_ENV=publishing");

		$this->lang = $this->lang;
		$translator = $this->getServiceLocator()->get("translator");

		$type = $this->params()->fromQuery('type', 'single');
		$startpoint = $this->params()->fromRoute('id');

		if(file_exists(RENDERED.'/render.pid')) {
			$content  = $translator->translate("A publishing contract is already running");
			$content .= '<script>showModal("#helper");</script>';
			return new JsonModel(array(
					'#helperLabel' => array('content'=>$translator->translate("Tip"), 'type'=>'update'),
					'#helperContent' => array('content'=>$content, 'type'=>'update')
			));
		}
		else file_put_contents(RENDERED.'/render.pid', '');

		$entry = $this->dm->getRepository("Model\DocumentModel")->find($startpoint);


		switch($type) {

			case 'single':
				$entry->createPublishStruct( $this->lang, $this->dm, false );
				break;

			case 'full':
				$entry->createPublishStruct( $this->lang, $this->dm, true );
				break;

		}

		$this->publishFinishAction();

		$content  = $translator->translate("publishing finished");
		$content .= '<script>showModal("#helper");</script>';
		return new JsonModel(array(
			'#helperLabel' => array('content'=>$translator->translate("Tip"), 'type'=>'update'),
			'#helperContent' => array('content'=>$content, 'type'=>'update')
		));
	}

	public function publishFinishAction()
	{
		$session = new SessionContainer('default');
		$this->lang = !isset($this->lang) || empty($this->lang) ? $session->offsetGet('lang') : $this->lang;

		putenv("APP_ENV=local");
		if( file_exists(RENDERED.'/render.pid') )
			unlink(RENDERED.'/render.pid');
	}


}
