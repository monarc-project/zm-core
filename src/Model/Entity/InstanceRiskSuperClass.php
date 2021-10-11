<?php
/**
 * @link      https://github.com/monarc-project for the canonical source repository
 * @copyright Copyright (c) 2016-2020 SMILE GIE Securitymadein.lu - Licensed under GNU Affero GPL v3
 * @license   MONARC is licensed under GNU Affero General Public License version 3
 */

namespace Monarc\Core\Model\Entity;

use Doctrine\ORM\Mapping as ORM;
use Monarc\Core\Model\Entity\Traits\CreateEntityTrait;
use Monarc\Core\Model\Entity\Traits\UpdateEntityTrait;

/**
 * InstanceRisk
 *
 * @ORM\Table(name="instances_risks", indexes={
 *      @ORM\Index(name="anr", columns={"anr_id"}),
 *      @ORM\Index(name="amv_id", columns={"amv_id"}),
 *      @ORM\Index(name="asset_id", columns={"asset_id"}),
 *      @ORM\Index(name="threat_id", columns={"threat_id"}),
 *      @ORM\Index(name="vulnerability_id", columns={"vulnerability_id"}),
 *      @ORM\Index(name="instance_id", columns={"instance_id"}),
 *      @ORM\Index(name="owner_id", columns={"owner_id"})
 * })
 * @ORM\MappedSuperclass
 * @ORM\HasLifecycleCallbacks()
 */
class InstanceRiskSuperClass extends AbstractEntity
{
    use CreateEntityTrait;
    use UpdateEntityTrait;

    const KIND_NOT_SET = 0;
    const KIND_REDUCTION = 1;
    const KIND_REFUS = 2;
    const KIND_ACCEPTATION = 3;
    const KIND_PARTAGE = 4;
    const KIND_NOT_TREATED = 5;

    public const TYPE_SPECIFIC = 1;
    public const TYPE_NOT_SPECIFIC = 0;

    /**
     * @var int
     *
     * @ORM\Column(name="id", type="integer", nullable=false)
     * @ORM\Id
     * @ORM\GeneratedValue(strategy="IDENTITY")
     */
    protected $id;

    /**
     * @var AnrSuperClass
     *
     * @ORM\ManyToOne(targetEntity="Anr", cascade={"persist"})
     * @ORM\JoinColumns({
     *   @ORM\JoinColumn(name="anr_id", referencedColumnName="id", nullable=true, onDelete="CASCADE")
     * })
     */
    protected $anr;

    /**
     * @var AmvSuperClass
     *
     * @ORM\ManyToOne(targetEntity="Amv", cascade={"persist"})
     * @ORM\JoinColumns({
     *   @ORM\JoinColumn(name="amv_id", referencedColumnName="uuid", nullable=true)
     * })
     */
    protected $amv;

    /**
     * @var AssetSuperClass
     *
     * @ORM\ManyToOne(targetEntity="Asset", cascade={"persist"})
     * @ORM\JoinColumns({
     *   @ORM\JoinColumn(name="asset_id", referencedColumnName="uuid", nullable=true)
     * })
     */
    protected $asset;

    /**
     * @var ThreatSuperClass
     *
     * @ORM\ManyToOne(targetEntity="Threat", cascade={"persist"})
     * @ORM\JoinColumns({
     *   @ORM\JoinColumn(name="threat_id", referencedColumnName="uuid", nullable=true)
     * })
     */
    protected $threat;

    /**
     * @var VulnerabilitySuperClass
     *
     * @ORM\ManyToOne(targetEntity="Vulnerability", cascade={"persist"})
     * @ORM\JoinColumns({
     *   @ORM\JoinColumn(name="vulnerability_id", referencedColumnName="uuid", nullable=true)
     * })
     */
    protected $vulnerability;

    /**
     * @var InstanceSuperClass
     *
     * @ORM\ManyToOne(targetEntity="Instance", cascade={"persist"})
     * @ORM\JoinColumns({
     *   @ORM\JoinColumn(name="instance_id", referencedColumnName="id", nullable=true, onDelete="CASCADE")
     * })
     */
    protected $instance;

    /**
     * @var InstanceRiskOwnerSuperClass
     *
     * @ORM\ManyToOne(targetEntity="InstanceRiskOwner", cascade={"persist"})
     * @ORM\JoinColumns({
     *   @ORM\JoinColumn(name="owner_id", referencedColumnName="id", nullable=true)
     * })
     */
    protected $instanceRiskOwner;

    /**
     * @var string
     *
     * @ORM\Column(name="context", type="string", length=255, nullable=true)
     */
    protected $context;

    /**
     * @var int
     *
     * @ORM\Column(name="`specific`", type="smallint", options={"unsigned":true, "default":0})
     */
    protected $specific = 0;

