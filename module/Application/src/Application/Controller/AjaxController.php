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

use Zend\View\Model\JsonModel;
use Zend\Session\Container as Container;

use Model\DocumentModel,
	Model\UserModel,
	Model\Image,
	Model\ClientModel;



class AjaxController extends AbstractActionController
{

	public function dtreeAction()
	{

		if($editor = $this->identity())
			$client = $editor->getClient();
		else
			$client = $this->dm->getRepository("Model\ClientModel")->findOneBy(array('shortname'=>'hhog'));

		$requestparams = array_merge($this->params()->fromQuery(),$this->params()->fromPost(),$this->params()->fromRoute());
		$id = isset($requestparams['id']) ? $requestparams['id'] : null;

		$qb = $this->dm->createQueryBuilder('Model\DocumentModel');

		if(isset($requestparams['id']) && $requestparams['id'] == 0) {
			$qb->addOr($qb->expr()->field('parent')->equals(null));
			$qb->addOr($qb->expr()->field('parent')->exists(false));
		}
		else {
			$parent = $this->dm->getRepository("Model\DocumentModel")->find($requestparams['id']);
			$qb->field('parent')->references($parent);
		}
// 		$qb->field('client')->references($client);

		$qb->sort('sort','ASC');
		$documents = $qb->getQuery()->execute();


		$return = array();
		foreach($documents as $document) $return[] = $document->toArray();

		return new JsonModel(array('documents'=>$return));
	}

	public function userTreeAction()
	{
		$requestparams = array_merge($this->params()->fromQuery(),$this->params()->fromPost(),$this->params()->fromRoute());
		$id = isset($requestparams['id']) ? $requestparams['id'] : null;

		$qb = $this->dm->createQueryBuilder('Model\UserModel');

		if($requestparams['id'] == 0) {
			$qb->addOr($qb->expr()->field('parent')->exists(false));
			$qb->addOr($qb->expr()->field('parent')->equals(null));
		}
		else {
			$parent = $this->dm->getRepository("Model\UserModel")->find($id);
			$qb->field('parent')->references($parent);
		}

		$users = $qb->getQuery()->execute();

		$return = array();
		foreach($users as $user) $return[] = $user->toArray();

		return new JsonModel(array('users'=>$return));
	}

	public function usersAction()
	{
		$requestparams = array_merge($this->params()->fromQuery(),$this->params()->fromPost(),$this->params()->fromRoute());
		$q = $requestparams['q'];

		$qb = $this->dm->createQueryBuilder('Model\UserModel');
		$qb->addOr($qb->expr()->field('nickname')->equals(new \MongoRegex("/{$q}/i")));
		$qb->addOr($qb->expr()->field('email')->equals(new \MongoRegex("/{$q}/i")));
		$qb->addOr($qb->expr()->field('sheet.firstname')->equals(new \MongoRegex("/{$q}/i")));
		$qb->addOr($qb->expr()->field('sheet.lastname')->equals(new \MongoRegex("/{$q}/i")));
		$qb->addOr($qb->expr()->field('sheet.city')->equals(new \MongoRegex("/{$q}/i")));
		$qb->addOr($qb->expr()->field('sheet.streetnr')->equals(new \MongoRegex("/{$q}/i")));

		$users = $qb->getQuery()->execute();

		$return = array();
		foreach($users as $user) {
			$user_array = $user->toArray();
			$user_array['fullname'] = $user->getFullname();
			$return[] = $user_array;
		}
		return new JsonModel($return);
	}

	public function clientTreeAction()
	{

		$id = $this->params()->fromQuery("id");
		$qb = $this->dm->createQueryBuilder('Model\ClientModel');

		if(!User::perm(GRP_LEADER, $this->identity())) {
			$qb->field('employee')->equals($usession['id']);
		}
		if($this->params()->fromQuery("id", 0) != 0) {
			$qb->field('parent.id')->equals($this->params()->fromQuery("id"));
		}
		else {
			$qb->field('parent.id')->equals(null);
		}

		$clients = $qb->getQuery()->execute();

		$return = array();
		foreach($clients as $client) $return[] = $client->toArray();

		return new JsonModel(array('clients'=>$return));
	}

	public function tokenAction()
	{
		$token = \Model\User::newPassword(16, null, 0);
		return new JsonModel(array('token'=>$token));
	}

	public function thumbsAction()
	{
		$lang = $this->lang;
		$query = $this->params()->fromQuery('term');
		$folder = $this->params()->fromQuery('folder');
		$qb = $this->dm->createQueryBuilder('Model\Image');

		$limit = $this->params()->fromQuery('limit', 10);
		$page  = $this->params()->fromQuery('page', 1);
		$offset = $limit*$page-$limit;

		if($query && strlen(trim($query))) {
			$qb->addOr($qb->expr()->field('name')->equals( new \MongoRegex("/.*{$query}.*/i") ));
			$qb->addOr($qb->expr()->field('filename')->equals( new \MongoRegex("/.*{$query}.*/i") ));
			$qb->addOr($qb->expr()->field('attributes.copyright')->equals( new \MongoRegex("/.*{$query}.*/i") ));
			$qb->addOr($qb->expr()->field("attributes.title.{$lang}")->equals( new \MongoRegex("/.*{$query}.*/i") ));
			$qb->addOr($qb->expr()->field("attributes.alt.{$lang}")->equals( new \MongoRegex("/.*{$query}.*/i") ));
			$qb->addOr($qb->expr()->field("attributes.tag")->in( array($query) ));
		}
		if($folder && strlen(trim($folder))) {
			$qb->field('folder')->equals($folder);
		}

		$qb->sort('uploadDate', 'desc')
			->limit($limit)
			->skip($offset);

		$images = $qb->getQuery()
						->execute();

		$return = array();
		foreach($images as $image) {
			$return[] = $image->toArray();
		}


		return new JsonModel($return);
	}

	public function documentSearchAction()
	{

		return new JsonModel($this->params());
	}

	public function isearchAction()
	{

		$folders = $this->params()->fromQuery('folder');
		$query = $this->params()->fromQuery('q');

		$qb = $this->dm->createQueryBuilder('Model\Image');

		if(isset($folders) && is_array($folders))
			$qb->addOr($qb->expr()->field('folder')->in($folders));

		if($query && strlen(trim($query))) {
			$qb->addOr($qb->expr()->field('name')->equals( new \MongoRegex("/.*{$query}.*/i") ));
			$qb->addOr($qb->expr()->field('filename')->equals( new \MongoRegex("/.*{$query}.*/i") ));
			$qb->addOr($qb->expr()->field('attributes.copyright')->equals( new \MongoRegex("/.*{$query}.*/i") ));
			$qb->addOr($qb->expr()->field("attributes.title.{$this->lang}")->equals( new \MongoRegex("/.*{$query}.*/i") ));
			$qb->addOr($qb->expr()->field("attributes.alt.{$this->lang}")->equals( new \MongoRegex("/.*{$query}.*/i") ));
			$qb->addOr($qb->expr()->field("attributes.tag")->in( array($query) ));
		}


		$images = $qb->getQuery()->execute();

		$return = array();
		foreach($images as $image) $return[] = $image->toArray();


		return new JsonModel($return);
	}
}
