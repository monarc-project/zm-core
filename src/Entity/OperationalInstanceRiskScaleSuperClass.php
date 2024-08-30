<?php declare(strict_types=1);
/**
 * @link      https://github.com/monarc-project for the canonical source repository
 * @copyright Copyright (c) 2016-2024 Luxembourg House of Cybersecurity LHC.lu - Licensed under GNU Affero GPL v3
 * @license   MONARC is licensed under GNU Affero General Public License version 3
 */

namespace Monarc\Core\Entity;

use Doctrine\ORM\Mapping as ORM;
use Monarc\Core\Entity\Traits\CreateEntityTrait;
use Monarc\Core\Entity\Traits\UpdateEntityTrait;

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
     * @var InstanceRiskOpSuperClass
     *
     * @ORM\ManyToOne(targetEntity="InstanceRiskOp")
     * @ORM\JoinColumns({
     *   @ORM\JoinColumn(name="instance_risk_op_id", referencedColumnName="id", onDelete="CASCADE")
     * })
     */
    protected $operationalInstanceRisk;

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

    public function getOperationalRiskScaleType(): OperationalRiskScaleTypeSuperClass
    {
        return $this->operationalRiskScaleType;
    }

    public function setOperationalRiskScaleType(OperationalRiskScaleTypeSuperClass $operationalRiskScaleType): self
    {
        $this->operationalRiskScaleType = $operationalRiskScaleType;

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
