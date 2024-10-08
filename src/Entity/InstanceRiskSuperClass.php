<?php declare(strict_types=1);
/**
 * @link      https://github.com/monarc-project for the canonical source repository
 * @copyright Copyright (c) 2016-2023 Luxembourg House of Cybersecurity LHC.lu - Licensed under GNU Affero GPL v3
 * @license   MONARC is licensed under GNU Affero General Public License version 3
 */

namespace Monarc\Core\Entity;

use Doctrine\ORM\Mapping as ORM;
use Monarc\Core\Entity\Traits\CreateEntityTrait;
use Monarc\Core\Entity\Traits\UpdateEntityTrait;

/**
 * @ORM\Table(name="instances_risks", indexes={
 *      @ORM\Index(name="anr", columns={"anr_id"}),
 *      @ORM\Index(name="amv_id", columns={"amv_id"}),
 *      @ORM\Index(name="asset_id", columns={"asset_id"}),
 *      @ORM\Index(name="threat_id", columns={"threat_id"}),
 *      @ORM\Index(name="vulnerability_id", columns={"vulnerability_id"}),
 *      @ORM\Index(name="instance_id", columns={"instance_id"})
 * })
 * @ORM\MappedSuperclass
 * @ORM\HasLifecycleCallbacks()
 */
class InstanceRiskSuperClass
{
    use CreateEntityTrait;
    use UpdateEntityTrait;

    public const KIND_NOT_SET = 0;
    public const KIND_REDUCTION = 1;
    public const KIND_REFUSED = 2;
    public const KIND_ACCEPTATION = 3;
    public const KIND_SHARED = 4;
    public const KIND_NOT_TREATED = 5;

    public const TYPE_SPECIFIC = 1;

    /**
     * @var int
     *
     * @ORM\Column(name="id", type="integer", nullable=false)
     * @ORM\Id
     * @ORM\GeneratedValue(strategy="IDENTITY")
     */
    protected $id;

    /**
     * @var AmvSuperClass
     *
     * @ORM\ManyToOne(targetEntity="Amv")
     * @ORM\JoinColumns({
     *   @ORM\JoinColumn(name="amv_id", referencedColumnName="uuid", nullable=true)
     * })
     */
    protected $amv;

    /**
     * @var AssetSuperClass
     *
     * @ORM\ManyToOne(targetEntity="Asset")
     * @ORM\JoinColumns({
     *   @ORM\JoinColumn(name="asset_id", referencedColumnName="uuid", nullable=true)
     * })
     */
    protected $asset;

    /**
     * @var ThreatSuperClass
     *
     * @ORM\ManyToOne(targetEntity="Threat")
     * @ORM\JoinColumns({
     *   @ORM\JoinColumn(name="threat_id", referencedColumnName="uuid", nullable=true)
     * })
     */
    protected $threat;

    /**
     * @var VulnerabilitySuperClass
     *
     * @ORM\ManyToOne(targetEntity="Vulnerability")
     * @ORM\JoinColumns({
     *   @ORM\JoinColumn(name="vulnerability_id", referencedColumnName="uuid", nullable=true)
     * })
     */
    protected $vulnerability;

    /**
     * @var InstanceSuperClass
     *
     * @ORM\ManyToOne(targetEntity="Instance")
     * @ORM\JoinColumns({
     *   @ORM\JoinColumn(name="instance_id", referencedColumnName="id", nullable=true, onDelete="CASCADE")
     * })
     */
    protected $instance;

    /**
     * NOTE. The anr relation has to be on the last position to avoid setting null when amv is set to null.
     * Doctrine's double field relation issue.
     *
     * @var AnrSuperClass
     *
     * @ORM\ManyToOne(targetEntity="Anr")
     * @ORM\JoinColumns({
     *   @ORM\JoinColumn(name="anr_id", referencedColumnName="id", nullable=true, onDelete="CASCADE")
     * })
     */
    protected $anr;

    /**
     * @var int
     *
     * @ORM\Column(name="`specific`", type="smallint", options={"unsigned":true, "default":0})
     */
    protected $specific = 0;

    /**
     * The field is to mark if the threatRate value is not set or is modified externally.
     * 1 - means that the threatRate values is not set yet manually, but could be set from the "Threats assessment" menu
     * for all the instance risks that are composed with the threat.
     * 0 - means the value is set manually on the risks table.
     *
     * @var int
     *
     * @ORM\Column(name="mh", type="smallint", options={"unsigned":true, "default":1})
     */
    protected $isThreatRateNotSetOrModifiedExternally = 1;

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
    protected $riskConfidentiality = -1;

    /**
     * @var int
     *
     * @ORM\Column(name="risk_i", type="smallint", options={"unsigned":false, "default":-1})
     */
    protected $riskIntegrity = -1;

    /**
     * @var int
     *
     * @ORM\Column(name="risk_d", type="smallint", options={"unsigned":false, "default":-1})
     */
    protected $riskAvailability = -1;

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

