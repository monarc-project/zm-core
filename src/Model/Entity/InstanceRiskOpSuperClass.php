<?php
/**
 * @link      https://github.com/monarc-project for the canonical source repository
 * @copyright Copyright (c) 2016-2020 SMILE GIE Securitymadein.lu - Licensed under GNU Affero GPL v3
 * @license   MONARC is licensed under GNU Affero General Public License version 3
 */

namespace Monarc\Core\Model\Entity;

use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\ORM\Mapping as ORM;
use Monarc\Core\Model\Entity\Traits\CreateEntityTrait;
use Monarc\Core\Model\Entity\Traits\UpdateEntityTrait;

/**
 * Instance Risk Op
 *
 * @ORM\Table(name="instances_risks_op", indexes={
 *      @ORM\Index(name="anr_id", columns={"anr_id"}),
 *      @ORM\Index(name="instance_id", columns={"instance_id"}),
 *      @ORM\Index(name="object_id", columns={"object_id"}),
 *      @ORM\Index(name="rolf_risk_id", columns={"rolf_risk_id"})
 * })
 * @ORM\MappedSuperclass
 * @ORM\HasLifecycleCallbacks()
 */
class InstanceRiskOpSuperClass extends AbstractEntity
{
    use CreateEntityTrait;
    use UpdateEntityTrait;

    const KIND_REDUCTION = 1;
    const KIND_REFUS = 2;
    const KIND_ACCEPTATION = 3;
    const KIND_PARTAGE = 4;
    const KIND_NOT_TREATED = 5;

    public const TYPE_SPECIFIC = 1;

    /**
     * @var integer
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
     * @var InstanceSuperClass
     *
     * @ORM\ManyToOne(targetEntity="Instance", cascade={"persist"})
     * @ORM\JoinColumns({
     *   @ORM\JoinColumn(name="instance_id", referencedColumnName="id", nullable=true, onDelete="CASCADE")
     * })
     */
    protected $instance;

    /**
     * @var string
     *
     * @ORM\Column(name="owner", type="string", length=255, nullable=true)
     */
    protected $owner;

    /**
     * @var string
     *
     * @ORM\Column(name="context", type="string", length=255, nullable=true)
     */
    protected $context;

    /**
     * @var ObjectSuperClass
     *
     * @ORM\ManyToOne(targetEntity="MonarcObject", cascade={"persist"})
     * @ORM\JoinColumns({
     *   @ORM\JoinColumn(name="object_id", referencedColumnName="uuid", nullable=true)
     * })
     */
    protected $object;

    /**
     * @var RolfRiskSuperClass
     *
     * @ORM\ManyToOne(targetEntity="RolfRisk", cascade={"persist"})
     * @ORM\JoinColumns({
     *   @ORM\JoinColumn(name="rolf_risk_id", referencedColumnName="id", nullable=true)
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
     * @var string
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

    public function __clone()
    {
        $this->id = null;
        $this->setCreatedAtValue();
    }

    /**
     * @return int
     */
    public function getId()
    {
        return $this->id;
    }

    /**
     * @param int $id
     * @return self
     */
    public function setId($id): self
    {
        $this->id = $id;

        return $this;
    }

    /**
     * @return AnrSuperClass
     */
    public function getAnr()
    {
        return $this->anr;
    }

    /**
     * @param AnrSuperClass $anr
     * @return self
     */
    public function setAnr($anr): self
    {
        $this->anr = $anr;

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
     * @return self
     */
    public function setInstance($instance): self
    {
        $this->instance = $instance;

        return $this;
    }

    public function getOwner(): string
    {
        return $this->owner;
    }

    public function setOwner(string $owner): self
    {
        $this->owner = $owner;

        return $this;
    }

    public function getContext(): string
    {
        return $this->context;
    }

    /**
     * @return ObjectSuperClass
     */
    public function getObject()
    {
        return $this->object;
    }

    /**
     * @param ObjectSuperClass $object
     * @return self
     */
    public function setObject($object): self
    {
        $this->object = $object;

        return $this;
    }

    /**
     * @return RolfRiskSuperClass
     */
    public function getRolfRisk()
    {
        return $this->rolfRisk;
    }

    /**
     * @param RolfRiskSuperClass $rolfRisk
     * @return self
     */
    public function setRolfRisk($rolfRisk): self
    {
        $this->rolfRisk = $rolfRisk;

        return $this;
    }

    public function isSpecific(): bool
    {
        return $this->specific === self::TYPE_SPECIFIC;
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

    public function setRiskCacheCode(string $riskCacheCode): self
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
        if (\in_array($kindOfMeasure, self::getAvailableMeasureTypes(), true)) {
            $this->kindOfMeasure = $kindOfMeasure;
        }

        return $this;
    }

    public static function getAvailableMeasureTypes(): array
    {
        return [
            self::KIND_REDUCTION,
            self::KIND_REFUS,
            self::KIND_ACCEPTATION,
            self::KIND_PARTAGE,
            self::KIND_NOT_TREATED,
        ];
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

    public function getSpecific(): int
    {
        return $this->specific;
    }

    public function setSpecific(int $specific): self
    {
        $this->specific = $specific;

        return $this;
    }

    public function getBrutProb(): string
    {
        return $this->brutProb;
    }

    public function setBrutProb(string $brutProb): self
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
        return $this->mitigation;
    }

    public function setMitigation(string $mitigation): self
    {
        $this->mitigation = $mitigation;

        return $this;
    }
}
