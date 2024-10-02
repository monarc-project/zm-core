<?php declare(strict_types=1);
/**
 * @link      https://github.com/monarc-project for the canonical source repository
 * @copyright Copyright (c) 2016-2023 Luxembourg House of Cybersecurity LHC.lu - Licensed under GNU Affero GPL v3
 * @license   MONARC is licensed under GNU Affero General Public License version 3
 */

namespace Monarc\Core\Entity;

use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\ORM\Mapping as ORM;

/**
 * @ORM\Table(name="anrs")
 * @ORM\Entity
 */
class Anr extends AnrSuperClass
{
    use Traits\LabelsEntityTrait;
    use Traits\DescriptionsEntityTrait;

    /**
     * @var Model
     *
     * @ORM\OneToOne(targetEntity="Model",  mappedBy="anr")
     */
    protected $model;

    /**
     * @var ArrayCollection|MonarcObject[]
     *
     * @ORM\ManyToMany(targetEntity="MonarcObject", mappedBy="anrs")
     */
    protected $objects;

    /**
     * @var ArrayCollection|ObjectCategory[]
     *
     * @ORM\ManyToMany(targetEntity="ObjectCategory", inversedBy="anrs")
     * @ORM\JoinTable(name="anrs_objects_categories",
     *  inverseJoinColumns={@ORM\JoinColumn(name="object_category_id", referencedColumnName="id")},
     *  joinColumns={@ORM\JoinColumn(name="anr_id", referencedColumnName="id")},
     * )
     * @ORM\OrderBy({"position" = "ASC"})
     */
    protected $objectCategories;

    public function __construct()
    {
        parent::__construct();

        $this->objects = new ArrayCollection();
        $this->objectCategories = new ArrayCollection();
    }

    /**
     * @param Anr $anr
     *
     * @return Anr
     */
    public static function constructFromObject(AnrSuperClass $anr): AnrSuperClass
    {
        /** @var Anr $newAnr */
        $newAnr = parent::constructFromObject($anr);

        return $newAnr->setLabels($anr->getLabels())->setDescriptions($anr->getDescriptions());
    }

    public function setModel(Model $model): self
    {
        $this->model = $model;

        return $this;
    }

    public function getModel(): Model
    {
        return $this->model;
    }

    public function getObjects()
    {
        return $this->objects;
    }

    public function addObject(MonarcObject $object): self
    {
        if (!$this->objects->contains($object)) {
            $this->objects->add($object);
            $object->addAnr($this);
        }

        return $this;
    }

    public function removeObject(MonarcObject $object): self
    {
        if ($this->objects->contains($object)) {
            $this->objects->removeElement($object);
            $object->removeAnr($this);
        }

        return $this;
    }

    public function getObjectCategories()
    {
        return $this->objectCategories;
    }

    public function addObjectCategory(ObjectCategory $objectCategory): self
    {
        if (!$this->objectCategories->contains($objectCategory)) {
            $this->objectCategories->add($objectCategory);
            $objectCategory->addAnrLink($this);
        }

        return $this;
    }

    public function removeObjectCategory(ObjectCategory $objectCategory): self
    {
        if ($this->objectCategories->contains($objectCategory)) {
            $this->objectCategories->removeElement($objectCategory);
            $objectCategory->removeAnrLink($this);
        }

        return $this;
    }
}
