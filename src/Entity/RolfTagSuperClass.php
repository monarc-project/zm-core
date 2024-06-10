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

/**
 * @ORM\Table(name="rolf_tags", indexes={@ORM\Index(name="code", columns={"code"})})
 * @ORM\MappedSuperclass
 * @ORM\HasLifecycleCallbacks()
 */
class RolfTagSuperClass
{
    use CreateEntityTrait;
    use UpdateEntityTrait;

    use LabelsEntityTrait;

    /**
     * @var int
     *
     * @ORM\Column(name="id", type="integer", nullable=false)
     * @ORM\Id
     * @ORM\GeneratedValue(strategy="IDENTITY")
     */
    protected $id;

    /**
     * @var RolfRiskSuperClass[]|ArrayCollection
     *
     * @ORM\ManyToMany(targetEntity="RolfRisk", mappedBy="tags")
     */
    protected $risks;

    /**
     * @var ArrayCollection|ObjectSuperClass[]
     *
     * @ORM\OneToMany(targetEntity="MonarcObject", mappedBy="rolfTag")
     */
    protected $objects;

    /**
     * @var string
     *
     * @ORM\Column(name="code", type="string", length=255, nullable=true)
     */
    protected $code;

    public function __construct()
    {
        $this->risks = new ArrayCollection();
        $this->objects = new ArrayCollection();
    }

    public function getId()
    {
        return $this->id;
    }

    public function getRisks()
    {
        return $this->risks;
    }

    public function addRisk(RolfRiskSuperClass $rolfRisk): self
    {
        if (!$this->risks->contains($rolfRisk)) {
            $this->risks->add($rolfRisk);
            $rolfRisk->addTag($this);
        }

        return $this;
    }

    public function removeRisk(RolfRiskSuperClass $rolfRisk): self
    {
        if ($this->risks->contains($rolfRisk)) {
            $this->risks->removeElement($rolfRisk);
            $rolfRisk->removeTag($this);
        }

        return $this;
    }

    public function getObjects()
    {
        return $this->objects;
    }

    public function addObject(ObjectSuperClass $object): self
    {
        if (!$this->objects->contains($object)) {
            $this->objects->add($object);
            $object->setRolfTag($this);
        }

        return $this;
    }

    public function removeObject(ObjectSuperClass $object): self
    {
        if ($this->objects->contains($object)) {
            $this->objects->removeElement($object);
        }

        return $this;
    }

    public function getCode(): string
    {
        return (string)$this->code;
    }

    public function setCode(string $code): self
    {
        $this->code = $code;

        return $this;
    }
}
