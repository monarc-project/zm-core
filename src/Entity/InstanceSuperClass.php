<?php declare(strict_types=1);
/**
 * @link      https://github.com/monarc-project for the canonical source repository
 * @copyright Copyright (c) 2016-2023 Luxembourg House of Cybersecurity LHC.lu - Licensed under GNU Affero GPL v3
 * @license   MONARC is licensed under GNU Affero General Public License version 3
 */

namespace Monarc\Core\Entity;

use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\ORM\Mapping as ORM;
use Monarc\Core\Entity\Interfaces\PositionedEntityInterface;
use Monarc\Core\Entity\Interfaces\PropertyStateEntityInterface;
use Monarc\Core\Entity\Traits\CreateEntityTrait;
use Monarc\Core\Entity\Traits\LabelsEntityTrait;
use Monarc\Core\Entity\Traits\NamesEntityTrait;
use Monarc\Core\Entity\Traits\PropertyStateEntityTrait;
use Monarc\Core\Entity\Traits\UpdateEntityTrait;

/**
 * @ORM\Table(name="instances", indexes={
 *      @ORM\Index(name="anr", columns={"anr_id"}),
 *      @ORM\Index(name="asset_id", columns={"asset_id"}),
 *      @ORM\Index(name="object_id", columns={"object_id"}),
 *      @ORM\Index(name="root_id", columns={"root_id"}),
 *      @ORM\Index(name="parent_id", columns={"parent_id"})
 * })
 * @ORM\MappedSuperclass
 * @ORM\HasLifecycleCallbacks()
 */
class InstanceSuperClass implements PositionedEntityInterface, PropertyStateEntityInterface
{
    use CreateEntityTrait;
    use UpdateEntityTrait;

    use LabelsEntityTrait;
    use NamesEntityTrait;

    use PropertyStateEntityTrait;

    public const LEVEL_ROOT = 1; // Root instance.
    public const LEVEL_LEAF = 2; // Child instance.
    public const LEVEL_INTER = 3; // Intermediate level.

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
     * @var AssetSuperClass
     *
     * @ORM\ManyToOne(targetEntity="Asset")
     * @ORM\JoinColumns({
     *   @ORM\JoinColumn(name="asset_id", referencedColumnName="uuid", nullable=true)
     * })
     */
    protected $asset;

    /**
     * @var ObjectSuperClass
     *
     * @ORM\ManyToOne(targetEntity="MonarcObject")
     * @ORM\JoinColumns({
     *   @ORM\JoinColumn(name="object_id", referencedColumnName="uuid", nullable=true)
     * })
     */
    protected $object;

    /**
     * @var InstanceSuperClass|null
     *
     * @ORM\ManyToOne(targetEntity="Instance")
     * @ORM\JoinColumns({
     *   @ORM\JoinColumn(name="root_id", referencedColumnName="id", nullable=true)
     * })
     */
    protected $root;

    /**
     * @var InstanceSuperClass|null
     *
     * @ORM\ManyToOne(targetEntity="Instance", inversedBy="children")
     * @ORM\JoinColumns({
     *   @ORM\JoinColumn(name="parent_id", referencedColumnName="id", nullable=true)
     * })
     */
    protected $parent;

    /**
     * @var ArrayCollection|InstanceSuperClass[]
     *
     * @ORM\OneToMany(targetEntity="Instance", mappedBy="parent")
     * @ORM\OrderBy({"position" = "ASC"})
     */
    protected $children;

    /**
     * @var InstanceConsequenceSuperClass[]|ArrayCollection
     *
     * @ORM\OneToMany(targetEntity="InstanceConsequence", mappedBy="instance")
     */
    protected $instanceConsequences;

    /**
     * @var InstanceRiskSuperClass[]|ArrayCollection
     *
     * @ORM\OneToMany(targetEntity="InstanceRisk", orphanRemoval=true, mappedBy="instance")
     */
    protected $instanceRisks;

    /**
     * @var InstanceRiskOpSuperClass[]|ArrayCollection
     *
     * @ORM\OneToMany(targetEntity="InstanceRiskOp", orphanRemoval=true, mappedBy="instance")
     */
    protected $operationalInstanceRisks;

    /**
     * @var int
     *
     * @ORM\Column(name="level", type="smallint", options={"unsigned":true, "default":1})
     */
    protected $level = 1;

    /**
     * @var int "-1" - means the value is inherited from one of its parents.
     *
     * @ORM\Column(name="c", type="smallint", options={"unsigned":true, "default":1})
     */
    protected $c = -1;

    /**
     * @var int "-1" - means the value is inherited from one of its parents.
     *
     * @ORM\Column(name="i", type="smallint", options={"unsigned":true, "default":1})
     */
    protected $i = -1;

