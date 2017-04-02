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
	Zend\Mvc\MvcEvent;

use Zend\View\Model\ViewModel;
use Zend\View\Model\JsonModel;

use Zend\Session\Container as Container;

use Model\DocumentModel,
	Model\WidgetModel,
	Model\Coordinates,
	Model\ArticleAttributes;

class WidgetController extends AbstractActionController
{

	public function indexAction()
	{
		$session = new Container('default');
		$this->lang = $this->params()->fromQuery('lang') ? $this->params()->fromQuery('lang') : ($this->lang ? $this->lang : $session->offsetGet('lang'));
		$config = $this->getServiceLocator()->get('Config');

		$viewParams = array(
			'lang' => $this->lang,
			'dm' => $this->dm,
			'config' => $config
		);
		$this->layout()->setVariables($viewParams);

		return new ViewModel($viewParams);
	}

	public function setAction()
	{
		$session = new Container('default');
		$this->lang = $this->params()->fromQuery('lang') ? $this->params()->fromQuery('lang') : ($this->lang ? $this->lang : $session->offsetGet('lang'));
		$config = $this->getServiceLocator()->get('Config');

		$viewParams = array(
			'lang' => $this->lang,
			'dm' => $this->dm,
			'config' => $config
		);
		$this->layout()->setVariables($viewParams);
		$this->translator = $translator = $this->getServiceLocator()->get('translator');

		$requestparams = array_merge($this->params()->fromQuery(), $this->params()->fromPost());

		if(isset($requestparams['token']) && strlen(trim($requestparams['token'])) && $requestparams['token'] != 'undefined') {
			$widget = $this->dm->getRepository("Model\WidgetModel")->findOneBy(array('token'=>$requestparams['token']));
			$type = $widget->getType();
		}
		else {
			$widget = new WidgetModel();
			$widget->setAuthor($this->identity());

			foreach(array_keys($config['locales']['list']) as $l) {
				$widget->setInlanguage($l);
			}

			$id = $this->params()->fromRoute('id');
			$parent = $this->dm->getRepository("Model\DocumentModel")->find($id);
			$widget->setParent($parent);

			$type = $this->params()->fromQuery('type');
			$anker = $this->params()->fromQuery('path');

			$widget->setType($requestparams['type']);
			$widget->setAnker($requestparams['path']);

			$type = $requestparams['type'];

			if(isset($config['attributes'][$type])) {
				$widget->setAttributes($config['attributes'][$type]);
			}
		}

		$request = $this->getRequest();
		if($request->isPost() == true) {

			$attributes = array();

			switch($type) {

				case 'carousel':
					foreach($requestparams['slide']['image'] as $k => $v) {
						$attributes['slides'][] = array(
							'image' => $requestparams['slide']['image'][$k],
							'label' => $requestparams['slide']['label'][$k],
							'scale' => $requestparams['slide']['scale'][$k],
							'message' => $requestparams['slide']['message'][$k]
						);
					}
					break;
				default:
					if(isset($config['attributes'][$type])) {
						foreach($config['attributes'][$type] as $key => $entry) {

							if($key == 'form' && isset($config['attributes'][$type][$entry])) {
								$attributes[$entry] = 1;
							}
							else {
								if(is_array($entry) && isset($entry[$this->lang])) {
									$attributes[$key][$this->lang] = isset($requestparams[$key]) ? $requestparams[$key] : $config['attributes'][$type];
								}
								else {
									$attributes[$key] = isset($requestparams[$key]) ? $requestparams[$key] : $config['attributes'][$type];
								}
							}

						}
					}
					break;

			}

			$widget->setAttributes($attributes);

			if(isset($requestparams['georeverse']) && strlen($requestparams['georeverse'])) {
				if($requestparams['georeverse'] != $widget->getGeoreverse()) {
					$widget->setGeoreverse($requestparams['georeverse']);
					$widget->setCoordinates(Coordinates::findGeoCoords($requestparams['georeverse']));
				}
			}

			$this->dm->flush($widget);

			$content  = $translator->translate("Update Success");
			$content .= ' <script>showModal("#helper");</script>';
			return new JsonModel(array(
				'#helperLabel' => array('content'=>$translator->translate("Info"), 'type'=>'update'),
				'#helperContent' => array('content'=>$content, 'type'=>'update')
			));

		}

		$this->dm->persist($widget);
		$this->dm->flush();

		$viewParams['widget'] = $widget;

		$view = new ViewModel($viewParams);
		$view->setTemplate('application/widget/'.$type.'/edit')
			->setTerminal(true);

		return $view;

	}

	public function removeAction()
	{
		$id = $this->params()->fromRoute('id');
		$qb = $this->dm->createQueryBuilder('Model\WidgetModel');
		$qb->remove()
			->field('id')->equals($id)
			->getQuery()
			->execute();

		return new JsonModel(array());
	}
}
