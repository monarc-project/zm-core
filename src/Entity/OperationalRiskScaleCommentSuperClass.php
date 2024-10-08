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
 * @ORM\Table(name="operational_risks_scales_comments", indexes={
 *      @ORM\Index(name="scale_id", columns={"scale_id"})
 * })
 * @ORM\MappedSuperclass
 * @ORM\HasLifecycleCallbacks()
 */
class OperationalRiskScaleCommentSuperClass
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
     * @var OperationalRiskScaleTypeSuperClass
     *
     * @ORM\ManyToOne(targetEntity="OperationalRiskScaleType")
     * @ORM\JoinColumns({
     *   @ORM\JoinColumn(name="operational_risk_scale_type_id", referencedColumnName="id", onDelete="CASCADE")
     * })
     */
    protected $operationalRiskScaleType;

    /**
     * @var int
     *
     * @ORM\Column(name="scale_value", type="integer", options={"unsigned": true})
     */
    protected $scaleValue;

    /**
     * @var int
     *
     * @ORM\Column(name="scale_index", type="integer", options={"unsigned": true})
     */
    protected $scaleIndex;

    /**
     * @var int
     *
     * @ORM\Column(name="is_hidden", type="smallint", options={"default": 0})
     */
    protected $isHidden = 0;

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

    public function getOperationalRiskScale(): OperationalRiskScaleSuperClass
    {
        return $this->operationalRiskScale;
    }

    public function setOperationalRiskScale(OperationalRiskScaleSuperClass $operationalRiskScale): self
    {
        $this->operationalRiskScale = $operationalRiskScale;
        $operationalRiskScale->addOperationalRiskScaleComments($this);

        return $this;
    }

    public function getOperationalRiskScaleType(): ?OperationalRiskScaleTypeSuperClass
    {
        return $this->operationalRiskScaleType;
    }

    public function setOperationalRiskScaleType(OperationalRiskScaleTypeSuperClass $operationalRiskScaleType): self
    {
        $this->operationalRiskScaleType = $operationalRiskScaleType;
        $operationalRiskScaleType->addOperationalRiskScaleComments($this);

        return $this;
    }

    public function getScaleIndex(): int
    {
        return $this->scaleIndex;
    }

    public function setScaleIndex(int $scaleIndex): self
    {
        $this->scaleIndex = $scaleIndex;

        return $this;
    }

    public function getScaleValue(): int
    {
        return $this->scaleValue;
    }

    public function setScaleValue(int $scaleValue): self
    {
        $this->scaleValue = $scaleValue;

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
}