    /**
     * @var int "-1" - means the value is inherited from one of its parents.
     *
     * @ORM\Column(name="d", type="smallint", options={"unsigned":true, "default":1})
     */
    protected $d = -1;

    /**
     * @var int 1 - the confidentiality value is inherited, 0 - not inherited.
     *
     * @ORM\Column(name="ch", type="smallint", options={"unsigned":true, "default":1})
     */
    protected $ch = 1;

    /**
     * @var int 1 - the integrity value is inherited, 0 - not inherited.
     *
     * @ORM\Column(name="ih", type="smallint", options={"unsigned":true, "default":1})
     */
    protected $ih = 1;

    /**
     * @var int 1 - the availability value is inherited, 0 - not inherited.
     *
     * @ORM\Column(name="dh", type="smallint", options={"unsigned":true, "default":1})
     */
    protected $dh = 1;

    /**
     * @var int
     *
     * @ORM\Column(name="position", type="smallint", options={"unsigned":true, "default":1})
     */
    protected $position = 1;

    public function __construct()
    {
        $this->instanceConsequences = new ArrayCollection();
        $this->instanceRisks = new ArrayCollection();
        $this->operationalInstanceRisks = new ArrayCollection();
        $this->children = new ArrayCollection();
    }

    public static function constructFromObject(InstanceSuperClass $instance): InstanceSuperClass
    {
        return (new static())
            ->setLabels($instance->getLabels())
            ->setNames($instance->getNames())
            ->setConfidentiality($instance->getConfidentiality())
            ->setIntegrity($instance->getIntegrity())
            ->setAvailability($instance->getAvailability())
            ->setInheritedConfidentiality((int)$instance->isConfidentialityInherited())
            ->setInheritedIntegrity((int)$instance->isIntegrityInherited())
            ->setInheritedAvailability((int)$instance->isAvailabilityInherited())
            ->setPosition($instance->getPosition())
            ->setLevel($instance->getLevel());
    }

    public function getImplicitPositionRelationsValues(): array
    {
        return [
            'parent' => $this->parent,
            'anr' => $this->anr,
        ];
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
        $anr->addInstance($this);

        return $this;
    }

    public function getAsset(): AssetSuperClass
    {
        return $this->asset;
    }

    public function setAsset(AssetSuperClass $asset): self
    {
        $this->asset = $asset;
        $asset->addInstance($this);

        return $this;
    }

    public function getObject(): ObjectSuperClass
    {
        return $this->object;
    }

    public function setObject(ObjectSuperClass $object): self
    {
        $this->object = $object;
        $object->addInstance($this);

        return $this;
    }

    public function getRoot(): ?self
    {
        return $this->root;
    }

    public function getRootInstance(): self
    {
        return $this->root ?? $this;
    }

    public function setRoot(?self $root): self
    {
        $this->root = $root;

        return $this;
    }

    public function isRoot(): bool
    {
        return $this->root === null;
    }

    public function getParent(): ?self
    {
        return $this->parent;
    }

    public function setParent(?InstanceSuperClass $parent): self
    {
        $this->trackPropertyState('parent', $this->parent);
        $this->parent = $parent;

        return $this;
    }

    public function hasParent(): bool
    {
        return $this->parent !== null;
    }

    public function getChildren()
    {
        return $this->children;
    }

    public function getLevel(): int
    {
        return $this->level;
    }

    public function setLevel(int $level): self
    {
        $this->level = $level;

        return $this;
    }

    public function isLevelRoot(): bool
    {
        return $this->level === static::LEVEL_ROOT;
    }

    public function setConfidentiality(int $c): self
    {
        $this->c = $c;

        return $this;
    }

    public function getConfidentiality(): int
    {
        return $this->c;
    }

    public function setIntegrity(int $i): self
    {
        $this->i = $i;

        return $this;
    }

    public function getIntegrity(): int
    {
        return $this->i;
    }

    public function setAvailability(int $d): self
    {
        $this->d = $d;

        return $this;
    }

    public function getAvailability(): int
    {
        return $this->d;
    }

    public function setInheritedConfidentiality(int $ch): self
    {
        $this->ch = $ch;

        return $this;
    }

    public function isConfidentialityInherited(): bool
    {
        return $this->ch === 1;
    }

    public function setInheritedIntegrity(int $ih): self
    {
        $this->ih = $ih;

        return $this;
    }

    public function isIntegrityInherited(): bool
    {
        return $this->ih === 1;
    }

    public function setInheritedAvailability(int $dh): self
    {
        $this->dh = $dh;

        return $this;
    }

    public function isAvailabilityInherited(): bool
    {
        return $this->dh === 1;
    }

    public static function getAvailableScalesCriteria(): array
    {
        return [
            'c' => 'Confidentiality',
            'i' => 'Integrity',
            'd' => 'Availability'
        ];
    }

