<?php declare(strict_types=1);
/**
 * @link      https://github.com/monarc-project for the canonical source repository
 * @copyright Copyright (c) 2016-2022 SMILE GIE Securitymadein.lu - Licensed under GNU Affero GPL v3
 * @license   MONARC is licensed under GNU Affero General Public License version 3
 */

namespace Monarc\Core\Entity;

use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\ORM\Mapping as ORM;
use Monarc\Core\Exception\Exception;
use Monarc\Core\Entity\Traits\CreateEntityTrait;
use Monarc\Core\Entity\Traits\DescriptionsEntityTrait;
use Monarc\Core\Entity\Traits\LabelsEntityTrait;
use Monarc\Core\Entity\Traits\UpdateEntityTrait;

/**
 * @ORM\Table(name="models", indexes={
 *      @ORM\Index(name="anr", columns={"anr_id"})
 * })
 * @ORM\Entity
 * @ORM\HasLifecycleCallbacks()
 */
class Model
{
    use CreateEntityTrait;
    use UpdateEntityTrait;

    use LabelsEntityTrait;
    use DescriptionsEntityTrait;

    public const STATUS_ACTIVE = 1;
    public const STATUS_INACTIVE = 0;
    public const STATUS_DELETED = 3;

    /**
     * @var int
     *
     * @ORM\Column(name="id", type="integer", nullable=false)
     * @ORM\Id
     * @ORM\GeneratedValue(strategy="IDENTITY")
     */
    protected $id;

    /**
     * @var Anr
     *
     * @ORM\OneToOne(targetEntity="Anr", cascade={"persist", "REMOVE"})
     * @ORM\JoinColumns({
     *   @ORM\JoinColumn(name="anr_id", referencedColumnName="id", nullable=true, onDelete="SET NULL")
     * })
     */
    protected $anr;

    /**
     * @var int
     *
     * @ORM\Column(name="status", type="smallint", options={"unsigned":true, "default":1})
     */
    protected $status = 1;

    /**
     * @var int
     *
     * @ORM\Column(name="are_scales_updatable", type="smallint", options={"unsigned":true, "default":1})
     */
    protected $areScalesUpdatable = 1;

    /**
     * @var int
     *
     * @ORM\Column(name="is_default", type="smallint", options={"unsigned":true, "default":0})
     */
    protected $isDefault = 0;

    /**
     * @var int
     *
     * @ORM\Column(name="is_generic", type="smallint", options={"unsigned":true, "default":1})
     */
    protected $isGeneric = 1;

    /**
     * @var int
     *
     * @ORM\Column(name="show_rolf_brut", type="smallint", options={"unsigned":true, "default":1})
     */
    protected $showRolfBrut = 1;

    /**
     * @var Asset[]|ArrayCollection
     * @ORM\ManyToMany(targetEntity="Asset", mappedBy="models")
     */
    protected $assets;

    /**
     * @var Threat[]|ArrayCollection
     * @ORM\ManyToMany(targetEntity="Threat", mappedBy="models")
     */
    protected $threats;

    /**
     * @var Vulnerability[]|ArrayCollection
     * @ORM\ManyToMany(targetEntity="Vulnerability", mappedBy="models")
     */
    protected $vulnerabilities;

    public function __construct()
    {
        $this->assets = new ArrayCollection();
        $this->threats = new ArrayCollection();
        $this->vulnerabilities = new ArrayCollection();
    }

    public function getId(): int
    {
        return $this->id;
    }

    public function getAnr(): ?Anr
    {
        return $this->anr;
    }

    public function setAnr(Anr $anr): self
    {
        $this->anr = $anr;

        return $this;
    }

    public function getAssets()
    {
        return $this->assets;
    }

    public function addAsset(Asset $asset): self
    {
        if (!$this->assets->contains($asset)) {
            $this->assets->add($asset);
            $asset->addModel($this);
        }

        return $this;
    }

    public function removeAsset(AssetSuperClass $asset): self
    {
        if ($this->assets->contains($asset)) {
            $this->assets->removeElement($asset);
            $asset->removeModel($this);
        }

        return $this;
    }

    public function getThreats()
    {
        return $this->threats;
    }

    public function addThreat(Threat $threat): self
    {
        if (!$this->threats->contains($threat)) {
            $this->threats->add($threat);
            $threat->addModel($this);
        }

        return $this;
    }

    public function removeThreat(ThreatSuperClass $threat): self
    {
        if ($this->threats->contains($threat)) {
            $this->threats->removeElement($threat);
            $threat->removeModel($this);
        }

        return $this;
    }

    public function getVulnerabilities()
    {
        return $this->vulnerabilities;
    }

    public function addVulnerability(Vulnerability $vulnerability): self
    {
        if (!$this->vulnerabilities->contains($vulnerability)) {
            $this->vulnerabilities->add($vulnerability);
            $vulnerability->addModel($this);
        }

        return $this;
    }

    public function removeVulnerability(VulnerabilitySuperClass $vulnerability): self
    {
        if ($this->vulnerabilities->contains($vulnerability)) {
            $this->vulnerabilities->removeElement($vulnerability);
            $vulnerability->removeModel($this);
        }

        return $this;
    }

    public function getStatus(): int
    {
        return $this->status;
    }

    public function setStatus(int $status): self
    {
        $this->status = $status;

        return $this;
    }

    public function isActive(): bool
    {
        return $this->status === static::STATUS_ACTIVE;
    }

    public function areScalesUpdatable(): bool
    {
        return (bool)$this->areScalesUpdatable;
    }

    public function setAreScalesUpdatable(bool $areScalesUpdatable): self
    {
        $this->areScalesUpdatable = (int)$areScalesUpdatable;

        return $this;
    }

    public function isDefault(): bool
    {
        return (bool)$this->isDefault;
    }

    public function setIsDefault(bool $isDefault): self
    {
        $this->isDefault = (int)$isDefault;

        return $this;
    }

    public function isGeneric(): bool
    {
        return (bool)$this->isGeneric;
    }

    public function setIsGeneric(bool $isGeneric): self
    {
        $this->isGeneric = (int)$isGeneric;

        return $this;
    }

    public function showRolfBrut(): bool
    {
        return (bool)$this->showRolfBrut;
    }

    public function setShowRolfBrut(bool $showRolfBrut): self
    {
        $this->showRolfBrut = (int)$showRolfBrut;

        return $this;
    }

    public function validateObjectAcceptance(MonarcObject $object, Asset $forcedAsset = null): void
    {
        if ($this->isGeneric() && $object->isModeSpecific()) {
            throw new Exception('You cannot add a specific object to a generic model', 412);
        }

        /** @var Asset $asset */
        $asset = $forcedAsset ?? $object->getAsset();
        if ($asset !== null && !$this->isGeneric() && $asset->isModeSpecific()) {
            foreach ($asset->getModels() as $assetModel) {
                if ($assetModel->getId() === $this->id) {
                    return;
                }
            }

            throw new Exception('You cannot add an object with specific asset unrelated to a specific model', 412);
        }
    }
}
