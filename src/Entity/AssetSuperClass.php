<?php declare(strict_types=1);
/**
 * @link      https://github.com/monarc-project for the canonical source repository
 * @copyright Copyright (c) 2016-2023 Luxembourg House of Cybersecurity LHC.lu - Licensed under GNU Affero GPL v3
 * @license   MONARC is licensed under GNU Affero General Public License version 3
 */

namespace Monarc\Core\Entity;

use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\ORM\Mapping as ORM;
use Monarc\Core\Entity\Traits\CreateEntityTrait;
use Monarc\Core\Entity\Traits\DescriptionsEntityTrait;
use Monarc\Core\Entity\Traits\LabelsEntityTrait;
use Monarc\Core\Entity\Traits\UpdateEntityTrait;
use Ramsey\Uuid\Lazy\LazyUuidFromString;
use Ramsey\Uuid\Uuid;
use Ramsey\Uuid\UuidInterface;

/**
 * @ORM\Table(name="assets")
 * @ORM\MappedSuperclass
 * @ORM\HasLifecycleCallbacks()
 */
class AssetSuperClass
{
    use CreateEntityTrait;
    use UpdateEntityTrait;

    use LabelsEntityTrait;
    use DescriptionsEntityTrait;

    public const MODE_GENERIC = 0;
    public const MODE_SPECIFIC = 1;

    public const TYPE_PRIMARY = 1;
    public const TYPE_SECONDARY = 2;

    public const STATUS_ACTIVE = 1;
    public const STATUS_INACTIVE = 0;

    /**
    * @var LazyUuidFromString|UuidInterface|string
    *
    * @ORM\Column(name="uuid", type="uuid", nullable=false)
    * @ORM\Id
    */
    protected $uuid;

    /**
     * @var ArrayCollection|AmvSuperClass[]
     *
     * @ORM\OneToMany(targetEntity="Amv", mappedBy="asset")
     * @ORM\OrderBy({"position": "ASC"})
     */
    protected $amvs;

    /**
     * @var ArrayCollection|ObjectSuperClass[]
     *
     * @ORM\OneToMany(targetEntity="MonarcObject", mappedBy="asset")
     */
    protected $objects;

    /**
     * @var ArrayCollection|InstanceSuperClass[]
     *
     * @ORM\OneToMany(targetEntity="Instance", mappedBy="asset")
     */
    protected $instances;

    /**
     * @var int
     *
     * @ORM\Column(name="status", type="smallint", options={"unsigned":true, "default":1})
     */
    protected $status = self::STATUS_ACTIVE;

    /**
     * @var int
     *
     * @ORM\Column(name="mode", type="smallint", options={"unsigned":true, "default":0})
     */
    protected $mode = self::MODE_GENERIC;

    /**
     * @var int
     *
     * @ORM\Column(name="type", type="smallint", options={"unsigned":true, "default":1})
     */
    protected $type = self::TYPE_PRIMARY;

    /**
     * @var string
     *
     * @ORM\Column(name="code", type="string", length=255, nullable=true)
     */
    protected $code;

    public function __construct()
    {
        $this->amvs = new ArrayCollection();
        $this->objects = new ArrayCollection();
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

    public function getAmvs()
    {
        return $this->amvs;
    }

    public function addAmv(AmvSuperClass $amv): self
    {
        if (!$this->amvs->contains($amv)) {
            $this->amvs->add($amv);
            $amv->setAsset($this);
        }

        return $this;
    }

    public function removeAmv(AmvSuperClass $amv): self
    {
        if ($this->amvs->contains($amv)) {
            $this->amvs->removeElement($amv);
        }

        return $this;
    }

    public function getObjects()
    {
        return $this->objects;
    }

    public function hasObjects(): bool
    {
        return !$this->objects->isEmpty();
    }

    public function addObject(ObjectSuperClass $object): self
    {
        if (!$this->objects->contains($object)) {
            $this->objects->add($object);
            $object->setAsset($this);
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
            $instance->setAsset($this);
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

    public function setStatus(int $status): self
    {
        $this->status = $status;

        return $this;
    }

    public function getStatus(): int
    {
        return $this->status;
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

    public function setCode(string $code): self
    {
        $this->code = $code;

        return $this;
    }

    public function getCode(): string
    {
        return (string)$this->code;
    }

    public function setType(int $type): self
    {
        $this->type = $type;

        return $this;
    }

    public function getType(): int
    {
        return $this->type;
    }

    public function getTypeName(): string
    {
        return $this->isPrimary() ? 'Primary' : 'Secondary';
    }

    public function isPrimary(): bool
    {
        return $this->type === static::TYPE_PRIMARY;
    }
}
