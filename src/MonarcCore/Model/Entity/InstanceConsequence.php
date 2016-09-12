<?php

namespace MonarcCore\Model\Entity;

use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\ORM\Mapping as ORM;

/**
 * Instance Consequence
 *
 * @ORM\Table(name="instances_consequences")
 * @ORM\Entity
 */
class InstanceConsequence extends AbstractEntity
{
    /**
     * @var integer
     *
     * @ORM\Column(name="id", type="integer", nullable=false)
     * @ORM\Id
     * @ORM\GeneratedValue(strategy="IDENTITY")
     */
    protected $id;

    /**
     * @var \MonarcCore\Model\Entity\Anr
     *
     * @ORM\ManyToOne(targetEntity="MonarcCore\Model\Entity\Anr", cascade={"persist"})
     * @ORM\JoinColumns({
     *   @ORM\JoinColumn(name="anr_id", referencedColumnName="id", nullable=true)
     * })
     */
    protected $anr;

    /**
     * @var \MonarcCore\Model\Entity\Instance
     *
     * @ORM\ManyToOne(targetEntity="MonarcCore\Model\Entity\Instance", cascade={"persist"})
     * @ORM\JoinColumns({
     *   @ORM\JoinColumn(name="instance_id", referencedColumnName="id", nullable=true)
     * })
     */
    protected $instance;

    /**
     * @var \MonarcCore\Model\Entity\Object
     *
     * @ORM\ManyToOne(targetEntity="MonarcCore\Model\Entity\Object", cascade={"persist"})
     * @ORM\JoinColumns({
     *   @ORM\JoinColumn(name="object_id", referencedColumnName="id", nullable=true)
     * })
     */
    protected $object;

    /**
     * @var \MonarcCore\Model\Entity\ScaleImpactType
     *
     * @ORM\ManyToOne(targetEntity="MonarcCore\Model\Entity\ScaleImpactType", cascade={"persist"})
     * @ORM\JoinColumns({
     *   @ORM\JoinColumn(name="scale_impact_type_id", referencedColumnName="id", nullable=true)
     * })
     */
    protected $scaleImpactType;

    /**
     * @var smallint
     *
     * @ORM\Column(name="is_hidden", type="smallint", options={"unsigned":true, "default":0})
     */
    protected $isHidden = '0';

    /**
     * @var smallint
     *
     * @ORM\Column(name="locally_touched", type="smallint", options={"unsigned":true, "default":0})
     */
    protected $localyTouched = '0';

    /**
     * @var smallint
     *
     * @ORM\Column(name="c", type="smallint", options={"unsigned":true, "default":1})
     */
    protected $c = '1';

    /**
     * @var smallint
     *
     * @ORM\Column(name="i", type="smallint", options={"unsigned":true, "default":1})
     */
    protected $i = '1';

    /**
     * @var smallint
     *
     * @ORM\Column(name="d", type="smallint", options={"unsigned":true, "default":1})
     */
    protected $d = '1';

    /**
     * @var smallint
     *
     * @ORM\Column(name="ch", type="smallint", options={"unsigned":true, "default":0})
     */
    protected $ch = '0';

    /**
     * @var smallint
     *
     * @ORM\Column(name="ih", type="smallint", options={"unsigned":true, "default":0})
     */
    protected $ih = '0';

    /**
     * @var smallint
     *
     * @ORM\Column(name="dh", type="smallint", options={"unsigned":true, "default":0})
     */
    protected $dh = '0';

    /**
     * @var string
     *
     * @ORM\Column(name="creator", type="string", length=255, nullable=true)
     */
    protected $creator;

    /**
     * @var \DateTime
     *
     * @ORM\Column(name="created_at", type="datetime", nullable=true)
     */
    protected $createdAt;

    /**
     * @var string
     *
     * @ORM\Column(name="updater", type="string", length=255, nullable=true)
     */
    protected $updater;

    /**
     * @var \DateTime
     *
     * @ORM\Column(name="updated_at", type="datetime", nullable=true)
     */
    protected $updatedAt;

    /**
     * @return int
     */
    public function getId()
    {
        return $this->id;
    }

    /**
     * @param int $id
     * @return Instance
     */
    public function setId($id)
    {
        $this->id = $id;
        return $this;
    }

    /**
     * @return int
     */
    public function getAnr()
    {
        return $this->anr;
    }

    /**
     * @param int $anr
     * @return Instance
     */
    public function setAnr($anr)
    {
        $this->anr = $anr;
        return $this;
    }

    /**
     * @return Instance
     */
    public function getInstance()
    {
        return $this->instance;
    }

    /**
     * @param Instance $instance
     * @return InstanceConsequence
     */
    public function setInstance($instance)
    {
        $this->instance = $instance;
        return $this;
    }

    /**
     * @return Object
     */
    public function getObject()
    {
        return $this->object;
    }

    /**
     * @param Object $object
     * @return InstanceConsequence
     */
    public function setObject($object)
    {
        $this->object = $object;
        return $this;
    }

    /**
     * @return ScaleImpactType
     */
    public function getScaleImpactType()
    {
        return $this->scaleImpactType;
    }

    /**
     * @param ScaleImpactType $scaleImpactType
     * @return InstanceConsequence
     */
    public function setScaleImpactType($scaleImpactType)
    {
        $this->scaleImpactType = $scaleImpactType;
        return $this;
    }

    public function getInputFilter($partial = false){
        if (!$this->inputFilter) {
            parent::getInputFilter($partial);

            $fields = ['anr', 'instance', 'object', 'scaleImpactType'];
            foreach ($fields as $field) {
                $this->inputFilter->add(array(
                    'name' => $field,
                    'required' => true,
                    'allow_empty' => false,
                    'filters' => array(),
                    'validators' => array(),
                ));
            }
        }
        return $this->inputFilter;
    }
}

