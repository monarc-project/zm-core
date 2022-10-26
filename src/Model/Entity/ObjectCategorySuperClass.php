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
use Monarc\Core\Model\Entity\Interfaces\TreeStructuredEntityInterface;
use Monarc\Core\Model\Entity\Traits\CreateEntityTrait;
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
class ObjectCategorySuperClass implements PositionedEntityInterface, TreeStructuredEntityInterface
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
     * @var AnrObjectCategorySuperClass[]|ArrayCollection
     *
     * @ORM\OneToMany(targetEntity="AnrObjectCategory", mappedBy="category")
     * @ORM\OrderBy({"position" = "ASC"})
     */
    protected $anrObjectCategories;

    /**
     * @var AnrSuperClass[]|ArrayCollection
     *
     * @ORM\ManyToMany(targetEntity="Anr", mappedBy="objectCategories", cascade={"persist"})
     */
    protected $anrs;

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
     * @var int
     *
     * @ORM\Column(name="position", type="smallint", options={"unsigned":true, "default":1})
     */
    protected $position = 1;

    public function __construct()
    {
        $this->objects = new ArrayCollection();
        $this->children = new ArrayCollection();
        $this->anrObjectCategories = new ArrayCollection();
        $this->anrs = new ArrayCollection();
    }

    public function getImplicitPositionRelationsValues(): array
    {
        $fields = [
            'parent' => $this->parent,
        ];
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
        $this->parent = $parent;

        return $this;
    }

    public function getChildren()
    {
        return $this->children;
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

    /**
     * @param ObjectCategorySuperClass|null $root
     */
    public function setRoot(?TreeStructuredEntityInterface $root): self
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

    public function areRootCategoriesEqual(ObjectCategorySuperClass $category): bool
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

    public function getAnrObjectCategories()
    {
        return $this->anrObjectCategories;
    }

    public function addAnrObjectCategory(AnrObjectCategorySuperClass $anrObjectCategory): self
    {
        if (!$this->anrObjectCategories->contains($anrObjectCategory)) {
            $this->anrObjectCategories->add($anrObjectCategory);
            $anrObjectCategory->setCategory($this);
        }

        return $this;
    }

    public function hasAnrLink(AnrSuperClass $anr): bool
    {
        return $this->anrs->contains($anr);
    }

    public function removeAnrLink(AnrSuperClass $anr): self
    {
        if ($this->anrs->contains($anr)) {
            $this->anrs->removeElement($anr);
            $anr->removeObjectCategory($this);
        }

        return $this;
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

    public function getLabel(int $languageIndex): string
    {
        if (!\in_array($languageIndex, range(1, 4), true)) {
            return '';
        }

        return (string)$this->{'label' . $languageIndex};
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