    public static function constructFromObject(InstanceRiskSuperClass $sourceInstanceRisk): InstanceRiskSuperClass
    {
        return (new static())
            ->setSpecific($sourceInstanceRisk->getSpecific())
            ->setIsThreatRateNotSetOrModifiedExternally($sourceInstanceRisk->IsThreatRateNotSetOrModifiedExternally())
            ->setThreatRate($sourceInstanceRisk->getThreatRate())
            ->setVulnerabilityRate($sourceInstanceRisk->getVulnerabilityRate())
            ->setKindOfMeasure($sourceInstanceRisk->getKindOfMeasure())
            ->setReductionAmount($sourceInstanceRisk->getReductionAmount())
            ->setComment($sourceInstanceRisk->getComment())
            ->setCommentAfter($sourceInstanceRisk->getCommentAfter())
            ->setRiskConfidentiality($sourceInstanceRisk->getRiskConfidentiality())
            ->setRiskIntegrity($sourceInstanceRisk->getRiskIntegrity())
            ->setRiskAvailability($sourceInstanceRisk->getRiskAvailability())
            ->setCacheMaxRisk($sourceInstanceRisk->getCacheMaxRisk())
            ->setCacheTargetedRisk($sourceInstanceRisk->getCacheTargetedRisk());
    }

    public function getId()
    {
        return $this->id;
    }

    public function getAnr(): AnrSuperClass
    {
        return $this->anr;
    }

    public function setAnr(AnrSuperClass $anr): self
    {
        $this->anr = $anr;

        return $this;
    }

    public function getAsset(): AssetSuperClass
    {
        return $this->asset;
    }

    public function setAsset(AssetSuperClass $asset): self
    {
        $this->asset = $asset;

        return $this;
    }

    public function getAmv(): ?AmvSuperClass
    {
        return $this->amv;
    }

    public function setAmv(?AmvSuperClass $amv): self
    {
        if ($amv !== null) {
            $amv->addInstanceRisk($this);
        } elseif ($this->amv !== null) {
            $this->amv->removeInstanceRisk($this);
        }
        $this->amv = $amv;

        return $this;
    }

    public function getThreat(): ThreatSuperClass
    {
        return $this->threat;
    }

    public function setThreat(ThreatSuperClass $threat): self
    {
        $this->threat = $threat;

        return $this;
    }

    public function getVulnerability(): VulnerabilitySuperClass
    {
        return $this->vulnerability;
    }

    public function setVulnerability(VulnerabilitySuperClass $vulnerability): self
    {
        $this->vulnerability = $vulnerability;

        return $this;
    }

    public function getInstance(): InstanceSuperClass
    {
        return $this->instance;
    }

    public function setInstance(InstanceSuperClass $instance): self
    {
        $this->instance = $instance;
        $this->instance->addInstanceRisk($this);

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
        return (int)$this->riskConfidentiality;
    }

    public function setRiskConfidentiality(int $riskConfidentiality): InstanceRiskSuperClass
    {
        $this->riskConfidentiality = $riskConfidentiality;

        return $this;
    }

    public function getRiskIntegrity(): int
    {
        return (int)$this->riskIntegrity;
    }

    public function setRiskIntegrity(int $riskIntegrity): InstanceRiskSuperClass
    {
        $this->riskIntegrity = $riskIntegrity;

        return $this;
    }

    public function getRiskAvailability(): int
    {
        return (int)$this->riskAvailability;
    }

    public function setRiskAvailability(int $riskAvailability): InstanceRiskSuperClass
    {
        $this->riskAvailability = $riskAvailability;

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

    public function setIsThreatRateNotSetOrModifiedExternally(bool $isThreatRateNotSetOrModifiedExternally): self
    {
        $this->isThreatRateNotSetOrModifiedExternally = (int)$isThreatRateNotSetOrModifiedExternally;

        return $this;
    }

    public function IsThreatRateNotSetOrModifiedExternally(): bool
    {
        return (bool)$this->isThreatRateNotSetOrModifiedExternally;
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
            self::KIND_REFUSED => 'Denied',
            self::KIND_ACCEPTATION => 'Accepted',
            self::KIND_SHARED => 'Shared',
            self::KIND_NOT_TREATED => 'Not treated',
        ];
    }

    public function isTreated(): bool
    {
        return !\in_array($this->kindOfMeasure, [self::KIND_NOT_TREATED, self::KIND_NOT_SET], true);
    }

    public function getTreatmentName(): string
    {
        return static::getTreatmentNameByType($this->kindOfMeasure);
    }

    public static function getTreatmentNameByType(int $treatmentType): string
    {
        return match ($treatmentType) {
            static::KIND_REDUCTION => 'Reduction',
            static::KIND_REFUSED => 'Denied',
            static::KIND_ACCEPTATION => 'Accepted',
            static::KIND_SHARED => 'Shared',
            default => 'Not treated',
        };
    }

    public function getTreatmentServiceName(): string
    {
        return match ($this->kindOfMeasure) {
            static::KIND_REDUCTION => 'reduction',
            static::KIND_REFUSED => 'denied',
            static::KIND_ACCEPTATION => 'accepted',
            static::KIND_SHARED => 'shared',
            default => 'not_treated',
        };
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
}
