<?php declare(strict_types=1);
/**
 * @link      https://github.com/monarc-project for the canonical source repository
 * @copyright Copyright (c) 2016-2023 Luxembourg House of Cybersecurity LHC.lu - Licensed under GNU Affero GPL v3
 * @license   MONARC is licensed under GNU Affero General Public License version 3
 */

namespace Monarc\Core\Entity;

use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\ORM\Mapping as ORM;
use Monarc\Core\Entity\Traits\CreateEntityTrait;
use Monarc\Core\Entity\Traits\UpdateEntityTrait;

/**
 * @ORM\Table(name="instances_risks_op", indexes={
 *      @ORM\Index(name="anr_id", columns={"anr_id"}),
 *      @ORM\Index(name="instance_id", columns={"instance_id"}),
 *      @ORM\Index(name="object_id", columns={"object_id"}),
 *      @ORM\Index(name="rolf_risk_id", columns={"rolf_risk_id"})
 * })
 * @ORM\MappedSuperclass
 * @ORM\HasLifecycleCallbacks()
 */
class InstanceRiskOpSuperClass
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
     * @var AnrSuperClass
     *
     * @ORM\ManyToOne(targetEntity="Anr")
     * @ORM\JoinColumns({
     *   @ORM\JoinColumn(name="anr_id", referencedColumnName="id", nullable=true, onDelete="CASCADE")
     * })
     */
    protected $anr;

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
     * @var ObjectSuperClass
     *
     * @ORM\ManyToOne(targetEntity="MonarcObject")
     * @ORM\JoinColumns({
     *   @ORM\JoinColumn(name="object_id", referencedColumnName="uuid", nullable=true)
     * })
     */
    protected $object;

    /**
     * @var RolfRiskSuperClass|null
     *
     * @ORM\ManyToOne(targetEntity="RolfRisk")
     * @ORM\JoinColumns({
     *   @ORM\JoinColumn(name="rolf_risk_id", referencedColumnName="id", nullable=true, onDelete="SET NULL")
     * })
     */
    protected $rolfRisk;

    /**
     * @var OperationalInstanceRiskScaleSuperClass[]|ArrayCollection
     *
     * @ORM\OneToMany(targetEntity="OperationalInstanceRiskScale", mappedBy="operationalInstanceRisk")
     */
    protected $operationalInstanceRiskScales;

    /**
     * @var string
     *
     * @ORM\Column(name="risk_cache_code", type="string", length=255, nullable=true)
     */
    protected $riskCacheCode;

    /**
     * @var string
     *
     * @ORM\Column(name="risk_cache_label1", type="string", length=255, nullable=true)
     */
    protected $riskCacheLabel1;

    /**
     * @var string
     *
     * @ORM\Column(name="risk_cache_label2", type="string", length=255, nullable=true)
     */
    protected $riskCacheLabel2;

    /**
     * @var string
     *
     * @ORM\Column(name="risk_cache_label3", type="string", length=255, nullable=true)
     */
    protected $riskCacheLabel3;

    /**
     * @var string
     *
     * @ORM\Column(name="risk_cache_label4", type="string", length=255, nullable=true)
     */
    protected $riskCacheLabel4;

    /**
     * @var string
     *
     * @ORM\Column(name="risk_cache_description1", type="text", nullable=true)
     */
    protected $riskCacheDescription1;

    /**
     * @var string
     *
     * @ORM\Column(name="risk_cache_description2", type="text", nullable=true)
     */
    protected $riskCacheDescription2;

    /**
     * @var string
     *
     * @ORM\Column(name="risk_cache_description3", type="text", nullable=true)
     */
    protected $riskCacheDescription3;

    /**
     * @var string
     *
     * @ORM\Column(name="risk_cache_description4", type="text", nullable=true)
     */
    protected $riskCacheDescription4;

    /**
     * @var int
     *
     * @ORM\Column(name="brut_prob", type="smallint", options={"unsigned":false, "default":-1})
     */
    protected $brutProb = -1;

    /**
     * @var int
     *
     * @ORM\Column(name="net_prob", type="smallint", options={"unsigned":false, "default":-1})
     */
    protected $netProb = -1;

    /**
     * @var int
     *
     * @ORM\Column(name="targeted_prob", type="smallint", options={"unsigned":false, "default":-1})
     */
    protected $targetedProb = -1;

    /**
     * @var int
     *
     * @ORM\Column(name="cache_brut_risk", type="smallint", options={"unsigned":false, "default":-1})
     */
    protected $cacheBrutRisk = -1;

    /**
     * @var int
     *
     * @ORM\Column(name="cache_net_risk", type="smallint", options={"unsigned":false, "default":-1})
     */
    protected $cacheNetRisk = -1;

    /**
     * @var int
     *
     * @ORM\Column(name="cache_targeted_risk", type="smallint", options={"unsigned":false, "default":-1})
     */
    protected $cacheTargetedRisk = -1;

    /**
     * @var int
     *
     * @ORM\Column(name="kind_of_measure", type="smallint", options={"unsigned":true, "default":5})
     */
    protected $kindOfMeasure = 5;

    /**
     * @var string
     *
     * @ORM\Column(name="comment", type="text", nullable=true)
     */
    protected $comment;

    /**
     * @var string
     *
     * @ORM\Column(name="mitigation", type="text", nullable=true)
     */
    protected $mitigation;

    /**
     * @var int
     *
     * @ORM\Column(name="`specific`", type="smallint", options={"unsigned":true, "default":0})
     */
    protected $specific = 0;

    public function __construct()
    {
        $this->operationalInstanceRiskScales = new ArrayCollection();
    }

    public static function constructFromObject(
        InstanceRiskOpSuperClass $sourceOperationalInstanceRisk
    ): InstanceRiskOpSuperClass {
        return (new static())
            ->setRiskCacheCode($sourceOperationalInstanceRisk->getRiskCacheCode())
            ->setRiskCacheLabels($sourceOperationalInstanceRisk->getRiskCacheLabels())
            ->setRiskCacheDescriptions($sourceOperationalInstanceRisk->getRiskCacheDescriptions())
            ->setBrutProb($sourceOperationalInstanceRisk->getBrutProb())
            ->setNetProb($sourceOperationalInstanceRisk->getNetProb())
            ->setTargetedProb($sourceOperationalInstanceRisk->getTargetedProb())
            ->setRiskCacheCode($sourceOperationalInstanceRisk->getRiskCacheCode())
            ->setCacheBrutRisk($sourceOperationalInstanceRisk->getCacheBrutRisk())
            ->setCacheNetRisk($sourceOperationalInstanceRisk->getCacheNetRisk())
            ->setCacheTargetedRisk($sourceOperationalInstanceRisk->getCacheTargetedRisk())
            ->setKindOfMeasure($sourceOperationalInstanceRisk->getKindOfMeasure())
            ->setComment($sourceOperationalInstanceRisk->getComment())
            ->setMitigation($sourceOperationalInstanceRisk->getMitigation())
            ->setSpecific($sourceOperationalInstanceRisk->getSpecific());
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

    public function getInstance(): InstanceSuperClass
    {
        return $this->instance;
    }

    public function setInstance(InstanceSuperClass $instance): self
    {
        $this->instance = $instance;
        $instance->addOperationalInstanceRisk($this);

        return $this;
    }

    public function getObject()
    {
        return $this->object;
    }

    public function setObject(ObjectSuperClass $object): self
    {
        $this->object = $object;

        return $this;
    }

    public function getRolfRisk(): ?RolfRiskSuperClass
    {
        return $this->rolfRisk;
    }

    public function setRolfRisk(?RolfRiskSuperClass $rolfRisk): self
    {
        $this->rolfRisk = $rolfRisk;

        return $this;
    }

    public function getCacheBrutRisk(): int
    {
        return $this->cacheBrutRisk;
    }

    public function setCacheBrutRisk(int $cacheBrutRisk): self
    {
        $this->cacheBrutRisk = $cacheBrutRisk;

        return $this;
    }

    public function getCacheNetRisk(): int
    {
        return $this->cacheNetRisk;
    }

    public function setCacheNetRisk(int $cacheNetRisk): self
    {
        $this->cacheNetRisk = $cacheNetRisk;

        return $this;
    }

    public function getCacheTargetedRisk(): int
    {
        return $this->cacheTargetedRisk;
    }

    public function setCacheTargetedRisk(int $cacheTargetedRisk): self
    {
        $this->cacheTargetedRisk = $cacheTargetedRisk;

        return $this;
    }

    public function getComment(): string
    {
        return (string)$this->comment;
    }

    public function setComment(string $comment): self
    {
        $this->comment = $comment;

        return $this;
    }

    public function getRiskCacheCode(): ?string
    {
        return $this->riskCacheCode;
    }

    public function setRiskCacheCode(?string $riskCacheCode): self
    {
        $this->riskCacheCode = $riskCacheCode;

        return $this;
    }

    public function getKindOfMeasure(): int
    {
        return $this->kindOfMeasure;
    }

    public function setKindOfMeasure(int $kindOfMeasure): self
    {
        if (isset(self::getAvailableMeasureTypes()[$kindOfMeasure])) {
            $this->kindOfMeasure = $kindOfMeasure;
        }

        return $this;
    }

    public static function getAvailableMeasureTypes(): array
    {
        return [
            static::KIND_NOT_SET => 'Not treated',
            static::KIND_REDUCTION => 'Reduction',
            static::KIND_REFUSED => 'Denied',
            static::KIND_ACCEPTATION => 'Accepted',
            static::KIND_SHARED => 'Shared',
            static::KIND_NOT_TREATED => 'Not treated',
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

    public function getRiskCacheLabel(int $languageIndex): string
    {
        if (!\in_array($languageIndex, range(1, 4), true)) {
            return '';
        }

        return (string)$this->{'riskCacheLabel' . $languageIndex};
    }

    public function setRiskCacheLabels(array $labels): self
    {
        foreach (range(1, 4) as $index) {
            $key = 'riskCacheLabel' . $index;
            if (isset($labels[$key])) {
                $this->{$key} = $labels[$key];
            }
        }

        return $this;
    }

    public function getRiskCacheLabels(): array
    {
        return [
            'riskCacheLabel1' => $this->riskCacheLabel1,
            'riskCacheLabel2' => $this->riskCacheLabel2,
            'riskCacheLabel3' => $this->riskCacheLabel3,
            'riskCacheLabel4' => $this->riskCacheLabel4,
        ];
    }

    public function getRiskCacheDescription(int $languageIndex): string
    {
        if (!\in_array($languageIndex, range(1, 4), true)) {
            return '';
        }

        return (string)$this->{'riskCacheDescription' . $languageIndex};
    }

    public function setRiskCacheDescriptions(array $descriptions): self
    {
        foreach (range(1, 4) as $index) {
            $key = 'riskCacheDescription' . $index;
            if (isset($descriptions[$key])) {
                $this->{$key} = $descriptions[$key];
            }
        }

        return $this;
    }

    public function getRiskCacheDescriptions(): array
    {
        return [
            'riskCacheDescription1' => $this->riskCacheDescription1,
            'riskCacheDescription2' => $this->riskCacheDescription2,
            'riskCacheDescription3' => $this->riskCacheDescription3,
            'riskCacheDescription4' => $this->riskCacheDescription4,
        ];
    }

    public function getOperationalInstanceRiskScales()
    {
        return $this->operationalInstanceRiskScales;
    }

    public function addOperationalInstanceRiskScale(
        OperationalInstanceRiskScaleSuperClass $operationalInstanceRiskScale
    ): self {
        if (!$this->operationalInstanceRiskScales->contains($operationalInstanceRiskScale)) {
            $this->operationalInstanceRiskScales->add($operationalInstanceRiskScale);
            $operationalInstanceRiskScale->setOperationalInstanceRisk($this);
        }

        return $this;
    }

    public function isSpecific(): bool
    {
        return (bool)$this->specific;
    }

    public function setIsSpecific(bool $isSpecific): self
    {
        $this->specific = (int)$isSpecific;

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

    public function getBrutProb(): int
    {
        return $this->brutProb;
    }

    public function setBrutProb(int $brutProb): self
    {
        $this->brutProb = $brutProb;

        return $this;
    }

    public function getNetProb(): int
    {
        return $this->netProb;
    }

    public function setNetProb(int $netProb): self
    {
        $this->netProb = $netProb;

        return $this;
    }

    public function getTargetedProb(): int
    {
        return $this->targetedProb;
    }

    public function setTargetedProb(int $targetedProb): self
    {
        $this->targetedProb = $targetedProb;

        return $this;
    }

    public function getMitigation(): string
    {
        return (string)$this->mitigation;
    }

    public function setMitigation(string $mitigation): self
    {
        $this->mitigation = $mitigation;

        return $this;
    }
}
