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
use \Doctrine\MongoDB\Collection;

use Model\DocumentModel,
	Model\Coordinates;


use Zend\InputFilter\InputFilter;
use Zend\InputFilter\InputFilterAwareInterface;
use Zend\InputFilter\InputFilterInterface;


/**
 * @MongoDB\Document(collection="Widgets", indexes={
 * @MongoDB\Index(keys={"coordinates"="2d"}),
 * },
 * repositoryClass="Repositories\WidgetModelRepository")
 */
class WidgetModel implements InputFilterAwareInterface
{
	protected $inputFilter;

	/**
	 * @MongoDB\Id
	 */
	protected $id;

	/**
	 * @MongoDB\Field(type="date")
	 */
	protected $datecreate;

	/**
	 * @MongoDB\Field(type="date")
	 */
	protected $dateupdate=null;

	/**
	 * @MongoDB\Field(type="date")
	 */
	protected $datestart = null;

	/**
	 * @MongoDB\Field(type="date")
	 */
	protected $datestop = null;

	/**
	 * @MongoDB\Field(type="string")
	 * @MongoDB\Index(order="asc")
	 */
	protected $anker;

	/**
	 * @MongoDB\Field(type="string")
	 * @MongoDB\Index(order="asc")
	 */
	protected $type;

	/**
	 * @MongoDB\Field(type="hash")
	 */
	protected $path = array();

	/**
	 * @MongoDB\Field(type="hash")
	*/
	protected $attributes;

	/**
	 * @MongoDB\Field(type="collection")
	*/
	protected $inlanguage = array();

	/**
	 * @MongoDB\Field(type="hash")
	*/
	protected $draft = null;

	/**
	 * @MongoDB\Field(type="int")
	 * @MongoDB\Index(order="asc")
	 */
	protected $sort = 1;

	/**
	 * @MongoDB\Field(type="hash")
	 */
	protected $editmode = null;

	/**
	 * @MongoDB\ReferenceOne(targetDocument="DocumentModel")
	 */
	protected $parent = null;

	/**
	 * @MongoDB\EmbedOne(targetDocument="Coordinates")
	 */
	protected $coordinates = null;

	/**
	 * @MongoDB\ReferenceOne(targetDocument="UserModel")
	 */
	protected $author;

	/**
	 * @MongoDB\Field(type="string")
	*/
	protected $georeverse = '';

	/**
	 * @MongoDB\Field(type="string")
	 * @MongoDB\Index(order="asc")
	 */
	protected $token;

	public function __construct( Array $params=array() )
	{
		$this->datecreate = new \DateTime();
		$this->datestart = new \DateTime();
		$this->dateupdate = new \DateTime();
		$this->token = \Model\UserModel::newPassword(16, null, 0);
	}

	public function toArray()
	{
		$widget = get_object_vars( $this );
		$widget['parent'] = !empty($widget['parent']) ? $widget['parent']->toArray() : null;
		$widget['attributes'] = !empty($widget['attributes']) ? (array) $this->getAttributes() : null;
		return $widget;
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

			$this->inputFilter = $inputFilter;
		}

		return $this->inputFilter;
	}


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
	 * Set dateupdate
	 *
	 * @param date $dateupdate
	 * @return self
	 */
	public function setDateupdate($dateupdate)
	{
		$this->dateupdate = $dateupdate;
		return $this;
	}

	/**
	 * Get dateupdate
	 *
	 * @return date $dateupdate
	 */
	public function getDateupdate()
	{
		return $this->dateupdate;
	}

	/**
	 * Set datestart
	 *
	 * @param date $datestart
	 * @return self
	 */
	public function setDatestart($datestart)
	{
		$this->datestart = $datestart;
		return $this;
	}

	/**
	 * Get datestart
	 *
	 * @return date $datestart
	 */
	public function getDatestart()
	{
		return $this->datestart;
	}

	/**
	 * Set datestop
	 *
	 * @param date $datestop
	 * @return self
	 */
	public function setDatestop($datestop)
	{
		$this->datestop = $datestop;
		return $this;
	}

	/**
	 * Get datestop
	 *
	 * @return date $datestop
	 */
	public function getDatestop()
	{
		return $this->datestop;
	}

	/**
	 * Set anker
	 *
	 * @param string $anker
	 * @return self
	 */
	public function setAnker($anker)
	{
		$this->anker = $anker;
		return $this;
	}

	/**
	 * Get anker
	 *
	 * @return string $anker
	 */
	public function getAnker()
	{
		return $this->anker;
	}

	/**
	 * Set type
	 *
	 * @param string $type
	 * @return self
	 */
	public function setType($type)
	{
		$this->type = $type;
		return $this;
	}

	/**
	 * Get type
	 *
	 * @return string $type
	 */
	public function getType()
	{
		return $this->type;
	}

	/**
	 * Set path
	 *
	 * @param hash $path
	 * @return self
	 */
	public function setPath($path)
	{
		$this->path = $path;
		return $this;
	}

	/**
	 * Get path
	 *
	 * @return hash $path
	 */
	public function getPath()
	{
		return $this->path;
	}

	/**
	 * Set attributes
	 *
	 * @param hash $attributes
	 * @return self
	 */
	public function setAttributes($attributes)
	{
		$this->attributes = $attributes;
		return $this;
	}

	/**
	 * Get attributes
	 *
	 * @return hash $attributes
	 */
	public function getAttributes()
	{
		return $this->attributes;
	}

	/**
	 * Set inlanguage
	 *
	 * @param array $inlanguage
	 * @return self
	 */
	public function setInlanguage($inlanguage)
	{
		if(!is_array($this->inlanguage)) $this->inlanguage = array();
		if(!in_array($inlanguage, $this->inlanguage)) {
			$this->inlanguage[] = $inlanguage;
		}
		return $this;
	}

	/**
	 * Get inlanguage
	 *
	 * @return array $inlanguage
	 */
	public function getInlanguage()
	{
		return $this->inlanguage;
	}

	/**
	 * Set draft
	 *
	 * @param hash $draft
	 * @return self
	 */
	public function setDraft($draft)
	{
		$this->draft = $draft;
		return $this;
	}

	/**
	 * Get draft
	 *
	 * @return hash $draft
	 */
	public function getDraft()
	{
		return $this->draft;
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
	 * Set editmode
	 *
	 * @param hash $editmode
	 * @return self
	 */
	public function setEditmode($editmode)
	{
		$this->editmode = $editmode;
		return $this;
	}

	/**
	 * Get editmode
	 *
	 * @return hash $editmode
	 */
	public function getEditmode()
	{
		return $this->editmode;
	}

	/**
	 * Set parent
	 *
	 * @param Model\DocumentModel $parent
	 * @return self
	 */
	public function setParent(\Model\DocumentModel $parent)
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
	 * Set coordinates
	 *
	 * @param Model\Coordinates $coordinates
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
	 * @return Model\Coordinates $coordinates
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
     * Set author
     *
     * @param Model\UserModel $author
     * @return self
     */
    public function setAuthor(\Model\UserModel $author)
    {
        $this->author = $author;
        return $this;
    }

    /**
     * Get author
     *
     * @return Model\UserModel $author
     */
    public function getAuthor()
    {
        return $this->author;
    }
}
