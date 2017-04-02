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
use Doctrine\Common\Collections\Criteria;

use Model\UserSheetModel;

use Zend\InputFilter\InputFilter;
use Zend\InputFilter\InputFilterAwareInterface;
use Zend\InputFilter\InputFilterInterface;

use Model\Coordinates;

/**
 * @MongoDB\Document(collection="Clients", indexes={
 *	 @MongoDB\Index(keys={"sheet.coordinates"="2d"})
 * },
 * repositoryClass="Repositories\ClientModelRepository")
 * @MongoDB\HasLifecycleCallbacks
 */
class ClientModel implements InputFilterAwareInterface
{
	protected $inputFilter;

	/**
	 * @MongoDB\Id
	 */
	protected $id;

	/**
	 * @MongoDB\Distance
	 */
	public $distance;

	/** @MongoDB\EmbedOne(targetDocument="Coordinates") */
	protected $coordinates=null;

	/**
	 * @MongoDB\Field(type="date")
	 */
	protected $datecreate;

	/**
	 * @MongoDB\Field(type="string")
	 */
	 protected $name;

    /**
	 * @MongoDB\Field(type="string")
	 */
	 protected $firm;

    /**
	 * @MongoDB\Field(type="string")
	 */
	 protected $zipcode;

    /**
	 * @MongoDB\Field(type="string")
	 */
	 protected $streetnr;

    /**
	 * @MongoDB\Field(type="string")
	 */
	 protected $city;

	 /**
	  * @MongoDB\Field(type="string")
	  */
	 protected $phone;

	 /**
	  * @MongoDB\Field(type="string")
	  */
	 protected $fax;

	 /**
	  * @MongoDB\Field(type="string")
	  */
	 protected $mail;


	/**
	 * @MongoDB\Field(type="string")
	 * @MongoDB\Index(unique=true, sparse=true, dropDups=true)
	 */
	protected $token;

	/**
	 * @MongoDB\Field(type="string")
	 * @MongoDB\Index(unique=true, sparse=true, dropDups=true)
	 */
	protected $shortname;

	/** @MongoDB\PreFlush */
	public function findGeoCoords()
	{
		if($this->city && strlen($this->city))
			$this->setCoordinates( Coordinates::findGeoCoords("$this->zipcode,$this->city,$this->streetnr") );
	}


	public function __construct()
	{
		$this->datecreate = new \DateTime();
		$this->token = \Model\UserModel::newPassword(16,null,0);
	}

	public function toArray()
	{
		return get_object_vars($this);
	}


	public function exchangeArray($data, $dm, $flag='user')
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
     * Set shortname
     *
     * @param string $shortname
     * @return self
     */
    public function setShortname($shortname)
    {
        $this->shortname = $shortname;
        return $this;
    }

    /**
     * Get shortname
     *
     * @return string $shortname
     */
    public function getShortname()
    {
        return $this->shortname;
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

    /**
     * Set name
     *
     * @param string $name
     * @return self
     */
    public function setName($name)
    {
        $this->name = $name;
        return $this;
    }

    /**
     * Get name
     *
     * @return string $name
     */
    public function getName()
    {
        return $this->name;
    }

    /**
     * Set firm
     *
     * @param string $firm
     * @return self
     */
    public function setFirm($firm)
    {
        $this->firm = $firm;
        return $this;
    }

    /**
     * Get firm
     *
     * @return string $firm
     */
    public function getFirm()
    {
        return $this->firm;
    }

    /**
     * Set zipcode
     *
     * @param string $zipcode
     * @return self
     */
    public function setZipcode($zipcode)
    {
        $this->zipcode = $zipcode;
        return $this;
    }

    /**
     * Get zipcode
     *
     * @return string $zipcode
     */
    public function getZipcode()
    {
        return $this->zipcode;
    }

    /**
     * Set streetnr
     *
     * @param string $streetnr
     * @return self
     */
    public function setStreetnr($streetnr)
    {
        $this->streetnr = $streetnr;
        return $this;
    }

    /**
     * Get streetnr
     *
     * @return string $streetnr
     */
    public function getStreetnr()
    {
        return $this->streetnr;
    }

    /**
     * Set city
     *
     * @param string $city
     * @return self
     */
    public function setCity($city)
    {
        $this->city = $city;
        return $this;
    }

    /**
     * Get city
     *
     * @return string $city
     */
    public function getCity()
    {
        return $this->city;
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
     * Set phone
     *
     * @param string $phone
     * @return self
     */
    public function setPhone($phone)
    {
        $this->phone = $phone;
        return $this;
    }

    /**
     * Get phone
     *
     * @return string $phone
     */
    public function getPhone()
    {
        return str_replace(' ', '.', $this->phone);
    }

    /**
     * Set fax
     *
     * @param string $fax
     * @return self
     */
    public function setFax($fax)
    {
        $this->fax = $fax;
        return $this;
    }

    /**
     * Get fax
     *
     * @return string $fax
     */
    public function getFax()
    {
        return str_replace(' ', '.', $this->fax);
    }

    /**
     * Set mail
     *
     * @param string $mail
     * @return self
     */
    public function setMail($mail)
    {
        $this->mail = $mail;
        return $this;
    }

    /**
     * Get mail
     *
     * @return string $mail
     */
    public function getMail()
    {
        return $this->mail;
    }
}
