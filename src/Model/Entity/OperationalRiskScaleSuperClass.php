<?php declare(strict_types=1);

namespace Monarc\Core\Model\Entity;

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

    public function getMin(): int
    {
        return $this->min;
    }

    public function getMax(): int
    {
        return $this->max;
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
}
