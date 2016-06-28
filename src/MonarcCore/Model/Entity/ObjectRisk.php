<?php

namespace MonarcCore\Model\Entity;

use Doctrine\ORM\Mapping as ORM;

/**
 * Object Risks
 *
 * @ORM\Table(name="objects_risks")
 * @ORM\Entity
 */
class ObjectRisk extends AbstractEntity
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
     * @var integer
     *
     * @ORM\Column(name="anr_id", type="integer", nullable=true)
     */
    protected $anr;

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
     * @var \MonarcCore\Model\Entity\Amv
     *
     * @ORM\ManyToOne(targetEntity="MonarcCore\Model\Entity\Amv", cascade={"persist"})
     * @ORM\JoinColumns({
     *   @ORM\JoinColumn(name="amv_id", referencedColumnName="id", nullable=true)
     * })
     */
    protected $amv;

    /**
     * @var \MonarcCore\Model\Entity\Asset
     *
     * @ORM\ManyToOne(targetEntity="MonarcCore\Model\Entity\Asset", cascade={"persist"})
     * @ORM\JoinColumns({
     *   @ORM\JoinColumn(name="asset_id", referencedColumnName="id", nullable=true)
     * })
     */
    protected $asset;

    /**
     * @var \MonarcCore\Model\Entity\Threat
     *
     * @ORM\ManyToOne(targetEntity="MonarcCore\Model\Entity\Threat", cascade={"persist"})
     * @ORM\JoinColumns({
     *   @ORM\JoinColumn(name="threat_id", referencedColumnName="id", nullable=true)
     * })
     */
    protected $threat;

    /**
     * @var \MonarcCore\Model\Entity\Vulnerability
     *
     * @ORM\ManyToOne(targetEntity="MonarcCore\Model\Entity\Vulnerability", cascade={"persist"})
     * @ORM\JoinColumns({
     *   @ORM\JoinColumn(name="vulnerability_id", referencedColumnName="id", nullable=true)
     * })
     */
    protected $vulnerability;

    /**
     * @var smallint
     *
     * @ORM\Column(name="`specific`", type="smallint", options={"unsigned":true, "default":0})
     */
    protected $specific = 0;

    /**
     * @var smallint
     *
     * @ORM\Column(name="mh", type="smallint", options={"unsigned":true, "default":1})
     */
    protected $mh = 1;

    /**
     * @var integer
     *
     * @ORM\Column(name="threat_rate", type="integer", options={"default":-1})
     */
    protected $threatRate = -1;

    /**
     * @var integer
     *
     * @ORM\Column(name="vulnerability_rate", type="integer", options={"default":-1})
     */
    protected $vulnerabilityRate = -1;

    /**
     * @var smallint
     *
     * @ORM\Column(name="kind_of_measure", type="smallint", nullable=true, options={"default":0})
     */
    protected $kindOfMeasure = 0;

    /**
     * @var integer
     *
     * @ORM\Column(name="reduction_amount", type="integer", nullable=true, options={"default":0})
     */
    protected $reductionAmount = 0;

    /**
     * @var text
     *
     * @ORM\Column(name="comment", type="text", nullable=true)
     */
    protected $comment;

    /**
     * @var integer
     *
     * @ORM\Column(name="risk_c", type="integer", nullable=true, options={"default":-1})
     */
    protected $riskC = -1;

    /**
     * @var integer
     *
     * @ORM\Column(name="risk_i", type="integer", nullable=true, options={"default":-1})
     */
    protected $riskI = -1;

    /**
     * @var integer
     *
     * @ORM\Column(name="risk_d", type="integer", nullable=true, options={"default":-1})
     */
    protected $riskD = -1;

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
     * @return Model
     */
    public function setId($id)
    {
        $this->id = $id;
        return $this;
    }

    /**
     * @return array
     */
    public function getParameters()
    {
        return $this->parameters;
    }

    /**
     * @param array $parameters
     * @return AbstractEntity
     */
    public function setParameters($parameters)
    {
        $this->parameters = $parameters;
        return $this;
    }

    /**
     * @return Amv
     */
    public function getAmv()
    {
        return $this->amv;
    }

    /**
     * @param Amv $amv
     * @return ObjectRisk
     */
    public function setAmv($amv)
    {
        $this->amv = $amv;
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
     * @return ObjectRisk
     */
    public function setObject($object)
    {
        $this->object = $object;
        return $this;
    }

    /**
     * @return Asset
     */
    public function getAsset()
    {
        return $this->asset;
    }

    /**
     * @param Asset $asset
     * @return ObjectRisk
     */
    public function setAsset($asset)
    {
        $this->asset = $asset;
        return $this;
    }

    /**
     * @return Threat
     */
    public function getThreat()
    {
        return $this->threat;
    }

    /**
     * @param Threat $threat
     * @return ObjectRisk
     */
    public function setThreat($threat)
    {
        $this->threat = $threat;
        return $this;
    }

    /**
     * @return Vulnerability
     */
    public function getVulnerability()
    {
        return $this->vulnerability;
    }

    /**
     * @param Vulnerability $vulnerability
     * @return ObjectRisk
     */
    public function setVulnerability($vulnerability)
    {
        $this->vulnerability = $vulnerability;
        return $this;
    }

    public function getInputFilter($patch = false){
        if (!$this->inputFilter) {
            parent::getInputFilter($patch);

            $dependencies = [
                'object', 'amv', 'asset', 'threat',
                'mh', 'threat_rate', 'vulnerabilityRate', 'kindOfMeasure', 'reductionAmount',
                'riskC', 'riskD', 'riskI'
            ];

            foreach($dependencies as $dependency) {
                $this->inputFilter->add(array(
                    'name' => $dependency,
                    'required' => false,
                    'allow_empty' => false,
                    'filters' => array(),
                    'validators' => array(
                        array(
                            'name' => 'IsInt',
                            'options' => array(
                                'allow_white_space' => true,
                            )
                        ),
                    ),
                ));
            }

        }
        return $this->inputFilter;
    }
}

