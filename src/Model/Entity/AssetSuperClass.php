<?php declare(strict_types=1);
/**
 * @link      https://github.com/monarc-project for the canonical source repository
 * @copyright Copyright (c) 2016-2022 SMILE GIE Securitymadein.lu - Licensed under GNU Affero GPL v3
 * @license   MONARC is licensed under GNU Affero General Public License version 3
 */

namespace Monarc\Core\Model\Entity;

use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\ORM\Mapping as ORM;
use Monarc\Core\Model\Entity\Traits\CreateEntityTrait;
use Monarc\Core\Model\Entity\Traits\UpdateEntityTrait;
use Ramsey\Uuid\Lazy\LazyUuidFromString;
use Ramsey\Uuid\Uuid;

/**
 * @ORM\Table(name="assets", indexes={
 *      @ORM\Index(name="anr_id", columns={"anr_id","code"}),
 *      @ORM\Index(name="anr_id2", columns={"anr_id"})
 * })
 * @ORM\MappedSuperclass
 * @ORM\HasLifecycleCallbacks()
 */
class AssetSuperClass
{
    use CreateEntityTrait;
    use UpdateEntityTrait;

    public const MODE_GENERIC = 0;
    public const MODE_SPECIFIC = 1;

    public const TYPE_PRIMARY = 1;
    public const TYPE_SECONDARY = 2;

    public const STATUS_ACTIVE = 1;
    public const STATUS_INACTIVE = 0;

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
     * @var string
     *
     * @ORM\Column(name="description1", type="string", length=255, nullable=true)
     */
    protected $description1;

    /**
     * @var string
     *
     * @ORM\Column(name="description2", type="string", length=255, nullable=true)
     */
    protected $description2;

    /**
     * @var string
     *
     * @ORM\Column(name="description3", type="string", length=255, nullable=true)
     */
    protected $description3;

    /**
     * @var string
     *
     * @ORM\Column(name="description4", type="string", length=255, nullable=true)
     */
    protected $description4;

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
            $this->amvs->remove($amv);
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
            $this->objects->remove($object);
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

    public function getDescription(int $languageIndex): string
    {
        if (!\in_array($languageIndex, range(1, 4), true)) {
            return '';
        }

        return (string)$this->{'description' . $languageIndex};
    }

    public function setDescriptions(array $descriptions): self
    {
        foreach (range(1, 4) as $index) {
            $key = 'description' . $index;
            if (isset($descriptions[$key])) {
                $this->{$key} = $descriptions[$key];
            }
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

    public function isPrimary(): bool
    {
        return $this->type === static::TYPE_PRIMARY;
    }
}
