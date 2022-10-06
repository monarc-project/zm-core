<?php declare(strict_types=1);
/**
 * @link      https://github.com/monarc-project for the canonical source repository
 * @copyright Copyright (c) 2016-2022 SMILE GIE Securitymadein.lu - Licensed under GNU Affero GPL v3
 * @license   MONARC is licensed under GNU Affero General Public License version 3
 */

namespace Monarc\Core\Model\Entity;

use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\ORM\Mapping as ORM;
use Monarc\Core\Model\Entity\Interfaces\PositionedEntityInterface;
use Monarc\Core\Model\Entity\Traits\CreateEntityTrait;
use Monarc\Core\Model\Entity\Traits\UpdateEntityTrait;
use Ramsey\Uuid\Lazy\LazyUuidFromString;
use Ramsey\Uuid\Uuid;

/**
 * @ORM\Table(name="objects", indexes={
 *      @ORM\Index(name="object_category_id", columns={"object_category_id"}),
 *      @ORM\Index(name="asset_id", columns={"asset_id"}),
 *      @ORM\Index(name="rolf_tag_id", columns={"rolf_tag_id"})
 * })
 * @ORM\MappedSuperclass
 * @ORM\HasLifecycleCallbacks()
 */
class ObjectSuperClass implements PositionedEntityInterface
{
    use CreateEntityTrait;
    use UpdateEntityTrait;

    public const SCOPE_LOCAL = 1;
    public const SCOPE_GLOBAL = 2;

    public const MODE_GENERIC = 0;
    public const MODE_SPECIFIC = 1;

    /**
     * @var LazyUuidFromString|string
     *
     * @ORM\Column(name="uuid", type="uuid", nullable=false)
     * @ORM\Id
     */
    protected $uuid;

    /**
     * @var AnrSuperClass
     *
     * @ORM\ManyToOne(targetEntity="Anr", cascade={"persist"})
     * @ORM\JoinColumns({
     *   @ORM\JoinColumn(name="anr_id", referencedColumnName="id", nullable=true)
     * })
     */
    protected $anr;

    /**
     * @var ArrayCollection|AnrSuperClass[]
     *
     * @ORM\ManyToMany(targetEntity="Anr", inversedBy="objects", cascade={"persist"})
     * @ORM\JoinTable(name="anrs_objects",
     *  joinColumns={@ORM\JoinColumn(name="object_id", referencedColumnName="uuid")},
     *  inverseJoinColumns={@ORM\JoinColumn(name="anr_id", referencedColumnName="id")}
     * )
     */
    protected $anrs;

    /**
     * @var ObjectCategorySuperClass
     *
     * @ORM\ManyToOne(targetEntity="ObjectCategory", cascade={"persist"})
     * @ORM\JoinColumns({
     *   @ORM\JoinColumn(name="object_category_id", referencedColumnName="id", nullable=true)
     * })
     */
    protected $category;

    /**
     * @var AssetSuperClass
     *
     * @ORM\ManyToOne(targetEntity="Asset", cascade={"persist"})
     * @ORM\JoinColumns({
     *   @ORM\JoinColumn(name="asset_id", referencedColumnName="uuid", nullable=true, onDelete="SET NULL")
     * })
     */
    protected $asset;

    /**
     * @var RolfTagSuperClass
     *
     * @ORM\ManyToOne(targetEntity="RolfTag", cascade={"persist"})
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
    protected $mode = 1;

    /**
     * @var int
     *
     * @ORM\Column(name="scope", type="smallint", options={"unsigned":true, "default":1})
     */
    protected $scope = 1;

    /**
     * @var string
     *
     * @ORM\Column(name="name1", type="string", length=255, nullable=true)
     */
    protected $name1;

    /**
     * @var string
     *
     * @ORM\Column(name="name2", type="string", length=255, nullable=true)
     */
    protected $name2;

    /**
     * @var string
     *
     * @ORM\Column(name="name3", type="string", length=255, nullable=true)
     */
    protected $name3;

    /**
     * @var string
     *
     * @ORM\Column(name="name4", type="string", length=255, nullable=true)
     */
    protected $name4;

    /**
     * @var string
     *
     * @ORM\Column(name="label1", type="string", length=255, nullable=true)
     */
    protected $label1;

    /**
     * @var string
     *
     * @ORM\Column(name="label2", type="string", length=255, nullable=true)
     */
    protected $label2;

    /**
     * @var string
     *
     * @ORM\Column(name="label3", type="string", length=255, nullable=true)
     */
    protected $label3;

    /**
     * @var string
     *
     * @ORM\Column(name="label4", type="string", length=255, nullable=true)
     */
    protected $label4;

    /**
     * @var float
     *
     * @ORM\Column(name="disponibility", type="decimal", options={"unsigned":true, "default":0})
     */
    protected $availability = 0;

    /**
     * @var int
     *
     * @ORM\Column(name="position", type="smallint", options={"unsigned":true, "default":0})
     */
    protected $position = 0;

    /**
     * @var string
     *
     * @ORM\Column(name="token_import", type="string", length=255, nullable=true)
     */
    protected $tokenImport;

    /**
     * @var string
     *
     * @ORM\Column(name="original_name", type="string", length=255, nullable=true)
     */
    protected $originalName;

    // TODO: implement the same links on Client's entity side with 2 fields rel !!!

    /**
     * @var ArrayCollection|ObjectSuperClass[]
     *
     * @ORM\ManyToMany(targetEntity="MonarcObject", mappedBy="children")
     */
    protected $parents;