    /**
     * @var int
     *
     * @ORM\Column(name="mh", type="smallint", options={"unsigned":true, "default":1})
     */
    protected $mh = 1;

    /**
     * @var int
     *
     * @ORM\Column(name="threat_rate", type="smallint", options={"unsigned":false, "default":-1})
     */
    protected $threatRate = -1;

    /**
     * @var int
     *
     * @ORM\Column(name="vulnerability_rate", type="smallint", options={"unsigned":false, "default":-1})
     */
    protected $vulnerabilityRate = -1;

    /**
     * @var int
     *
     * @ORM\Column(name="kind_of_measure", type="smallint", options={"unsigned":true, "default":5})
     */
    protected $kindOfMeasure = 5;

    /**
     * @var int
     *
     * @ORM\Column(name="reduction_amount", type="smallint", options={"unsigned":true, "default":0})
     */
    protected $reductionAmount = 0;

    /**
     * @var string
     *
     * @ORM\Column(name="comment", type="text", nullable=true)
     */
    protected $comment;

    /**
     * @var string
     *
     * @ORM\Column(name="comment_after", type="text", nullable=true)
     */
    protected $commentAfter;

    /**
     * @var int
     *
     * @ORM\Column(name="risk_c", type="smallint", options={"unsigned":false, "default":-1})
     */
    protected $riskC = -1;

    /**
     * @var int
     *
     * @ORM\Column(name="risk_i", type="smallint", options={"unsigned":false, "default":-1})
     */
    protected $riskI = -1;

    /**
     * @var int
     *
     * @ORM\Column(name="risk_d", type="smallint", options={"unsigned":false, "default":-1})
     */
    protected $riskD = -1;

    /**
     * @var int
     *
     * @ORM\Column(name="cache_max_risk", type="smallint", options={"unsigned":false, "default":-1})
     */
    protected $cacheMaxRisk = -1;

    /**
     * @var int
     *
     * @ORM\Column(name="cache_targeted_risk", type="smallint", options={"unsigned":false, "default":-1})
     */
    protected $cacheTargetedRisk = -1;

    public function getId()
    {
        return $this->id;
    }

    public function setId($id): self
    {
        $this->id = $id;

        return $this;
    }

    // TODO: the nullable value is added for the multi-fields relation issue (when we remove a relation, e.g. amv).
    // TODO: remove when #240 is done.
    public function getAnr(): ?AnrSuperClass
    {
        return $this->anr;
    }

    public function setAnr(?AnrSuperClass $anr): self
    {
        $this->anr = $anr;

        return $this;
    }

    /**
     * @return AssetSuperClass
     */
    public function getAsset()
    {
        return $this->asset;
    }

    /**
     * @param AssetSuperClass $asset
     */
    public function setAsset($asset): self
    {
        $this->asset = $asset;

        return $this;
    }

    /**
     * @return AmvSuperClass
     */
    public function getAmv()
    {
        return $this->amv;
    }

    /**
     * @param AmvSuperClass $amv
     */
    public function setAmv($amv): self
    {
        $this->amv = $amv;

        return $this;
    }

    /**
     * @return ThreatSuperClass
     */
    public function getThreat()
    {
        return $this->threat;
    }

    /**
     * @param ThreatSuperClass $threat
     */
    public function setThreat($threat): self
    {
        $this->threat = $threat;

        return $this;
    }

    /**
     * @return VulnerabilitySuperClass
     */
    public function getVulnerability()
    {
        return $this->vulnerability;
    }

    /**
     * @param VulnerabilitySuperClass $vulnerability
     */
    public function setVulnerability($vulnerability): self
    {
        $this->vulnerability = $vulnerability;

        return $this;
    }

    public function getInstanceRiskOwner()
    {
        return $this->instanceRiskOwner;
    }

    public function setInstanceRiskOwner(?InstanceRiskOwnerSuperClass $instanceRiskOwner): self
    {
        if ($instanceRiskOwner === null) {
            if ($this->instanceRiskOwner !== null) {
                $this->instanceRiskOwner->removeInstanceRisk($this);
                $this->instanceRiskOwner = null;
            }
        } else {
            $this->instanceRiskOwner = $instanceRiskOwner;
            $instanceRiskOwner->addInstanceRisk($this);
        }

        return $this;
    }

    public function getContext(): string
    {
        return (string)$this->context;
    }

    public function setContext(string $context): self
    {
        $this->context = $context;

        return $this;
    }

    /**
     * @return InstanceSuperClass
     */
    public function getInstance()
    {
        return $this->instance;
    }

