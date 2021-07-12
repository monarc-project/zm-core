<?php declare(strict_types=1);
/**
 * @link      https://github.com/monarc-project for the canonical source repository
 * @copyright Copyright (c) 2016-2021 SMILE GIE Securitymadein.lu - Licensed under GNU Affero GPL v3
 * @license   MONARC is licensed under GNU Affero General Public License version 3
 */

namespace Monarc\Core\Model\Entity;

use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\ORM\Mapping as ORM;
use Monarc\Core\Model\Entity\Traits\CreateEntityTrait;
use Monarc\Core\Model\Entity\Traits\UpdateEntityTrait;

/**
 * @ORM\Table(name="operational_risks_scales")
 * @ORM\MappedSuperclass
 * @ORM\HasLifecycleCallbacks()
 */
class OperationalRiskScaleSuperClass
{
    use CreateEntityTrait;
    use UpdateEntityTrait;

    public const TYPE_IMPACT = 1;
    public const TYPE_LIKELIHOOD = 2;

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
     * @var OperationalRiskScaleCommentSuperClass[]|ArrayCollection
     *
     * @ORM\OneToMany(targetEntity="OperationalRiskScaleComment", mappedBy="operationalRiskScale")
     */
    protected $operationalRiskScaleComments;

    /**
     * @var OperationalRiskScaleTypeSuperClass[]|ArrayCollection
     *
     * @ORM\OneToMany(targetEntity="OperationalRiskScaleType", mappedBy="operationalRiskScale")
     */
    protected $operationalRiskScaleTypes;

    /**
     * @var int
     *
     * @ORM\Column(name="type", type="smallint", options={"unsigned":true})
     */
    protected $type;

    /**
     * @var int
     *
     * @ORM\Column(name="min", type="smallint", options={"unsigned":true})
     */
    protected $min;

    /**
     * @var int
     *
     * @ORM\Column(name="max", type="smallint", options={"unsigned":true})
     */
    protected $max;


    public function __construct()
    {
        $this->operationalRiskScaleTypes = new ArrayCollection();
        $this->operationalRiskScaleComments = new ArrayCollection();
    }

    /**
     * @return int
     */
    public function getId()
    {
        return $this->id;
    }

    /**
     * @return AnrSuperClass
     */
    public function getAnr()
    {
        return $this->anr;
    }

    public function setAnr(AnrSuperClass $anr): self
    {
        $this->anr = $anr;

        return $this;
    }

    public function getType(): int
    {
        return $this->type;
    }

    public function setType(int $type): self
    {
        $this->type = $type;

        return $this;
    }

    public function getMin(): int
    {
        return $this->min;
    }

    public function setMin(int $min): self
    {
        $this->min = $min;

        return $this;
    }

    public function getMax(): int
    {
        return $this->max;
    }

    public function setMax(int $max): self
    {
        $this->max = $max;

        return $this;
    }

    public function getOperationalRiskScaleComments()
    {
        return $this->operationalRiskScaleComments;
    }

    public function addOperationalRiskScaleComments(
        OperationalRiskScaleCommentSuperClass $operationalRiskScaleComment
    ): self {
        if (!$this->operationalRiskScaleComments->contains($operationalRiskScaleComment)) {
            $this->operationalRiskScaleComments->add($operationalRiskScaleComment);
            $operationalRiskScaleComment->setOperationalRiskScale($this);
        }

        return $this;
    }

    /**
     * @param OperationalRiskScaleCommentSuperClass[] $operationalRiskScaleComments
     *
     * @return OperationalRiskScaleSuperClass
     */
    public function setOperationalRiskScaleComments($operationalRiskScaleComments): self
    {
        $this->operationalRiskScaleComments = $operationalRiskScaleComments;

        return $this;
    }

    public function getOperationalRiskScaleTypes()
    {
        return $this->operationalRiskScaleTypes;
    }

    public function addOperationalRiskScaleTypes(OperationalRiskScaleTypeSuperClass $operationalRiskScaleType): self
    {
        if (!$this->operationalRiskScaleTypes->contains($operationalRiskScaleType)) {
            $this->operationalRiskScaleTypes->add($operationalRiskScaleType);
            $operationalRiskScaleType->setOperationalRiskScale($this);
        }

        return $this;
    }

    /**
     * @param OperationalRiskScaleTypeSuperClass[] $operationalRiskScaleTypes
     *
     * @return OperationalRiskScaleSuperClass
     */
    public function setOperationalRiskScaleTypes($operationalRiskScaleTypes): self
    {
        $this->operationalRiskScaleTypes = $operationalRiskScaleTypes;

        return $this;
    }
}
