<?php declare(strict_types=1);
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
use Monarc\Core\Entity\Traits\NamesEntityTrait;
use Monarc\Core\Entity\Traits\UpdateEntityTrait;
use Ramsey\Uuid\Lazy\LazyUuidFromString;
use Ramsey\Uuid\Uuid;
use Ramsey\Uuid\UuidInterface;

/**
 * @ORM\Table(name="objects", indexes={
 *      @ORM\Index(name="object_category_id", columns={"object_category_id"}),
 *      @ORM\Index(name="asset_id", columns={"asset_id"}),
 *      @ORM\Index(name="rolf_tag_id", columns={"rolf_tag_id"})
 * })
 * @ORM\MappedSuperclass
 * @ORM\HasLifecycleCallbacks()
 */
class ObjectSuperClass
{
    use CreateEntityTrait;
    use UpdateEntityTrait;

    use LabelsEntityTrait;
    use NamesEntityTrait;

    public const SCOPE_LOCAL = 1;
    public const SCOPE_GLOBAL = 2;

    public const MODE_GENERIC = 0;
    public const MODE_SPECIFIC = 1;

    /**
     * @var LazyUuidFromString|UuidInterface|string
     *
     * @ORM\Column(name="uuid", type="uuid", nullable=false)
     * @ORM\Id
     */
    protected $uuid;

    /**
     * @var ObjectCategorySuperClass|null
     *
     * @ORM\ManyToOne(targetEntity="ObjectCategory")
     * @ORM\JoinColumns({
     *   @ORM\JoinColumn(name="object_category_id", referencedColumnName="id", nullable=true)
     * })
     */
    protected $category;

    /**
     * @var AssetSuperClass
     *
     * @ORM\ManyToOne(targetEntity="Asset")
     * @ORM\JoinColumns({
     *   @ORM\JoinColumn(name="asset_id", referencedColumnName="uuid", nullable=true, onDelete="SET NULL")
     * })
     */
    protected $asset;

    /**
     * @var RolfTagSuperClass
     *
     * @ORM\ManyToOne(targetEntity="RolfTag")
     * @ORM\JoinColumns({
     *   @ORM\JoinColumn(name="rolf_tag_id", referencedColumnName="id", nullable=true)
     * })
     */
    protected $rolfTag;

    /**
     * @var int
     *
     * @ORM\Column(name="mode", type="smallint", options={"unsigned":true, "default":1})
     */
    protected $mode = self::MODE_GENERIC;

    /**
     * @var int
     *
     * @ORM\Column(name="scope", type="smallint", options={"unsigned":true, "default":1})
     */
    protected $scope = self::SCOPE_LOCAL;

    /**
     * @var ArrayCollection|ObjectSuperClass[]
     *
     * @ORM\ManyToMany(targetEntity="MonarcObject", mappedBy="children")
     */
    protected $parents;

    /**
     * Note: If the property use used, the order has to be performed manually due to Doctrine limitation.
     *       Ordered list can be retrieved with use $childrenLinks relation.
     *
     * @var ArrayCollection|ObjectSuperClass[]
     *
     * @ORM\ManyToMany(targetEntity="MonarcObject")
     * @ORM\JoinTable(name="objects_objects",
     *  joinColumns={@ORM\JoinColumn(name="father_id", referencedColumnName="uuid")},
     *  inverseJoinColumns={@ORM\JoinColumn(name="child_id", referencedColumnName="uuid")}
     * )
     */
    protected $children;

    /**
     * @var ArrayCollection|ObjectObjectSuperClass[]
     *
     * @ORM\OneToMany(targetEntity="ObjectObject", mappedBy="child")
     */
    protected $parentsLinks;

    /**
     * @var ArrayCollection|ObjectObjectSuperClass[]
     *
     * @ORM\OneToMany(targetEntity="ObjectObject", mappedBy="parent")
     * @ORM\OrderBy({"position": "ASC"})
     */
    protected $childrenLinks;

    /**
     * Note: On BackofficeOffice side the instances list includes all the instances across all the models.
     *
     * @var ArrayCollection|InstanceSuperClass[]
     *
     * @ORM\OneToMany(targetEntity="Instance", mappedBy="object")
     */
    protected $instances;

