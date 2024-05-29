<?php declare(strict_types=1);
/**
 * @link      https://github.com/monarc-project for the canonical source repository
 * @copyright Copyright (c) 2016-2021 SMILE GIE Securitymadein.lu - Licensed under GNU Affero GPL v3
 * @license   MONARC is licensed under GNU Affero General Public License version 3
 */

namespace Monarc\Core\Entity;

use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\ORM\Mapping as ORM;
use Monarc\Core\Entity\Traits\CreateEntityTrait;
use Monarc\Core\Entity\Traits\UpdateEntityTrait;

/**
 * @ORM\Table(name="operational_risks_scales_types", indexes={
 *     @ORM\Index(name="anr_id", columns={"anr_id"}),
 *     @ORM\Index(name="scale_id", columns={"scale_id"})
 * })
 * @ORM\MappedSuperclass
 * @ORM\HasLifecycleCallbacks()
 */
class OperationalRiskScaleTypeSuperClass
{
    use CreateEntityTrait;
    use UpdateEntityTrait;

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
     * @var OperationalRiskScaleSuperClass
     *
     * @ORM\ManyToOne(targetEntity="OperationalRiskScale")
     * @ORM\JoinColumns({
     *   @ORM\JoinColumn(name="operational_risk_scale_id", referencedColumnName="id", onDelete="CASCADE")
     * })
     */
    protected $operationalRiskScale;

    /**
     * @var OperationalRiskScaleCommentSuperClass[]|ArrayCollection
     *
     * @ORM\OneToMany(targetEntity="OperationalRiskScaleComment", mappedBy="operationalRiskScaleType")
     * @ORM\OrderBy({"scaleIndex" = "ASC"})
     */
    protected $operationalRiskScaleComments;

    /**
     * @var OperationalInstanceRiskScaleSuperClass[]|ArrayCollection
     *
     * @ORM\OneToMany(targetEntity="OperationalInstanceRiskScale", mappedBy="operationalRiskScaleType")
     */
    protected $operationalInstanceRiskScales;

    /**
     * @var int
     *
     * @ORM\Column(name="is_hidden", type="smallint", options={"default": 0})
     */
    protected $isHidden = 0;

    public function __construct()
    {
        $this->operationalRiskScaleComments = new ArrayCollection();
        $this->operationalInstanceRiskScales = new ArrayCollection();
    }

    public function getId(): int
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

    public function getOperationalRiskScale(): OperationalRiskScaleSuperClass
    {
        return $this->operationalRiskScale;
    }

    public function setOperationalRiskScale(OperationalRiskScaleSuperClass $operationalRiskScale): self
    {
        $this->operationalRiskScale = $operationalRiskScale;
        $operationalRiskScale->addOperationalRiskScaleTypes($this);

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
            $operationalRiskScaleComment->setOperationalRiskScaleType($this);
        }

        return $this;
    }

    public function getOperationalInstanceRiskScales()
    {
        return $this->operationalInstanceRiskScales;
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

    public static function getDefaultScalesImpacts(): array
    {
        return [
            [
                'fr' => 'Réputation',
                'en' => 'Reputation',
                'de' => 'Ruf',
                'nl' => 'Reputatie',
            ],
            [
                'fr' => 'Opérationnel',
                'en' => 'Operational',
                'de' => 'Einsatzbereit',
                'nl' => 'Operationeel',
            ],
            [
                'fr' => 'Légal',
                'en' => 'Legal',
                'de' => 'Legal',
                'nl' => 'Legaal',
            ],
            [
                'fr' => 'Financier',
                'en' => 'Financial',
                'de' => 'Finanziellen',
                'nl' => 'Financieel',
            ],
            [
                'fr' => 'Personne',
                'en' => 'Personal',
                'de' => 'Person',
                'nl' => 'Persoon',
            ],
        ];
    }
}
