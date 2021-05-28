<?php declare(strict_types=1);

namespace Monarc\Core\Model\Entity;

use Doctrine\ORM\Mapping as ORM;
use Monarc\Core\Model\Entity\Traits\CreateEntityTrait;
use Monarc\Core\Model\Entity\Traits\UpdateEntityTrait;

/**
 * @ORM\Table(name="operational_instance_risks_scales")
 * @ORM\MappedSuperclass
 * @ORM\HasLifecycleCallbacks()
 */
class OperationalInstanceRiskScaleSuperClass
{
    use CreateEntityTrait;
    use UpdateEntityTrait;

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
     * @var InstanceRiskOpSuperClass
     *
     * @ORM\ManyToOne(targetEntity="InstanceRiskOp", cascade={"persist"})
     * @ORM\JoinColumns({
     *   @ORM\JoinColumn(name="operational_instance_risk_id", referencedColumnName="id", onDelete="CASCADE")
     * })
     */
    protected $operationalInstanceRisk;

    /**
     * @var ScaleImpactTypeSuperClass
     *
     * @ORM\ManyToOne(targetEntity="ScaleImpactType", cascade={"persist"})
     * @ORM\JoinColumns({
     *   @ORM\JoinColumn(name="scale_impact_type_id", referencedColumnName="id", onDelete="CASCADE")
     * })
     */
    protected $scaleImpactTypeId;

    /**
     * @var OperationalRiskScaleSuperClass
     *
     * @ORM\ManyToOne(targetEntity="OperationalRiskScale", cascade={"persist"})
     * @ORM\JoinColumns({
     *   @ORM\JoinColumn(name="operational_risk_scale_id", referencedColumnName="id", onDelete="CASCADE")
     * })
     */
    protected $operationalRiskScaleId;

    /**
     * @var int
     *
     * @ORM\Column(name="brut_value", type="integer", options={"default": -1})
     */
    protected $brutValue = -1;

    /**
     * @var int
     *
     * @ORM\Column(name="net_value", type="integer", options={"default": -1})
     */
    protected $netValue = -1;

    /**
     * @var int
     *
     * @ORM\Column(name="targeted_value", type="integer", options={"default": -1})
     */
    protected $targetedValue = -1;

    public function getId(): int
    {
        return $this->id;
    }

    public function getOperationalInstanceRisk(): InstanceRiskOpSuperClass
    {
        return $this->operationalInstanceRisk;
    }

    public function setOperationalInstanceRisk(InstanceRiskOpSuperClass $operationalInstanceRisk): self
    {
        $this->operationalInstanceRisk = $operationalInstanceRisk;

        return $this;
    }

    /**
     * @return ScaleImpactTypeSuperClass
     */
    public function getScaleImpactTypeId(): ScaleImpactTypeSuperClass
    {
        return $this->scaleImpactTypeId;
    }

    public function setScaleImpactTypeId(ScaleImpactTypeSuperClass $scaleImpactTypeId): self
    {
        $this->scaleImpactTypeId = $scaleImpactTypeId;

        return $this;
    }

    public function getOperationalRiskScaleId(): OperationalRiskScaleSuperClass
    {
        return $this->operationalRiskScaleId;
    }

    public function setOperationalRiskScaleId(OperationalRiskScaleSuperClass $operationalRiskScaleId): self
    {
        $this->operationalRiskScaleId = $operationalRiskScaleId;

        return $this;
    }

    public function getBrutValue(): int
    {
        return $this->brutValue;
    }

    public function setBrutValue(int $brutValue): self
    {
        $this->brutValue = $brutValue;

        return $this;
    }

    public function getNetValue(): int
    {
        return $this->netValue;
    }

    public function setNetValue(int $netValue): self
    {
        $this->netValue = $netValue;

        return $this;
    }

    public function getTargetedValue(): int
    {
        return $this->targetedValue;
    }

    public function setTargetedValue(int $targetedValue): self
    {
        $this->targetedValue = $targetedValue;

        return $this;
    }
}
