<?php declare(strict_types=1);

namespace Monarc\Core\Model\Entity\Traits;

use DateTime;

trait UpdateEntityTrait
{
    /**
     * @var string
     *
     * @ORM\Column(name="updater", type="string", length=255, nullable=true)
     */
    protected $updater;

    /**
     * @var DateTime
     *
     * @ORM\Column(name="updated_at", type="datetime", nullable=true)
     */
    protected $updatedAt;

    /**
     * @ORM\PreUpdate
     */
    public function setUpdatedAtValue(): void
    {
        $this->updatedAt = new DateTime();
    }

    /**
     * @ORM\PrePersist
     */
    public function resetUpdatedAtValue(): void
    {
        $this->updatedAt = null;
    }

    public function getUpdatedAt(): DateTime
    {
        return $this->updatedAt;
    }

    public function setUpdater(string $updater): self
    {
        $this->updater = $updater;

        return $this;
    }

    public function getUpdater(): string
    {
        return (string)$this->updater;
    }
}