    public function __construct()
    {
        $this->parents = new ArrayCollection();
        $this->children = new ArrayCollection();
        $this->parentsLinks = new ArrayCollection();
        $this->childrenLinks = new ArrayCollection();
        $this->instances = new ArrayCollection();
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

    public function setUuid(string $uuid): self
    {
        $this->uuid = $uuid;

        return $this;
    }

    public function hasCategory(): bool
    {
        return $this->category !== null;
    }

    public function getCategory(): ?ObjectCategorySuperClass
    {
        return $this->category;
    }

    public function setCategory(?ObjectCategorySuperClass $category): self
    {
        if ($category !== null) {
            $category->addObject($this);
        } elseif ($this->category !== null) {
            $this->category->removeObject($this);
        }
        $this->category = $category;

        return $this;
    }

    public function getAsset(): AssetSuperClass
    {
        return $this->asset;
    }

    public function setAsset(AssetSuperClass $asset): self
    {
        $this->asset = $asset;
        $asset->addObject($this);

        return $this;
    }

    public function getRolfTag(): ?RolfTagSuperClass
    {
        return $this->rolfTag;
    }

    public function setRolfTag(?RolfTagSuperClass $rolfTag)
    {
        if ($rolfTag !== null) {
            $rolfTag->addObject($this);
        } elseif ($this->rolfTag !== null) {
            $this->rolfTag->removeObject($this);
        }
        $this->rolfTag = $rolfTag;

        return $this;
    }

    public function hasRolfTag(): bool
    {
        return $this->rolfTag !== null;
    }

    public function getNameCleanedFromCopy(int $languageIndex): string
    {
        return preg_replace('/^(.*)(\(copy #\d+\))+$/', '$1', $this->getName($languageIndex));
    }

    public function getLabelCleanedFromCopy(int $languageIndex): string
    {
        return preg_replace('/^(.*)(\(copy #\d+\))+$/', '$1', $this->getLabel($languageIndex));
    }

    public function getScope(): int
    {
        return $this->scope;
    }

    public function setScope(int $scope): self
    {
        $this->scope = $scope;

        return $this;
    }

    public function getScopeName(): string
    {
        return $this->scope === static::SCOPE_LOCAL ? 'local' : 'global';
    }

    public function setMode(int $mode): self
    {
        $this->mode = $mode;

        return $this;
    }

    public function getMode(): int
    {
        return $this->mode;
    }

    public function isModeSpecific(): bool
    {
        return $this->mode === static::MODE_SPECIFIC;
    }

    public function isModeGeneric(): bool
    {
        return $this->mode === static::MODE_GENERIC;
    }

    public function isScopeGlobal(): bool
    {
        return $this->scope === static::SCOPE_GLOBAL;
    }

    public function isEqualTo(ObjectSuperClass $object): bool
    {
        return $this->getUuid() === $object->getUuid();
    }

    public function isObjectOneOfParents(ObjectSuperClass $object): bool
    {
        foreach ($this->parents as $parent) {
            if ($parent->getUuid() === $object->getUuid() || $parent->isObjectOneOfParents($object)) {
                return true;
            }
        }

        return false;
    }

    public function getParents()
    {
        return $this->parents;
    }

    /**
     * Note: If the method use used, the order has to be performed manually due to the Doctrine limitation.
     * It's not allowed to use addParent or addChild methods due to the doctrine limitation of 2 fields relation on FO.
     */
    public function getChildren()
    {
        return $this->children;
    }

    public function hasChildren(): bool
    {
        return !$this->children->isEmpty();
    }

    public function hasChild(ObjectSuperClass $child): bool
    {
        return $this->children->contains($child);
    }

    /**
     * The links are used to keep the order of the objects' composition.
     */
    public function getParentsLinks()
    {
        return $this->parentsLinks;
    }

    public function addParentLink(ObjectObjectSuperClass $parentLink): self
    {
        if (!$this->parentsLinks->contains($parentLink)) {
            $this->parentsLinks->add($parentLink);
        }

        return $this;
    }

    public function getChildrenLinks()
    {
        return $this->childrenLinks;
    }

    public function addChildLink(ObjectObjectSuperClass $childLink): self
    {
        if (!$this->childrenLinks->contains($childLink)) {
            $this->childrenLinks->add($childLink);
        }

        return $this;
    }

    public function removeChildLink(ObjectObjectSuperClass $childLink): self
    {
        if ($this->childrenLinks->contains($childLink)) {
            $this->childrenLinks->removeElement($childLink);
        }

        return $this;
    }

    public function getInstances()
    {
        return $this->instances;
    }

    public function hasInstances(): bool
    {
        return !$this->instances->isEmpty();
    }

    public function addInstance(InstanceSuperClass $instance): self
    {
        if (!$this->instances->contains($instance)) {
            $this->instances->add($instance);
            $instance->setObject($this);
        }

        return $this;
    }

    public function removeInstance(InstanceSuperClass $instance): self
    {
        if ($this->instances->contains($instance)) {
            $this->instances->removeElement($instance);
        }

        return $this;
    }
}
