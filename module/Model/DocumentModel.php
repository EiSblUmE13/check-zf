<?php
// {{{ Header
/**
 *
 * @author joerg.mueller
 * @version $Id:$
 */
// }}}

namespace Model;

use Doctrine\ODM\MongoDB\Mapping\Annotations as MongoDB;
use Doctrine\Common\Collections\Collection as Collection;

//use Doctrine\ODM\MongoDB\DocumentManager;

use Model\DocumentSheetModel,
	Model\Coordinates;


use Zend\InputFilter\InputFilter;
use Zend\InputFilter\InputFilterAwareInterface;
use Zend\InputFilter\InputFilterInterface;


/**
 * @MongoDB\Document(collection="Documents", indexes={
 * @MongoDB\Index(keys={"coordinates"="2d"}),
 * },
 * repositoryClass="Repositories\DocumentModelRepository")
 * @MongoDB\HasLifecycleCallbacks
 */
class DocumentModel implements InputFilterAwareInterface
{
	protected $inputFilter;

	protected $dm;

	/**
	 * @MongoDB\Id
	 */
	protected $id;

	/**
	 * @MongoDB\Distance
	 */
	public $distance;

	/**
	 * @MongoDB\Field(type="date")
	 * @MongoDB\Index(order="asc")
	 */
	protected $datecreate;

	/**
	 * @MongoDB\Field(type="date")
	 * @MongoDB\Index(order="asc")
	 */
	public $publishedOn;

	/**
	 * @MongoDB\Field(type="date")
	 * @MongoDB\Index(order="asc")
	 */
	public $publishedOff;

	/**
	 * @MongoDB\Field(type="hash")
	 */
	protected $layout = array();

	/**
	 * @MongoDB\Field(type="hash")
	 * @MongoDB\Index(order="asc")
	 */
	protected $path = array();

	/**
	 * @MongoDB\Field(type="hash")
	 */
	protected $structname = array();

	/**
	 * @MongoDB\Field(type="collection")
	 * @MongoDB\Index(order="asc")
	 */
	protected $inlanguage = array();

	/**
	 * @MongoDB\Field(type="string")
	 * @MongoDB\Index(order="asc")
	 */
	protected $bgimage;

	/**
	 * @MongoDB\Field(type="string")
	 * @MongoDB\Index(order="asc")
	 */
	protected $structicon=null;

	/**
	 * @MongoDB\Field(type="string")
	 * @MongoDB\Index(order="asc")
	 */
	protected $documentclass;

	/**
	 * @MongoDB\Field(type="int")
	 * @MongoDB\Index(order="asc")
	 */
	protected $sort = 1;

	/**
	 * @MongoDB\Field(type="int")
	 * @MongoDB\Index(order="asc")
	 */
	protected $visible = 0;

	/**
	 * @MongoDB\Field(type="int")
	 * @MongoDB\Index(order="asc")
	 */
	protected $isdocument = 1;

	/** @MongoDB\EmbedOne(targetDocument="DocumentSheetModel") */
	protected $sheet;

	/** @MongoDB\EmbedOne(targetDocument="Coordinates") */
	protected $coordinates=null;

	/**
	 * @MongoDB\Field(type="string")
	 * @MongoDB\Index(order="asc")
	 */
	protected $georeverse=null;

	/**
	 * @MongoDB\ReferenceOne(targetDocument="DocumentModel")
	 */
	protected $parent=null;

	/**
	 * @MongoDB\ReferenceMany(targetDocument="UserModel")
	 */
	protected $authors;

	/**
	 * @MongoDB\ReferenceOne(targetDocument="UserModel")
	 */
	protected $owner;

	/**
	 * @MongoDB\ReferenceOne(targetDocument="ClientModel")
	 */
	protected $client;

	/**
	 * @MongoDB\Field(type="string")
	 * @MongoDB\Index(unique=true, sparse=true, dropDups=true)
	 */
	protected $token;

	/** @MongoDB\PreFlush */
	public function findGeoCoords()
	{
		$rotatecount= 20;

		if($this->georeverse && strlen($this->georeverse))
			$this->setCoordinates( Coordinates::findGeoCoords($this->georeverse) );

		if($this->getAuthors() != null)
			while($this->getAuthors()->count()>$rotatecount) $this->removeAuthor($this->getAuthors()->first());
	}


	public function __construct( Array $params=array() )
	{
		$this->token = \Model\UserModel::newPassword(16, null, 0);
		$this->datecreate = new \DateTime();
		$this->publishedOn = new \DateTime();
		$this->documentclass = '';
		$this->structicon = '';
		$this->bgimage = '';
		$this->authors = [];
	}

