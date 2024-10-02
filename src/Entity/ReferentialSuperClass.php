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
use Monarc\Core\Entity\Traits\UpdateEntityTrait;
use Ramsey\Uuid\Uuid;
use Ramsey\Uuid\UuidInterface;

/**
 * @ORM\Table(name="referentials")
 * @ORM\MappedSuperclass
 * @ORM\HasLifecycleCallbacks()
 */
class ReferentialSuperClass
{
    use CreateEntityTrait;
    use UpdateEntityTrait;

    use LabelsEntityTrait;

    /**
     * @var UuidInterface|string
     *
     * @ORM\Id
     * @ORM\Column(name="uuid", type="uuid", unique=true)
     */
    protected $uuid;

    /**
     * @var MeasureSuperClass[]|ArrayCollection
     *
     * @ORM\OneToMany(targetEntity="Measure", mappedBy="referential")
     */
    protected $measures;

    /**
     * @var SoaCategorySuperClass[]|ArrayCollection
     *
     * @ORM\OneToMany(targetEntity="SoaCategory", mappedBy="referential")
     */
    protected $categories;

    public function __construct()
    {
        $this->measures = new ArrayCollection();
        $this->categories = new ArrayCollection();
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

    public function getMeasures()
    {
        return $this->measures;
    }

    public function addMeasure(MeasureSuperClass $measure): self
    {
        if (!$this->measures->contains($measure)) {
            $this->measures->add($measure);
            $measure->setReferential($this);
        }

        return $this;
    }

    public function getCategories()
    {
        return $this->categories;
    }

    public function addSoaCategory(SoaCategorySuperClass $soaCategory): self
    {
        if (!$this->categories->contains($soaCategory)) {
            $this->categories->add($soaCategory);
            $soaCategory->setReferential($this);
        }

        return $this;
    }
}
