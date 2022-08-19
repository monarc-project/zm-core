<?php declare(strict_types=1);
/**
 * @link      https://github.com/monarc-project for the canonical source repository
 * @copyright Copyright (c) 2016-2022 SMILE GIE Securitymadein.lu - Licensed under GNU Affero GPL v3
 * @license   MONARC is licensed under GNU Affero General Public License version 3
 */

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
    public function setUpdatedAtValue(): self
    {
        $this->updatedAt = new DateTime();

        return $this;
    }

    /**
     * @ORM\PrePersist
     */
    public function resetUpdatedAtValue(): self
    {
        $this->updatedAt = null;

        return $this;
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