    /**
     * @param InstanceSuperClass $instance
     */
    public function setInstance($instance): self
    {
        $this->instance = $instance;

        return $this;
    }

    public function getSpecific(): int
    {
        return $this->specific;
    }

    public function setSpecific(int $specific): self
    {
        $this->specific = $specific;

        return $this;
    }

    public function isSpecific(): bool
    {
        return $this->specific === self::TYPE_SPECIFIC;
    }

    public function setThreatRate(int $threatRate): self
    {
        $this->threatRate = $threatRate;

        return $this;
    }

    public function getThreatRate(): int
    {
        return $this->threatRate;
    }

    public function setVulnerabilityRate(int $vulnerabilityRate): self
    {
        $this->vulnerabilityRate = $vulnerabilityRate;

        return $this;
    }

    public function getVulnerabilityRate(): int
    {
        return $this->vulnerabilityRate;
    }

    public function getRiskConfidentiality(): int
    {
        return (int)$this->riskC;
    }

    public function setRiskConfidentiality(int $riskC): InstanceRiskSuperClass
    {
        $this->riskC = $riskC;

        return $this;
    }

    public function getRiskIntegrity(): int
    {
        return (int)$this->riskI;
    }

    public function setRiskIntegrity(int $riskI): InstanceRiskSuperClass
    {
        $this->riskI = $riskI;

        return $this;
    }

    public function getRiskAvailability(): int
    {
        return (int)$this->riskD;
    }

    public function setRiskAvailability(int $riskD): InstanceRiskSuperClass
    {
        $this->riskD = $riskD;

        return $this;
    }

    public function setCacheMaxRisk(int $cacheMaxRisk): InstanceRiskSuperClass
    {
        $this->cacheMaxRisk = $cacheMaxRisk;

        return $this;
    }

    public function setCacheTargetedRisk(int $cacheTargetedRisk): InstanceRiskSuperClass
    {
        $this->cacheTargetedRisk = $cacheTargetedRisk;

        return $this;
    }

    public function getReductionAmount(): int
    {
        return $this->reductionAmount;
    }

    public function setReductionAmount(int $reductionAmount): InstanceRiskSuperClass
    {
        $this->reductionAmount = $reductionAmount;

        return $this;
    }

    public function isTreated(): bool
    {
        return $this->kindOfMeasure !== self::KIND_NOT_TREATED;
    }

    public function getTreatmentName(): string
    {
        switch ($this->kindOfMeasure) {
            case static::KIND_REDUCTION:
                return 'Reduction';
            case static::KIND_REFUS:
                return 'Denied';
            case static::KIND_ACCEPTATION:
                return 'Accepted';
            case static::KIND_PARTAGE:
                return 'Shared';
            default:
                return 'Not treated';
        }
    }

    public function setMh(int $mh): self
    {
        $this->mh = $mh;

        return $this;
    }

    public function getMh(): int
    {
        return (int)$this->mh;
    }

    public function getCacheMaxRisk(): int
    {
        return (int)$this->cacheMaxRisk;
    }

    public function getCacheTargetedRisk(): int
    {
        return (int)$this->cacheTargetedRisk;
    }

    public function setComment(string $comment): self
    {
        $this->comment = $comment;

        return $this;
    }

    public function getComment(): string
    {
        return (string)$this->comment;
    }

    public function getKindOfMeasure(): int
    {
        return $this->kindOfMeasure;
    }

    public function setKindOfMeasure(int $kindOfMeasure): self
    {
        $this->kindOfMeasure = $kindOfMeasure;

        return $this;
    }

    public static function getAvailableMeasureTypes(): array
    {
        return [
            self::KIND_NOT_SET => 'Not treated',
            self::KIND_REDUCTION => 'Reduction',
            self::KIND_REFUS => 'Denied',
            self::KIND_ACCEPTATION => 'Accepted',
            self::KIND_PARTAGE => 'Shared',
            self::KIND_NOT_TREATED => 'Not treated',
        ];
    }

    public function getCommentAfter(): string
    {
        return (string)$this->commentAfter;
    }

    public function setCommentAfter(string $commentAfter): self
    {
        $this->commentAfter = $commentAfter;

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

    public function getFiltersForService(){
        $filterJoin = [
            [
                'as' => 'th',
                'rel' => 'threat',
            ],
            [
                'as' => 'v',
                'rel' => 'vulnerability',
            ],
            [
                'as' => 'i',
                'rel' => 'instance',
            ],
        ];
        $filterLeft = [
            [
                'as' => 'th1',
                'rel' => 'threat',
            ],
            [
                'as' => 'v1',
                'rel' => 'vulnerability',
            ],

        ];
        $filtersCol = [

        ];
        return [$filterJoin,$filterLeft,$filtersCol];
    }
}