	public function toArray($lang=null)
	{
		$o = get_object_vars( $this );
		$o['sheet'] = isset($o['sheet']) && is_object($o['sheet']) ? $o['sheet']->toArray() : $o['sheet'];
		$o['parent'] = isset($o['parent']) && is_object($o['parent']) ? $o['parent']->toArray() : $o['parent'];
		$o['owner'] = isset($o['owner']) && is_object($o['owner']) ? $o['owner']->toArray() : $o['owner'];
		return $o;
	}

	public function exchangeArray($data, $lang)
	{
	}

	public function setInputFilter(InputFilterInterface $inputFilter)
	{
		throw new \Exception("Not used");
	}

	public function getInputFilter()
	{
		if (!$this->inputFilter) {
			$inputFilter = new InputFilter();

			$inputFilter->add(array(
				'name'	 => 'path',
				'required' => true,
				'filters'  => array(
					array('name' => 'StripTags'),
					array('name' => 'StringTrim'),
				),
				'validators' => array(
					array(
						'name'	=> 'StringLength',
						'options' => array(
							'encoding' => 'UTF-8',
							'min'	  => 5,
							'max'	  => 101,
						),
					),
				),
			));

			$inputFilter->add(array(
				'name'	 => 'structurname',
				'required' => true,
				'filters'  => array(
					array('name' => 'StripTags'),
					array('name' => 'StringTrim'),
				),
				'validators' => array(
					array(
						'name'	=> 'StringLength',
						'options' => array(
							'encoding' => 'UTF-8',
							'min'	  => 5,
							'max'	  => 101,
						),
					),
				),
			));

			$inputFilter->add(array(
				'name'	 => 'sort',
				'required' => true,
				'filters'  => array(
					array('name' => 'StripTags'),
					array('name' => 'StringTrim'),
				),
				'validators' => array(
				),
			));

			$inputFilter->add(array(
				'name'	 => 'visible',
				'required' => true,
				'filters'  => array(
					array('name' => 'StripTags'),
					array('name' => 'StringTrim'),
				),
				'validators' => array(
				),
			));

			$this->inputFilter = $inputFilter;
		}

		return $this->inputFilter;
	}

	public function getChildList( Array $options=array(), $dm )
	{
		$sort = isset($options['sort']) && !empty($options['sort']) && is_array($options['sort']) ? $options['sort']  : array('structname','ASC');
		$options['lang'] = isset($options['lang']) ? $options['lang'] : 'de';

		$qb = $dm->createQueryBuilder("Model\DocumentModel")
					->field('publishedOn')->lte(new \DateTime())
					->field('parent')->references($this);

		$qb->addOr($qb->expr()->field('publishedOff')->exists(false));
		$qb->addOr($qb->expr()->field('publishedOff')->gte(new \DateTime()));

		$qb->field('inlanguage')->equals($options['lang']);
		if(!isset($options['iseditor']) || false == $options['iseditor']) {
			$qb->field('visible')->equals(1);
		}

		$qb->sort($sort[0],$sort[1]);

		return $qb->getQuery()->execute();
	}

	public function clearLayoutCache($lang)
	{
		try {
			@unlink(getcwd()."/data/cache/{$this->id}_{$lang}.tpl");
		}
		catch(Exception $e) {}
	}

	public static function createUrl($str, $delimiter = '-')
	{
		$search = array(
			'Ö','Ä','Ü','ö','ä','ü','ß'
		);
		$replace = array(
			'Oe','Ae','Ue','oe','ae','ue','ss'
		);
		$str = str_replace( $search, $replace, $str );

		$clean = iconv( 'UTF-8', 'ASCII//TRANSLIT', $str );
		$clean = preg_replace( "/[^a-zA-Z0-9\/_|+ -]/siU", '', $clean );
		$clean = preg_replace( "/[\/_|+ -]+/", $delimiter, $clean );
		$clean = strtolower( trim( $clean, '-' ) );

		return $clean;
	}

	public function generateUrl( $lang='de', $parent=null, $sheet=null )
	{
		$path_parent='/';
		$path_current='/';

		$parent = $parent ? $parent : ($this->getParent() ? $this->getParent() : null);
		if($parent)
			$path_parent = $parent->getPath($lang);

		$sheet = $sheet ? $sheet : ($this->getSheet() ? $this->getSheet() : null);
		if($sheet && $sheet->getTitle($lang))
			$path_current .= self::createUrl($sheet->getTitle($lang));

		$path = $path_parent . $path_current;
		return preg_replace("/[\/]+/siU", '/', $path);
	}

	public function hasChildren( $dm )
	{
		$qb = $dm->createQueryBuilder("Model\DocumentModel")
					->field('parent')->references($this)
					->field('visible')->equals(1)
					->getQuery()->execute();

		return $qb->count()>0 ? true : false;
	}

