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
     * @var String
     *
     * @ORM\Column(name="owner", type="string", length=255, nullable=true)
     */
    protected $owner;

    /**
     * @var String
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
     * @ORM\Column(name="brut_r", type="smallint", options={"unsigned":false, "default":-1})
     */
    protected $brutR = -1;

    /**
     * @var int
     *
     * @ORM\Column(name="brut_o", type="smallint", options={"unsigned":false, "default":-1})
     */
    protected $brutO = -1;

    /**
     * @var int
     *
     * @ORM\Column(name="brut_l", type="smallint", options={"unsigned":false, "default":-1})
     */
    protected $brutL = -1;

    /**
     * @var int
     *
     * @ORM\Column(name="brut_f", type="smallint", options={"unsigned":false, "default":-1})
     */
    protected $brutF = -1;

    /**
     * @var int
     *
     * @ORM\Column(name="brut_p", type="smallint", options={"unsigned":false, "default":-1})
     */
    protected $brutP = -1;

    /**
     * @var int
     *
     * @ORM\Column(name="cache_brut_risk", type="smallint", options={"unsigned":false, "default":-1})
     */
    protected $cacheBrutRisk = -1;

    /**
     * @var int
     *
     * @ORM\Column(name="net_prob", type="smallint", options={"unsigned":false, "default":-1})
     */
    protected $netProb = -1;

    /**
     * @var int
     *
     * @ORM\Column(name="net_r", type="smallint", options={"unsigned":false, "default":-1})
     */
    protected $netR = -1;

    /**
     * @var int
     *
     * @ORM\Column(name="net_o", type="smallint", options={"unsigned":false, "default":-1})
     */
    protected $netO = -1;

    /**
     * @var int
     *
     * @ORM\Column(name="net_l", type="smallint", options={"unsigned":false, "default":-1})
     */
    protected $netL = -1;

    /**
     * @var int
     *
     * @ORM\Column(name="net_f", type="smallint", options={"unsigned":false, "default":-1})
     */
    protected $netF = -1;

    /**
     * @var int
     *
     * @ORM\Column(name="net_p", type="smallint", options={"unsigned":false, "default":-1})
     */
    protected $netP = -1;

    /**
     * @var int
     *
     * @ORM\Column(name="cache_net_risk", type="smallint", options={"unsigned":false, "default":-1})
     */
    protected $cacheNetRisk = -1;

    /**
     * @var int
     *
     * @ORM\Column(name="targeted_prob", type="smallint", options={"unsigned":false, "default":-1})
     */
    protected $targetedProb = -1;

    /**
     * @var int
     *
     * @ORM\Column(name="targeted_r", type="smallint", options={"unsigned":false, "default":-1})
     */
    protected $targetedR = -1;

    /**
     * @var int
     *
     * @ORM\Column(name="targeted_o", type="smallint", options={"unsigned":false, "default":-1})
     */
    protected $targetedO = -1;

    /**
     * @var int
     *
     * @ORM\Column(name="targeted_l", type="smallint", options={"unsigned":false, "default":-1})
     */
    protected $targetedL = -1;

    /**
     * @var int
     *
     * @ORM\Column(name="targeted_f", type="smallint", options={"unsigned":false, "default":-1})
     */
    protected $targetedF = -1;

    /**
     * @var int
     *
     * @ORM\Column(name="targeted_p", type="smallint", options={"unsigned":false, "default":-1})
     */
    protected $targetedP = -1;

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

    public function getCacheNetRisk(): int
    {
        return $this->cacheNetRisk;
    }

    public function getCacheTargetedRisk(): int
    {
        return $this->cacheTargetedRisk;
    }

    public function getComment(): string
    {
        return (string)$this->comment;
    }

    public function getInputFilter($partial = false)
    {
        if (!$this->inputFilter) {
            parent::getInputFilter($partial);

            $integers = [
                'brutProb',
                'brutR',
                'brutO',
                'brutL',
                'brutF',
                'brutP',
                'cacheBrutRisk',
                'netProb',
                'netR',
                'netO',
                'netL',
                'netF',
                'netP',
                'cacheNetRisk',
                'targetedProb',
                'targetedR',
                'targetedO',
                'targetedL',
                'targetedF',
                'targetedP',
                'cacheTargetedRisk',
                'kindOfMeasure',
                'specific',
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
