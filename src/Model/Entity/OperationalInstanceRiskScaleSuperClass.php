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
     *   @ORM\JoinColumn(name="instance_risk_op_id", referencedColumnName="id", onDelete="CASCADE")
     * })
     */
    protected $operationalInstanceRisk;

    /**
     * @var OperationalRiskScaleSuperClass
     *
     * @ORM\ManyToOne(targetEntity="OperationalRiskScale", cascade={"persist"})
     * @ORM\JoinColumns({
     *   @ORM\JoinColumn(name="operational_risk_scale_id", referencedColumnName="id", onDelete="CASCADE")
     * })
     */
    protected $operationalRiskScale;

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

    public function __clone()
    {
        $this->id = null;
        $this->setCreatedAtValue();
    }

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
        $this->operationalInstanceRisk->addOperationalInstanceRiskScale($this);

        return $this;
    }

    public function getOperationalRiskScale(): OperationalRiskScaleSuperClass
    {
        return $this->operationalRiskScale;
    }

    public function setOperationalRiskScale(OperationalRiskScaleSuperClass $operationalRiskScale): self
    {
        $this->operationalRiskScale = $operationalRiskScale;

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

    public function getAnr(): AnrSuperClass
    {
        return $this->anr;
    }

    public function setAnr(AnrSuperClass $anr): self
    {
        $this->anr = $anr;

        return $this;
    }
}
