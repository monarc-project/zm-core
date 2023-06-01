<?php declare(strict_types=1);
/**
 * @link      https://github.com/monarc-project for the canonical source repository
 * @copyright Copyright (c) 2016-2023 Luxembourg House of Cybersecurity LHC.lu - Licensed under GNU Affero GPL v3
 * @license   MONARC is licensed under GNU Affero General Public License version 3
 */

namespace Monarc\Core\Model\Entity;

use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\ORM\Mapping as ORM;
use Monarc\Core\Model\Entity\Interfaces\PositionedEntityInterface;
use Monarc\Core\Model\Entity\Traits\CreateEntityTrait;
use Monarc\Core\Model\Entity\Traits\LabelsEntityTrait;
use Monarc\Core\Model\Entity\Traits\PropertyStateEntityTrait;
use Monarc\Core\Model\Entity\Traits\UpdateEntityTrait;

/**
 * @ORM\Table(name="objects_categories", indexes={
 *      @ORM\Index(name="root_id", columns={"root_id"}),
 *      @ORM\Index(name="parent_id", columns={"parent_id"}),
 *      @ORM\Index(name="position", columns={"position"}),
 *      @ORM\Index(name="anr", columns={"anr_id"}),
 * })
 * @ORM\MappedSuperclass
 * @ORM\HasLifecycleCallbacks()
 */
class ObjectCategorySuperClass implements PositionedEntityInterface
{
    use CreateEntityTrait;
    use UpdateEntityTrait;

    use LabelsEntityTrait;

    use PropertyStateEntityTrait;

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
     * @ORM\ManyToOne(targetEntity="Anr", cascade={"persist"})
     * @ORM\JoinColumns({
     *   @ORM\JoinColumn(name="anr_id", referencedColumnName="id", nullable=true)
     * })
     */
    protected $anr;

    /**
     * @var ObjectCategorySuperClass|null
     *
     * @ORM\ManyToOne(targetEntity="ObjectCategory", cascade={"persist"})
     * @ORM\JoinColumns({
     *   @ORM\JoinColumn(name="root_id", referencedColumnName="id", nullable=true)
     * })
     */
    protected $root;

    /**
     * @var ObjectCategorySuperClass|null
     *
     * @ORM\ManyToOne(targetEntity="ObjectCategory", inversedBy="children", cascade={"persist"})
     * @ORM\JoinColumns({
     *   @ORM\JoinColumn(name="parent_id", referencedColumnName="id", nullable=true)
     * })
     */
    protected $parent;

    /**
     * @var ArrayCollection|ObjectCategorySuperClass[]
     *
     * @ORM\OneToMany(targetEntity="ObjectCategory", mappedBy="parent")
     * @ORM\OrderBy({"position" = "ASC"})
     */
    protected $children;

    /**
     * @var ObjectSuperClass[]|ArrayCollection
     *
     * @ORM\OneToMany(targetEntity="MonarcObject", mappedBy="category")
     * @ORM\OrderBy({"position" = "ASC"})
     */
    protected $objects;

    /**
     * @var AnrSuperClass[]|ArrayCollection
     *
     * @ORM\ManyToMany(targetEntity="Anr", mappedBy="objectCategories", cascade={"persist"})
     */
    protected $anrs;

    /**
     * @var int
     *
     * @ORM\Column(name="position", type="smallint", options={"unsigned":true, "default":1})
     */
    protected $position = 1;

    public function __construct()
    {
        $this->objects = new ArrayCollection();
        $this->children = new ArrayCollection();
        $this->anrs = new ArrayCollection();
    }

    public function getImplicitPositionRelationsValues(): array
    {
        $fields['parent'] = $this->parent;
        if ($this->anr !== null) {
            $fields['anr'] = $this->anr;
        }

        return $fields;
    }

    public function getId(): int
    {
        return $this->id;
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

    public function getParent(): ?self
    {
        return $this->parent;
    }

    public function setParent(?self $parent): self
    {
        /* When parent is changed the child is removed. */
        if (($parent === null && $this->parent !== null)
            || ($this->parent !== null && $parent->getId() !== $this->parent->getId())
        ) {
            $this->parent->removeChild($this);
        }

        $this->trackPropertyState('parent', $this->parent);

        $this->parent = $parent;

        if ($this->parent !== null) {
            $this->parent->addChild($this);
        }

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

    public function removeChild(self $childCategory): self
    {
        if ($this->children->contains($childCategory)) {
            $this->children->removeElement($childCategory);
        }

        return $this;
    }

    public function addChild(self $childCategory): self
    {
        if (!$this->children->contains($childCategory)) {
            $this->children->add($childCategory);
        }

        return $this;
    }

    public function hasChildren(): bool
    {
        return !$this->children->isEmpty();
    }

    /**
     * @return int[]
     */
    public function getRecursiveChildrenIds(): array
    {
        $childrenIds = [];
        foreach ($this->children as $child) {
            $childrenIds[] = $child->getId();
            if (!$this->children->isEmpty()) {
                $childrenIds = array_merge($childrenIds, $child->getRecursiveChildrenIds());
            }
        }

        return $childrenIds;
    }

    public function getRoot(): ?self
    {
        return $this->root;
    }

    public function setRoot(?self $root): self
    {
        $this->root = $root;

        return $this;
    }

    public function isCategoryRoot(): bool
    {
        return $this->root === null;
    }

    public function getRootCategory(): self
    {
        return $this->root ?? $this;
    }

    public function areRootCategoriesEqual(self $category): bool
    {
        return $this->getRootCategory()->getId() === $category->getRootCategory()->getId();
    }

    public function getObjects()
    {
        return $this->objects;
    }

    public function addObject(ObjectSuperClass $object): self
    {
        if (!$this->objects->contains($object)) {
            $this->objects->add($object);
            $object->setCategory($this);
        }

        return $this;
    }

    public function removeObject(ObjectSuperClass $object): self
    {
        if ($this->objects->contains($object)) {
            $this->objects->removeElement($object);
            $object->setCategory(null);
        }

        return $this;
    }

    public function getObjectsRecursively(): array
    {
        $objects = [];
        if (!$this->objects->isEmpty()) {
            $objects = $this->objects->toArray();
            foreach ($this->children as $childCategory) {
                $objects = array_merge($objects, $childCategory->getObjectsRecursively());
            }
        }

        return $objects;
    }

    public function hasObjectsLinkedDirectlyOrToChildCategories(): bool
    {
        if (!$this->objects->isEmpty()) {
            return true;
        }

        foreach ($this->children as $childCategory) {
            if ($childCategory->hasObjectsLinkedDirectlyOrToChildCategories()) {
                return true;
            }
        }

        return false;
    }

    public function getLinkedAnrs()
    {
        return $this->anrs;
    }

    public function hasAnrLink(AnrSuperClass $anr): bool
    {
        return $this->anrs->contains($anr);
    }

    public function addAnrLink(AnrSuperClass $anr): self
    {
        if (!$this->anrs->contains($anr)) {
            $this->anrs->add($anr);
            $anr->addObjectCategory($this);
        }

        return $this;
    }

    public function removeAnrLink(AnrSuperClass $anr): self
    {
        if ($this->anrs->contains($anr)) {
            $this->anrs->removeElement($anr);
            $anr->removeObjectCategory($this);
        }

        return $this;
    }

    public function removeAllAnrLinks(): self
    {
        foreach ($this->anrs as $anr) {
            $this->removeAnrLink($anr);
        }

        return $this;
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
}