	public function getChildren( $dm )
	{
		return $dm->createQueryBuilder("Model\DocumentModel")
					->field('parent')->references($this)
					->field('visible')->equals(1)
					->sort('sort','ASC')
					->getQuery()->execute();
	}

	public function getArticleWidget($nth, $dm)
	{
		$qb = $dm->createQueryBuilder("Model\WidgetModel")
					->field('type')->equals('article')
					->field('parent')->references($this);



		return $qb->getQuery()->getSingleResult();
	}


	// interface

	public function getPages() { return array($this); }
	public function getOptions() { return array('object_manager'=>'doctrine.documentmanager.odm_default','target_class'=>'\Model\DocumentModel','ulClass'=>'nav navbar','maxDepth'=>1); }
	public function getAttributes() {}


	/**
	 * Get id
	 *
	 * @return id $id
	 */
	public function getId()
	{
		return $this->id;
	}

	/**
	 * Set datecreate
	 *
	 * @param date $datecreate
	 * @return self
	 */
	public function setDatecreate($datecreate)
	{
		$this->datecreate = $datecreate;
		return $this;
	}

	/**
	 * Get datecreate
	 *
	 * @return date $datecreate
	 */
	public function getDatecreate()
	{
		return $this->datecreate;
	}

	/**
	 * Set layout
	 *
	 * @param string $layout
	 * @return self
	 */
	public function setLayout($layout, $lang='de')
	{
		$this->layout[$lang] = $layout;
		return $this;
	}

	/**
	 * Get layout
	 *
	 * @return Doctrine\MongoDB\Collection $layout
	 */
	public function getLayout( $lang='de' )
	{
		if($lang === null) return $this->layout;
		return isset($this->layout[$lang]) ? $this->layout[$lang] : null;
	}

	/**
	 * Set path
	 *
	 * @param string $path
	 * @return self
	 */
	public function setPath($path, $lang='de')
	{
		$this->path[$lang] = $path;
		return $this;
	}

	/**
	 * Get path
	 *
	 * @return Doctrine\MongoDB\Collection $path
	 */
	public function getPath( $lang='de' )
	{
		if($lang === null) return $this->path;
		return isset($this->path[$lang]) ? $this->path[$lang] : null;
	}

	/**
	 * Set structname
	 *
	 * @param  $structname
	 * @return self
	 */
	public function setStructname($structname, $lang='de')
	{
		$this->structname[$lang] = $structname;
		return $this;
	}

	/**
	 * Get structname
	 *
	 * @return Doctrine\MongoDB\Collection $structname
	 */
	public function getStructname( $lang='de' )
	{
		return isset($this->structname[$lang]) ? $this->structname[$lang] : null;
	}

	/**
	 * Set inlanguage
	 *
	 * @param collection $inlanguage
	 * @return self
	 */
	public function setInlanguage($inlanguage)
	{
		$this->inlanguage = $inlanguage;
		return $this;
	}

	/**
	 * Get inlanguage
	 *
	 * @return Doctrine\MongoDB\Collections $inlanguage
	 */
	public function getInlanguage()
	{
		return $this->inlanguage;
	}

	/**
	 * Set sort
	 *
	 * @param int $sort
	 * @return self
	 */
	public function setSort($sort)
	{
		$this->sort = $sort;
		return $this;
	}

	/**
	 * Get sort
	 *
	 * @return int $sort
	 */
	public function getSort()
	{
		return $this->sort;
	}

	/**
	 * Set visible
	 *
	 * @param int $visible
	 * @return self
	 */
	public function setVisible($visible)
	{
		$this->visible = $visible;
		return $this;
	}

	/**
	 * Get visible
	 *
	 * @return int $visible
	 */
	public function getVisible()
	{
		return $this->visible;
	}

	/**
	 * Set parent
	 *
	 * @param Model\DocumentModel $parent
	 * @return self
	 */
	public function setParent( \Model\DocumentModel $parent=null )
	{
		$this->parent = $parent;
		return $this;
	}

	/**
	 * Get parent
	 *
	 * @return Model\DocumentModel $parent
	 */
	public function getParent()
	{
		return $this->parent;
	}

	/**
	 * Set token
	 *
	 * @param string $token
	 * @return self
	 */
	public function setToken($token)
	{
		$this->token = $token;
		return $this;
	}

	/**
	 * Get token
	 *
	 * @return string $token
	 */
	public function getToken()
	{
		return $this->token;
	}

    /**
     * Set sheet
     *
     * @param Model\DocumentSheetModel $sheet
     * @return self
     */
    public function setSheet(\Model\DocumentSheetModel $sheet)
    {
        $this->sheet = $sheet;
        return $this;
    }

    /**
     * Get sheet
     *
     * @return Model\DocumentSheetModel $sheet
     */
    public function getSheet()
    {
        return $this->sheet ? $this->sheet : new \Model\DocumentSheetModel();
    }