    /**
     * Note: If the property use used, the order has to be performed manually due to the Doctrine limitation.
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
     * @var ArrayCollection|InstanceSuperClass[]
     *
     * @ORM\OneToMany(targetEntity="Instance", mappedBy="object")
     */
    protected $instances;

    public function __construct()
    {
        $this->anrs = new ArrayCollection();
        $this->parents = new ArrayCollection();
        $this->children = new ArrayCollection();
        $this->parentsLinks = new ArrayCollection();
        $this->childrenLinks = new ArrayCollection();
        $this->instances = new ArrayCollection();
    }

    public function getImplicitPositionRelationsValues(): array
    {
        $fields = ['category' => $this->category];
        if ($this->anr !== null) {
            $fields['anr'] = $this->anr;
        }

        return $fields;
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

    public function getAnr(): ?AnrSuperClass
    {
        return $this->anr;
    }

    public function setAnr(AnrSuperClass $anr): self
    {
        $this->anr = $anr;

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
        if ($category === null) {
            if ($this->category !== null) {
                $this->category->removeObject($this);
            }
            $this->category = null;
        } else {
            $this->category = $category;
            $category->addObject($this);
        }

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

    public function setRolfTag(RolfTagSuperClass $rolfTag)
    {
        $this->rolfTag = $rolfTag;

        return $this;
    }

    public function hasRolfTag(): bool
    {
        return $this->rolfTag !== null;
    }

    public function getAnrs()
    {
        return $this->anrs;
    }

    public function addAnr(AnrSuperClass $anr): self
    {
        if (!$this->anrs->contains($anr)) {
            $this->anrs->add($anr);
            $anr->addObject($this);
        }

        return $this;
    }

    public function removeAnr(AnrSuperClass $anr): self
    {
        if ($this->anrs->contains($anr)) {
            $this->anrs->remove($anr);
            $anr->removeObject($this);
        }

        return $this;
    }

    public function hasAnrLink(AnrSuperClass $anr): bool
    {
        return $this->anrs->contains($anr);
    }

    public function setNames(array $names): self
    {
        foreach (range(1, 4) as $index) {
            $key = 'name' . $index;
            if (isset($names[$key])) {
                $this->{$key} = $names[$key];
            }
        }

        return $this;
    }

    public function setName(string $nameKey, string $nameValue): self
    {
        if (\in_array($nameKey, ['name1', 'name2', 'name3', 'name4'], true)) {
            $this->{$nameKey} = $nameValue;
        }

        return $this;
    }

    public function getName(int $languageIndex): string
    {
        if (!\in_array($languageIndex, range(1, 4), true)) {
            return '';
        }

        return (string)$this->{'name' . $languageIndex};
    }

    public function getNames(): array
    {
        return [
            'name1' => $this->name1,
            'name2' => $this->name2,
            'name3' => $this->name3,
            'name4' => $this->name4,
        ];
    }

    public function getNameCleanedFromCopy(int $languageIndex): string
    {
        return preg_replace('/^(.*)(\(copy #\d+\))+$/', '$1', $this->getName($languageIndex));
    }

    public function setLabels(array $labels): self
    {
        foreach (range(1, 4) as $index) {
            $key = 'label' . $index;
            if (isset($labels[$key])) {
                $this->{$key} = $labels[$key];
            }
        }

        return $this;
    }

    public function setLabel(string $labelKey, string $labelValue): self
    {
        if (\in_array($labelKey, ['label1', 'label2', 'label3', 'label4'], true)) {
            $this->{$labelKey} = $labelValue;
        }

        return $this;
    }

    public function getLabel(int $languageIndex): string
    {
        if (!\in_array($languageIndex, range(1, 4), true)) {
            return '';
        }

        return (string)$this->{'label' . $languageIndex};
    }

    public function getLabelCleanedFromCopy(int $languageIndex): string
    {
        return preg_replace('/^(.*)(\(copy #\d+\))+$/', '$1', $this->getLabel($languageIndex));
    }

    public function getLabels(): array
    {
        return [
            'label1' => $this->label1,
            'label2' => $this->label2,
            'label3' => $this->label3,
            'label4' => $this->label4,
        ];
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

    public function setAvailability(float $availability): self
    {
        $this->availability = $availability;

        return $this;
    }

    public function getAvailability(): float
    {
        return (float)$this->availability;
    }

    public function setPosition(int $position): self
    {
        $this->position = $position;

        return $this;
    }

    public function getPosition(): int
    {
        return $this->position;
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

    public function addParent(ObjectSuperClass $object): self
    {
        if (!$this->parents->contains($object)) {
            $this->parents->add($object);
            $object->addChild($this);
        }

        return $this;
    }

    public function removeParent(ObjectSuperClass $object): self
    {
        if ($this->parents->contains($object)) {
            $this->parents->remove($object);
            $object->removeChild($this);
        }

        return $this;
    }

    /**
     * Note: If the method use used, the order has to be performed manually due to the Doctrine limitation.
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

    public function addChild(ObjectSuperClass $object): self
    {
        if (!$this->children->contains($object)) {
            $this->children->add($object);
            $object->addParent($this);
        }

        return $this;
    }

    public function removeChild(ObjectSuperClass $object): self
    {
        if ($this->children->contains($object)) {
            $this->children->remove($object);
            $object->removeParent($this);
        }

        return $this;
    }

    /**
     * The links are only used to keep the order of the objects' composition.
     */
    public function getParentsLinks()
    {
        return $this->parentsLinks;
    }

    public function getChildrenLinks()
    {
        return $this->childrenLinks;
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
            $this->instances->remove($instance);
        }

        return $this;
    }
}
