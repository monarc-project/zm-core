<?php
/**
 * @link      https://github.com/monarc-project for the canonical source repository
 * @copyright Copyright (c) 2016-2024 Luxembourg House of Cybersecurity LHC.lu - Licensed under GNU Affero GPL v3
 * @license   MONARC is licensed under GNU Affero General Public License version 3
 */

namespace Monarc\Core\Entity;

use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\ORM\Mapping as ORM;
use Monarc\Core\Entity\Traits\CreateEntityTrait;
use Monarc\Core\Entity\Traits\LabelsEntityTrait;
use Monarc\Core\Entity\Traits\UpdateEntityTrait;

/**
 * @ORM\Table(name="scales_impact_types", indexes={
 *      @ORM\Index(name="anr", columns={"anr_id"}),
 *      @ORM\Index(name="scale_id", columns={"scale_id"})
 * })
 * @ORM\MappedSuperclass
 * @ORM\HasLifecycleCallbacks()
 */
class ScaleImpactTypeSuperClass
{
    use CreateEntityTrait;
    use UpdateEntityTrait;

    use LabelsEntityTrait;

    public const SCALE_TYPE_C = 1;
    public const SCALE_TYPE_I = 2;
    public const SCALE_TYPE_D = 3;
    public const SCALE_TYPE_R = 4;
    public const SCALE_TYPE_O = 5;
    public const SCALE_TYPE_L = 6;
    public const SCALE_TYPE_F = 7;
    public const SCALE_TYPE_P = 8;

    /**
     * @var int
     *
     * @ORM\Column(name="id", type="integer", nullable=false)
     * @ORM\Id
     * @ORM\GeneratedValue(strategy="IDENTITY")
     */
    protected $id;

    /**
     * @ORM\ManyToOne(targetEntity="Anr")
     * @ORM\JoinColumns({
     *   @ORM\JoinColumn(name="anr_id", referencedColumnName="id", nullable=true, onDelete="CASCADE")
     * })
     */
    protected $anr;

    /**
     * @var ScaleSuperClass
     *
     * @ORM\ManyToOne(targetEntity="Scale")
     * @ORM\JoinColumns({
     *   @ORM\JoinColumn(name="scale_id", referencedColumnName="id", nullable=true, onDelete="CASCADE")
     * })
     */
    protected $scale;

    /**
     * @var ScaleCommentSuperClass[]|ArrayCollection
     *
     * @ORM\OneToMany(targetEntity="ScaleComment", mappedBy="scaleImpactType")
     * @ORM\OrderBy({"scaleIndex" = "ASC"})
     */
    protected $scaleComments;

    /**
     * @var InstanceConsequenceSuperClass[]|ArrayCollection
     *
     * @ORM\OneToMany(targetEntity="InstanceConsequence", mappedBy="scaleImpactType")
     */
    protected $instanceConsequences;

    /**
     * @var int
     *
     * @ORM\Column(name="type", type="smallint", options={"unsigned":true})
     */
    protected $type;

    /**
     * @var int
     *
     * @ORM\Column(name="is_sys", type="smallint", options={"unsigned":true})
     */
    protected $isSys = 0;

    /**
     * @var int
     *
     * @ORM\Column(name="is_hidden", type="smallint", options={"unsigned":true})
     */
    protected $isHidden = 0;

    public function __construct()
    {
        $this->scaleComments = new ArrayCollection();
        $this->instanceConsequences = new ArrayCollection();
    }

    public static function constructFromObject(ScaleImpactTypeSuperClass $scaleImpactType): ScaleImpactTypeSuperClass
    {
        return (new static())
            ->setType($scaleImpactType->getType())
            ->setLabels($scaleImpactType->getLabels())
            ->setIsSys($scaleImpactType->isSys())
            ->setIsHidden($scaleImpactType->isHidden());
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

    public function getScale(): ScaleSuperClass
    {
        return $this->scale;
    }

    public function setScale(ScaleSuperClass $scale): self
    {
        $this->scale = $scale;

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
            $scaleComment->setScaleImpactType($this);
        }

        return $this;
    }

    public function getInstanceConsequences()
    {
        return $this->instanceConsequences;
    }

    public function addInstanceConsequence(InstanceConsequenceSuperClass $instanceConsequence): self
    {
        if (!$this->instanceConsequences->contains($instanceConsequence)) {
            $this->instanceConsequences->add($instanceConsequence);
            $instanceConsequence->setScaleImpactType($this);
        }

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

    public function isSys(): bool
    {
        return (bool)$this->isSys;
    }

    public function setIsSys(bool $isSys): self
    {
        $this->isSys = (int)$isSys;

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

    public static function getScaleImpactTypesCid(): array
    {
        return [
            static::SCALE_TYPE_C,
            static::SCALE_TYPE_I,
            static::SCALE_TYPE_D,
        ];
    }

    public static function getScaleImpactTypesShortcuts(): array
    {
        return [
            static::SCALE_TYPE_C => 'C',
            static::SCALE_TYPE_I => 'I',
            static::SCALE_TYPE_D => 'D',
            static::SCALE_TYPE_R => 'R',
            static::SCALE_TYPE_O => 'O',
            static::SCALE_TYPE_L => 'L',
            static::SCALE_TYPE_F => 'F',
            static::SCALE_TYPE_P => 'P',
        ];
    }

    public static function getDefaultScalesImpacts(): array
    {
        return [
            'label1' => [
                'C' => 'Confidentialité',
                'I' => 'Intégrité',
                'D' => 'Disponibilité',
                'R' => 'Réputation',
                'O' => 'Opérationnel',
                'L' => 'Légal',
                'F' => 'Financier',
                'P' => 'Personne'
            ],
            'label2' => [
                'C' => 'Confidentiality',
                'I' => 'Integrity',
                'D' => 'Availability',
                'R' => 'Reputation',
                'O' => 'Operational',
                'L' => 'Legal',
                'F' => 'Financial',
                'P' => 'Personal'
            ],
            'label3' => [
                'C' => 'Vertraulichkeit',
                'I' => 'Integrität',
                'D' => 'Verfügbarkeit',
                'R' => 'Ruf',
                'O' => 'Einsatzbereit',
                'L' => 'Legal',
                'F' => 'Finanziellen',
                'P' => 'Person'
            ],
            'label4' => [
                'C' => 'Vertrouwelijkheid',
                'I' => 'Integriteit',
                'D' => 'Beschikbaarheid',
                'R' => 'Reputatie',
                'O' => 'Operationeel',
                'L' => 'Legaal',
                'F' => 'Financieel',
                'P' => 'Persoon'
            ],
        ];
    }
}