    /**
     * Set coordinates
     *
     * @param \Model\Coordinates $coordinates
     * @return self
     */
    public function setCoordinates(\Model\Coordinates $coordinates)
    {
        $this->coordinates = $coordinates;
        return $this;
    }

    /**
     * Get coordinates
     *
     * @return \Model\Coordinates $coordinates
     */
    public function getCoordinates()
    {
        return $this->coordinates;
    }

    /**
     * Set georeverse
     *
     * @param string $georeverse
     * @return self
     */
    public function setGeoreverse($georeverse)
    {
        $this->georeverse = $georeverse;
        return $this;
    }

    /**
     * Get georeverse
     *
     * @return string $georeverse
     */
    public function getGeoreverse()
    {
        return $this->georeverse;
    }

    /**
     * Add author
     *
     * @param Model\UserModel $author
     */
    public function addAuthor(\Model\UserModel $author)
    {
        $this->authors[] = $author;
    }

    /**
     * Remove author
     *
     * @param Model\UserModel $author
     */
    public function removeAuthor(\Model\UserModel $author)
    {
        $this->authors->removeElement($author);
    }

    /**
     * Get authors
     *
     * @return Doctrine\Common\Collections\Collection $authors
     */
    public function getAuthors()
    {
        return $this->authors;
    }

    /**
     * Set owner
     *
     * @param Model\UserModel $owner
     * @return self
     */
    public function setOwner(\Model\UserModel $owner)
    {
        $this->owner = $owner;
        return $this;
    }

    /**
     * Get owner
     *
     * @return Model\UserModel $owner
     */
    public function getOwner()
    {
        return $this->owner;
    }

    /**
     * Set isdocument
     *
     * @param int $isdocument
     * @return self
     */
    public function setIsdocument($isdocument)
    {
        $this->isdocument = $isdocument;
        return $this;
    }

    /**
     * Get isdocument
     *
     * @return int $isdocument
     */
    public function getIsdocument()
    {
        return $this->isdocument;
    }

    /**
     * Set bgimage
     *
     * @param string $bgimage
     * @return self
     */
    public function setBgimage($bgimage)
    {
        $this->bgimage = $bgimage;
        return $this;
    }

    /**
     * Get bgimage
     *
     * @return string $bgimage
     */
    public function getBgimage()
    {
        return $this->bgimage;
    }

    /**
     * Set structicon
     *
     * @param string $structicon
     * @return self
     */
    public function setStructicon($structicon)
    {
        $this->structicon = $structicon;
        return $this;
    }

    /**
     * Get structicon
     *
     * @return string $structicon
     */
    public function getStructicon()
    {
        return $this->structicon;
    }

    /**
     * Set documentclass
     *
     * @param string $documentclass
     * @return self
     */
    public function setDocumentclass($documentclass)
    {
        $this->documentclass = $documentclass;
        return $this;
    }

    /**
     * Get documentclass
     *
     * @return string $documentclass
     */
    public function getDocumentclass()
    {
        return $this->documentclass;
    }

    /**
     * Set client
     *
     * @param Model\ClientModel $client
     * @return self
     */
    public function setClient(\Model\ClientModel $client)
    {
        $this->client = $client;
        return $this;
    }

    /**
     * Get client
     *
     * @return Model\ClientModel $client
     */
    public function getClient()
    {
        return $this->client;
    }

    /**
     * Set publishedOn
     *
     * @param date $publishedOn
     * @return self
     */
    public function setPublishedOn($publishedOn)
    {
    	if(is_string($publishedOn)) $publishedOn = new \DateTime($publishedOn);
        $this->publishedOn = $publishedOn;
        return $this;
    }

    /**
     * Get publishedOn
     *
     * @return date $publishedOn
     */
    public function getPublishedOn()
    {
        return $this->publishedOn;
    }

    /**
     * Set publishedOff
     *
     * @param date $publishedOff
     * @return self
     */
    public function setPublishedOff($publishedOff=false)
    {
    	if($publishedOff == false) {
    		$this->publishedOff = $publishedOff;
    		return $this;
    	}
    	if(is_string($publishedOff)) $publishedOff = new \DateTime($publishedOff);
        $this->publishedOff = $publishedOff;
        return $this;
    }

    /**
     * Get publishedOff
     *
     * @return date $publishedOff
     */
    public function getPublishedOff()
    {
        return $this->publishedOff;
    }

    /**
     * Set distance
     *
     * @param string $distance
     * @return self
     */
    public function setDistance($distance)
    {
        $this->distance = $distance;
        return $this;
    }

    /**
     * Get distance
     *
     * @return string $distance
     */
    public function getDistance()
    {
        return $this->distance;
    }
}
