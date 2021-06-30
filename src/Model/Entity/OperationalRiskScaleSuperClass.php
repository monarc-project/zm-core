<?php declare(strict_types=1);

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
class OperationalRiskScaleSuperClass extends AbstractEntity
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

    /**
     * @var string
     *
     * @ORM\Column(name="label_translation_key", type="string", length=255)
     */
    protected $labelTranslationKey;

    /**
     * @var int
     *
     * @ORM\Column(name="is_hidden", type="smallint", options={"default": 0})
     */
    protected $isHidden = 0;


    public function __construct()
    {
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

    public function getLabelTranslationKey(): string
    {
        return $this->labelTranslationKey;
    }

    public function setLabelTranslationKey(string $labelTranslationKey): self
    {
        $this->labelTranslationKey = $labelTranslationKey;

        return $this;
    }

    public function isHidden(): bool
    {
        return (bool)$this->isHidden;
    }

    public function setIsHidden(bool $isHidden): self
    {
        $this->isHidden = (int)$isHidden;

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

    public function setOperationalRiskScaleComments($operationalRiskScaleComments): self
    {
        $this->operationalRiskScaleComments = $operationalRiskScaleComments;

        return $this;
    }
}
