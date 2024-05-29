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
 * @ORM\Table(name="scales", indexes={
 *      @ORM\Index(name="anr", columns={"anr_id"})
 * })
 * @ORM\MappedSuperclass
 * @ORM\HasLifecycleCallbacks()
 */
class ScaleSuperClass
{
    use CreateEntityTrait;
    use UpdateEntityTrait;

    public const TYPE_IMPACT = 1;
    public const TYPE_THREAT = 2;
    public const TYPE_VULNERABILITY = 3;

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
     *   @ORM\JoinColumn(name="anr_id", referencedColumnName="id", nullable=false, onDelete="CASCADE")
     * })
     */
    protected $anr;

    /**
     * @var ScaleCommentSuperClass[]|ArrayCollection
     *
     * @ORM\OneToMany(targetEntity="ScaleComment", mappedBy="scale")
     * @ORM\OrderBy({"scaleIndex" = "ASC"})
     */
    protected $scaleComments;

    /**
     * @var ScaleImpactTypeSuperClass[]|ArrayCollection
     *
     * @ORM\OneToMany(targetEntity="ScaleImpactType", mappedBy="scale")
     */
    protected $scaleImpactTypes;

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

    public function __construct(AnrSuperClass $anr, array $data)
    {
        $this->setAnr($anr)
            ->setType($data['type'])
            ->setMin($data['min'])
            ->setMax($data['max']);

        $this->scaleComments = new ArrayCollection();
        $this->scaleImpactTypes = new ArrayCollection();
    }

    public static function constructFromObject(ScaleSuperClass $scale): ScaleSuperClass
    {
        return new static($scale->getAnr(), [
            'type' => $scale->getType(),
            'min' => $scale->getMin(),
            'max' => $scale->getMax(),
        ]);
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

    public function getType(): int
    {
        return $this->type;
    }

    public function setType(int $type): self
    {
        if (!\array_key_exists($type, static::getAvailableTypes())) {
            throw new \LogicException('Scale type "%d" is not supported. The scale cant be created.');
        }

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

    public function getScaleComments()
    {
        return $this->scaleComments;
    }

    public function addScaleComment(ScaleCommentSuperClass $scaleComment): self
    {
        if (!$this->scaleComments->contains($scaleComment)) {
            $this->scaleComments->add($scaleComment);
            $scaleComment->setScale($this);
        }

        return $this;
    }

    public function getScaleImpactTypes()
    {
        return $this->scaleImpactTypes;
    }

    public static function getAvailableTypes(): array
    {
        return [
            static::TYPE_IMPACT => 'impact',
            static::TYPE_THREAT => 'threat',
            static::TYPE_VULNERABILITY => 'vulnerability',
        ];
    }
}
