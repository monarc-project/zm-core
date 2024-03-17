<?php declare(strict_types=1);
/**
 * @link      https://github.com/monarc-project for the canonical source repository
 * @copyright Copyright (c) 2016-2024 Luxembourg House of Cybersecurity LHC.lu - Licensed under GNU Affero GPL v3
 * @license   MONARC is licensed under GNU Affero General Public License version 3
 */

namespace Monarc\Core\Entity;

use Doctrine\ORM\Mapping as ORM;
use Monarc\Core\Entity\Traits\CreateEntityTrait;
use Monarc\Core\Entity\Traits\DescriptionsEntityTrait;
use Monarc\Core\Entity\Traits\LabelsEntityTrait;
use Monarc\Core\Entity\Traits\UpdateEntityTrait;
use Ramsey\Uuid\Uuid;
use Ramsey\Uuid\UuidInterface;

/**
 * @ORM\Table(name="threats", indexes={
 *      @ORM\Index(name="theme_id", columns={"theme_id"})
 * })
 * @ORM\MappedSuperclass
 * @ORM\HasLifecycleCallbacks()
 */
class ThreatSuperClass
{
    use CreateEntityTrait;
    use UpdateEntityTrait;

    use LabelsEntityTrait;
    use DescriptionsEntityTrait;

    public const STATUS_ACTIVE = 1;
    public const STATUS_INACTIVE = 0;

    public const MODE_GENERIC = 0;
    public const MODE_SPECIFIC = 1;

    /**
    * @var UuidInterface|string
    *
    * @ORM\Column(name="uuid", type="uuid", nullable=false)
    * @ORM\Id
    */
    protected $uuid;

    /**
     * @var ThemeSuperClass
     *
     * @ORM\ManyToOne(targetEntity="Theme", cascade={"persist"})
     * @ORM\JoinColumns({
     *   @ORM\JoinColumn(name="theme_id", referencedColumnName="id", nullable=true)
     * })
     */
    protected $theme;

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
     * @var string
     *
     * @ORM\Column(name="code", type="string", length=255, nullable=true)
     */
    protected $code;

    /**
     * @var int
     *
     * @ORM\Column(name="trend", type="smallint", options={"unsigned":true, "default":0})
     */
    protected $trend = 0;

    /**
     * @var int
     *
     * @ORM\Column(name="qualification", type="smallint", options={"unsigned":false, "default":-1})
     */
    protected $qualification = -1;

    /**
     * @var int
     *
     * @ORM\Column(name="c", type="smallint", options={"unsigned":true, "default":1})
     */
    protected $c = 1;

    /**
     * @var int
     *
     * @ORM\Column(name="i", type="smallint", options={"unsigned":true, "default":1})
     */
    protected $i = 1;

    /**
     * @var int
     *
     * @ORM\Column(name="a", type="smallint", options={"unsigned":true, "default":1})
     */
    protected $a = 1;

    /**
     * @var string
     *
     * @ORM\Column(name="comment", type="text", nullable=true)
     */
    protected $comment;

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

    public function setTheme(ThemeSuperClass $theme): self
    {
        $this->theme = $theme;

        return $this;
    }

    public function getTheme(): ?ThemeSuperClass
    {
        return $this->theme;
    }

    public function getCode(): ?string
    {
        return $this->code;
    }

    public function setCode(string $code): self
    {
        $this->code = $code;

        return $this;
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

    public function setAvailability(int $a): self
    {
        $this->a= $a;

        return $this;
    }

    public function getAvailability(): int
    {
        return $this->a;
    }

    public function getStatus(): int
    {
        return (int)$this->status;
    }

    public function setStatus(int $status): self
    {
        $this->status = $status;

        return $this;
    }

    public function getMode(): int
    {
        return (int)$this->mode;
    }

    public function setMode(int $mode): self
    {
        $this->mode = $mode;

        return $this;
    }

    public function isModeSpecific(): bool
    {
        return $this->mode === self::MODE_SPECIFIC;
    }

    public function isModeGeneric(): bool
    {
        return $this->mode === self::MODE_GENERIC;
    }

    public function getTrend(): int
    {
        return (int)$this->trend;
    }

    public function setTrend(int $trend): self
    {
        $this->trend = $trend;

        return $this;
    }

    public function getQualification(): int
    {
        return (int)$this->qualification;
    }

    public function setQualification(int $qualification): self
    {
        $this->qualification = $qualification;

        return $this;
    }

    public function getComment(): string
    {
        return (string)$this->comment;
    }

    public function setComment(string $comment): self
    {
        $this->comment = $comment;

        return $this;
    }
}
