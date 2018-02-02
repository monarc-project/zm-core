<?php
/**
 * @link      https://github.com/monarc-project for the canonical source repository
 * @copyright Copyright (c) Cases is a registered trademark of SECURITYMADEIN.LU
 * @license   MyCases is licensed under the GNU Affero GPL v3 - See license.txt for more information
 */

namespace MonarcCore\Model\Entity;

use Doctrine\ORM\Mapping as ORM;

/**
 * InstanceRisk
 *
 * @ORM\Table(name="instances_risks", indexes={
 *      @ORM\Index(name="anr", columns={"anr_id"}),
 *      @ORM\Index(name="amv_id", columns={"amv_id"}),
 *      @ORM\Index(name="asset_id", columns={"asset_id"}),
 *      @ORM\Index(name="threat_id", columns={"threat_id"}),
 *      @ORM\Index(name="vulnerability_id", columns={"vulnerability_id"}),
 *      @ORM\Index(name="instance_id", columns={"instance_id"})
 * })
 * @ORM\MappedSuperclass
 */
class InstanceRiskSuperClass extends AbstractEntity
{
    const KIND_REDUCTION = 1;
    const KIND_REFUS = 2;
    const KIND_ACCEPTATION = 3;
    const KIND_PARTAGE = 4;
    const KIND_NOT_TREATED = 5;

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
     *   @ORM\JoinColumn(name="anr_id", referencedColumnName="id", nullable=true, onDelete="CASCADE")
     * })
     */
    protected $anr;

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
     * @var \MonarcCore\Model\Entity\Instance
     *
     * @ORM\ManyToOne(targetEntity="MonarcCore\Model\Entity\Instance", cascade={"persist"})
     * @ORM\JoinColumns({
     *   @ORM\JoinColumn(name="instance_id", referencedColumnName="id", nullable=true, onDelete="CASCADE")
     * })
     */
    protected $instance;

    /**
     * @var smallint
     *
     * @ORM\Column(name="`specific`", type="smallint", options={"unsigned":true, "default":0})
     */
    protected $specific = '0';

    /**
     * @var smallint
     *
     * @ORM\Column(name="mh", type="smallint", options={"unsigned":true, "default":1})
     */
    protected $mh = '1';

    /**
     * @var smallint
     *
     * @ORM\Column(name="threat_rate", type="smallint", options={"unsigned":false, "default":-1})
     */
    protected $threatRate = '-1';

    /**
     * @var smallint
     *
     * @ORM\Column(name="vulnerability_rate", type="smallint", options={"unsigned":false, "default":-1})
     */
    protected $vulnerabilityRate = '-1';

    /**
     * @var smallint
     *
     * @ORM\Column(name="kind_of_measure", type="smallint", options={"unsigned":true, "default":5})
     */
    protected $kindOfMeasure = 5;

    /**
     * @var smallint
     *
     * @ORM\Column(name="reduction_amount", type="smallint", options={"unsigned":true, "default":0})
     */
    protected $reductionAmount = '0';

    /**
     * @var text
     *
     * @ORM\Column(name="comment", type="text", length=255, nullable=true)
     */
    protected $comment;

    /**
     * @var text
     *
     * @ORM\Column(name="comment_after", type="text", length=255, nullable=true)
     */
    protected $commentAfter;

    /**
     * @var smallint
     *
     * @ORM\Column(name="risk_c", type="smallint", options={"unsigned":false, "default":-1})
     */
    protected $riskC = '-1';

    /**
     * @var smallint
     *
     * @ORM\Column(name="risk_i", type="smallint", options={"unsigned":false, "default":-1})
     */
    protected $riskI = '-1';

    /**
     * @var smallint
     *
     * @ORM\Column(name="risk_d", type="smallint", options={"unsigned":false, "default":-1})
     */
    protected $riskD = '-1';

    /**
     * @var smallint
     *
     * @ORM\Column(name="cache_max_risk", type="smallint", options={"unsigned":false, "default":-1})
     */
    protected $cacheMaxRisk = '-1';

    /**
     * @var smallint
     *
     * @ORM\Column(name="cache_targeted_risk", type="smallint", options={"unsigned":false, "default":-1})
     */
    protected $cacheTargetedRisk = '-1';

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
     * @return Asset
     */
    public function getAsset()
    {
        return $this->asset;
    }

    /**
     * @param Asset $asset
     * @return Instance
     */
    public function setAsset($asset)
    {
        $this->asset = $asset;
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
     * @return Instance
     */
    public function setObject($object)
    {
        $this->object = $object;
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
     * @return InstanceRisk
     */
    public function setAmv($amv)
    {
        $this->amv = $amv;
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
     * @return InstanceRisk
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
     * @return InstanceRisk
     */
    public function setVulnerability($vulnerability)
    {
        $this->vulnerability = $vulnerability;
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
     * @return InstanceRisk
     */
    public function setInstance($instance)
    {
        $this->instance = $instance;
        return $this;
    }

    public function getInputFilter($partial = false)
    {
        if (!$this->inputFilter) {
            parent::getInputFilter($partial);

            $integers = [
                'specific',
                'mh',
                'threatRate',
                'vulnerabilityRate',
                'kindOfMeasure',
                'reductionAmount',
                'riskC',
                'riskI',
                'riskD',
                'cacheMaxRisk',
                'cacheTargetedRisk',
            ];
            foreach ($integers as $i) {
                $this->inputFilter->add(array(
                    'name' => $i,
                    'required' => false,
                    'allow_empty' => false,
                    'filters' => array(),
                    'validators' => array(
                        array(
                            'name' => 'IsInt',
                        ),
                    ),
                ));
            }
        }
        return $this->inputFilter;
    }
}
