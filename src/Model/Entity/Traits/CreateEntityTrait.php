<?php declare(strict_types=1);

namespace Monarc\Core\Model\Entity\Traits;

use DateTime;

trait CreateEntityTrait
{
    /**
     * @var string
     *
     * @ORM\Column(name="creator", type="string", length=255, nullable=true)
     */
    protected $creator;

    /**
     * @var DateTime
     *
     * @ORM\Column(name="created_at", type="datetime", nullable=true)
     */
    protected $createdAt;

    /**
     * @ORM\PrePersist
     */
    public function setCreatedAtValue(): void
    {
        $this->createdAt = new DateTime();
    }

    public function getCreateAt(): DateTime
    {
        return $this->createdAt;
    }

    public function setCreator(string $creator): self
    {
        $this->creator = $creator;

        return $this;
    }

    public function getCreator(): string
    {
        return $this->creator;
    }
}
