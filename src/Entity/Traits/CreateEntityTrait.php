<?php declare(strict_types=1);
/**
 * @link      https://github.com/monarc-project for the canonical source repository
 * @copyright Copyright (c) 2016-2024 Luxembourg House of Cybersecurity LHC.lu - Licensed under GNU Affero GPL v3
 * @license   MONARC is licensed under GNU Affero General Public License version 3
 */

namespace Monarc\Core\Entity\Traits;

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
    public function setCreatedAtValue(): self
    {
        $this->createdAt = new DateTime();

        return $this;
    }

    public function getCreatedAt(): DateTime
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
        return (string)$this->creator;
    }
}
