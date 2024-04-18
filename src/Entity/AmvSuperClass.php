<?php declare(strict_types=1);
/**
 * @link      https://github.com/monarc-project for the canonical source repository
 * @copyright Copyright (c) 2016-2024 Luxembourg House of Cybersecurity LHC.lu - Licensed under GNU Affero GPL v3
 * @license   MONARC is licensed under GNU Affero General Public License version 3
 */

namespace Monarc\Core\Entity;

use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\ORM\Mapping as ORM;
use Monarc\Core\Entity\Interfaces\PositionedEntityInterface;
use Monarc\Core\Entity\Traits\CreateEntityTrait;
use Monarc\Core\Entity\Traits\PropertyStateEntityTrait;
use Monarc\Core\Entity\Traits\UpdateEntityTrait;
use Ramsey\Uuid\Uuid;
use Ramsey\Uuid\UuidInterface;

/**
 * @ORM\Table(name="amvs", indexes={
 *      @ORM\Index(name="asset", columns={"asset_id"}),
 *      @ORM\Index(name="threat", columns={"threat_id"}),
 *      @ORM\Index(name="vulnerability", columns={"vulnerability_id"}),
 * })
 * @ORM\MappedSuperclass
 * @ORM\HasLifecycleCallbacks()
 */
class AmvSuperClass implements PositionedEntityInterface
{
    use CreateEntityTrait;
    use UpdateEntityTrait;

    use PropertyStateEntityTrait;

    public const STATUS_ACTIVE = 1;
    public const STATUS_INACTIVE = 0;

    /**
     * @var UuidInterface|string
     *
     * @ORM\Column(name="uuid", type="uuid", nullable=false)
     * @ORM\Id
     */
    protected $uuid;

    /**
     * @var InstanceRiskSuperClass[]|ArrayCollection
     *
     * @ORM\OneToMany(targetEntity="InstanceRisk", mappedBy="amv")
     */
    protected $instanceRisks;

    /**
     * @var AssetSuperClass
     *
     * @ORM\ManyToOne(targetEntity="Asset", cascade={"persist"})
     * @ORM\JoinColumns({
     *   @ORM\JoinColumn(name="asset_id", referencedColumnName="uuid", nullable=false)
     * })
     */
    protected $asset;

    /**
     * @var ThreatSuperClass
     *
     * @ORM\ManyToOne(targetEntity="Threat", cascade={"persist"})
     * @ORM\JoinColumns({
     *   @ORM\JoinColumn(name="threat_id", referencedColumnName="uuid", nullable=false)
     * })
     */
    protected $threat;

    /**
     * @var VulnerabilitySuperClass
     *
     * @ORM\ManyToOne(targetEntity="Vulnerability", cascade={"persist"})
     * @ORM\JoinColumns({
     *   @ORM\JoinColumn(name="vulnerability_id", referencedColumnName="uuid", nullable=false)
     * })
     */
    protected $vulnerability;

    /**
     * @var ArrayCollection|MeasureSuperClass[]
     *
     * @ORM\ManyToMany(targetEntity="Measure", mappedBy="amvs", cascade={"persist"})
     */
    protected $measures;

    /**
     * @var int
     *
     * @ORM\Column(name="position", type="smallint", options={"unsigned":true, "default":1})
     */
    protected $position = 1;

    /**
     * @var int
     *
     * @ORM\Column(name="status", type="smallint", options={"unsigned":true, "default":1})
     */
    protected $status = 1;

    public function __construct()
    {
        $this->instanceRisks = new ArrayCollection();
        $this->measures = new ArrayCollection();
    }

    public function getImplicitPositionRelationsValues(): array
    {
        return ['asset' => $this->asset];
    }

    /**
     * @ORM\PrePersist
     */
    public function generateAndSetUuid(): self
    {
        if ($this->uuid === null) {
            $this->uuid = Uuid::uuid4();
        }

        return $this;
    }

    public function getUuid(): string
    {
        return (string)$this->uuid;
    }

    /**
     * @param string $uuid
     *
     * @return self
     */
    public function setUuid($uuid): self
    {
        $this->uuid = $uuid;

        return $this;
    }

    public function getInstanceRisks()
    {
        return $this->instanceRisks;
    }

    public function addInstanceRisk(InstanceRiskSuperClass $instanceRisk): self
    {
        if (!$this->instanceRisks->contains($instanceRisk)) {
            $this->instanceRisks->add($instanceRisk);
            $instanceRisk->setAmv($this);
        }

        return $this;
    }

    public function removeInstanceRisk(InstanceRiskSuperClass $instanceRisk): self
    {
        if ($this->instanceRisks->contains($instanceRisk)) {
            $this->instanceRisks->removeElement($instanceRisk);
            $instanceRisk->setAmv(null);
        }

        return $this;
    }

    public function getThreat(): ThreatSuperClass
    {
        return $this->threat;
    }

    public function setThreat(ThreatSuperClass $threat): self
    {
        $this->threat = $threat;

        return $this;
    }

    public function getAsset(): AssetSuperClass
    {
        return $this->asset;
    }

    public function setAsset(AssetSuperClass $asset): self
    {
        $this->trackPropertyState('asset', $this->asset);
        $this->asset = $asset;
        $asset->addAmv($this);

        return $this;
    }

    public function getVulnerability(): VulnerabilitySuperClass
    {
        return $this->vulnerability;
    }

    public function setVulnerability(VulnerabilitySuperClass $vulnerability): self
    {
        $this->vulnerability = $vulnerability;

        return $this;
    }

    public function getMeasures()
    {
        return $this->measures;
    }

    public function addMeasure(MeasureSuperClass $measure): self
    {
        if (!$this->measures->contains($measure)) {
            $this->measures->add($measure);
            $measure->addAmv($this);
        }

        return $this;
    }

    public function removeMeasure(MeasureSuperClass $measure): self
    {
        if ($this->measures->contains($measure)) {
            $this->measures->removeElement($measure);
            $measure->removeAmv($this);
        }

        return $this;
    }

    public function unlinkMeasures(): self
    {
        foreach ($this->measures as $measure) {
            $this->measures->removeElement($measure);
            $measure->removeAmv($this);
        }

        return $this;
    }

    public function getPosition(): int
    {
        return $this->position;
    }

    public function setPosition(int $position): AmvSuperClass
    {
        $this->position = $position;

        return $this;
    }

    public function getStatus(): int
    {
        return $this->status;
    }

    public function setStatus(int $status): AmvSuperClass
    {
        $this->status = $status;

        return $this;
    }
}