    public function getPosition(): int
    {
        return $this->position;
    }

    public function setPosition(int $position): self
    {
        $this->position = $position;

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
            $instanceConsequence->setInstance($this);
        }

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
            $instanceRisk->setInstance($this);
        }

        return $this;
    }

    public function removeInstanceRisk(InstanceRiskSuperClass $instanceRisk): self
    {
        if ($this->instanceRisks->contains($instanceRisk)) {
            $this->instanceRisks->removeElement($instanceRisk);
        }

        return $this;
    }

    public function removeAllInstanceRisks(): self
    {
        foreach ($this->instanceRisks as $instanceRisk) {
            $this->instanceRisks->removeElement($instanceRisk);
        }

        return $this;
    }

    public function getOperationalInstanceRisks()
    {
        return $this->operationalInstanceRisks;
    }

    public function addOperationalInstanceRisk(InstanceRiskOpSuperClass $operationalInstanceRisk): self
    {
        if (!$this->operationalInstanceRisks->contains($operationalInstanceRisk)) {
            $this->operationalInstanceRisks->add($operationalInstanceRisk);
            $operationalInstanceRisk->setInstance($this);
        }

        return $this;
    }

    public function removeOperationalInstanceRisk(InstanceRiskOpSuperClass $operationalInstanceRisk): self
    {
        if ($this->operationalInstanceRisks->contains($operationalInstanceRisk)) {
            $this->operationalInstanceRisks->removeElement($operationalInstanceRisk);
        }

        return $this;
    }

    public function removeAllOperationalInstanceRisks(): self
    {
        foreach ($this->operationalInstanceRisks as $operationalInstanceRisk) {
            $this->operationalInstanceRisks->removeElement($operationalInstanceRisk);
        }

        return $this;
    }

    /**
     * Returns the instance hierarchy array ordered from its root through all the children to the instance itself.
     * Each element is a normalized array of the instances' names.
     */
    public function getHierarchyArray(): array
    {
        if ($this->isRoot()) {
            return [$this->getNames()];
        }

        return $this->getParents();
    }

    private function getParents(): array
    {
        if ($this->isRoot() || $this->getParent() === null) {
            return [$this->getNames()];
        }

        return array_merge($this->getParent()->getParents(), [$this->getNames()]);
    }

    /**
     * @return int[]
     */
    public function getSelfAndChildrenIds(): array
    {
        $childrenIds = [];
        foreach ($this->children as $childInstance) {
            $childrenIds[] = $childInstance->getSelfAndChildrenIds();
        }

        return array_merge([$this->id], ...$childrenIds);
    }

    /**
     * @return self[]
     */
    public function getSelfAndChildrenInstances(): array
    {
        $childrenInstances = [];
        foreach ($this->children as $childInstance) {
            $childrenInstances[] = $childInstance->getSelfAndChildrenInstances();
        }

        return array_merge([$this], ...$childrenInstances);
    }

    public function updateImpactBasedOnConsequences(): self
    {
        $maxConfidentiality = -1;
        $maxIntegrity = -1;
        $maxAvailability = -1;
        foreach ($this->instanceConsequences as $instanceConsequence) {
            /* Exclude hidden consequences and deprecated consequences impact types from the calculation. */
            if ($instanceConsequence->isHidden() || \in_array(
                $instanceConsequence->getScaleImpactType()->getType(),
                ScaleImpactTypeSuperClass::getScaleImpactTypesCid(),
                true
            )) {
                continue;
            }

            if ($instanceConsequence->getConfidentiality() > $maxConfidentiality) {
                $maxConfidentiality = $instanceConsequence->getConfidentiality();
            }
            if ($instanceConsequence->getIntegrity() > $maxIntegrity) {
                $maxIntegrity = $instanceConsequence->getIntegrity();
            }
            if ($instanceConsequence->getAvailability() > $maxAvailability) {
                $maxAvailability = $instanceConsequence->getAvailability();
            }
        }

        $this->c = $maxConfidentiality;
        $this->i = $maxIntegrity;
        $this->d = $maxAvailability;
        $this->ch = $this->c === -1 ? 1 : 0;
        $this->ih = $this->i === -1 ? 1 : 0;
        $this->dh = $this->d === -1 ? 1 : 0;

        return $this;
    }

    public function refreshInheritedImpact(): self
    {
        if ($this->isConfidentialityInherited()) {
            $this->c = $this->hasParent() ? $this->parent->getConfidentiality() : -1;
        }
        if ($this->isIntegrityInherited()) {
            $this->i = $this->hasParent() ? $this->parent->getIntegrity() : -1;
        }
        if ($this->isAvailabilityInherited()) {
            $this->d = $this->hasParent() ? $this->parent->getAvailability() : -1;
        }

        return $this;
    }
}
