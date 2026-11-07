<?php
/**
 * @link      https://github.com/monarc-project for the canonical source repository
 * @copyright Copyright (c) 2016-2026 Luxembourg House of Cybersecurity LHC.lu - Licensed under GNU Affero GPL v3
 * @license   MONARC is licensed under GNU Affero General Public License version 3
 */

namespace Monarc\Core\Entity;

use Doctrine\ORM\Mapping as ORM;
use Monarc\Core\Entity\Traits\CreateEntityTrait;
use Monarc\Core\Entity\Traits\DescriptionsEntityTrait;
use Monarc\Core\Entity\Traits\UpdateEntityTrait;

/**
 * Guide
 *
 * @ORM\Table(name="guides")
 * @ORM\Entity
 * @ORM\HasLifecycleCallbacks()
 */
class Guide
{
    use CreateEntityTrait;
    use UpdateEntityTrait;
    use DescriptionsEntityTrait;

    /**
     * @ORM\Column(name="id", type="integer", nullable=false)
     * @ORM\Id
     * @ORM\GeneratedValue(strategy="IDENTITY")
     */
    protected int $id;

    /**
     * @var int
     *
     * @ORM\Column(name="type", type="smallint", options={"unsigned":true})
     */
    protected $type;

    /**
     * @var int
     *
     * @ORM\Column(name="is_with_items", type="smallint", options={"unsigned":true})
     */
    protected $isWithItems;

    public function getId(): int
    {
        return $this->id;
    }

    public function getType(): int
    {
        return $this->type;
    }

    public function setType(int $type): self
    {
        $this->type = $type;

        return $this;
    }

    public function getIsWithItems(): int
    {
        return $this->isWithItems;
    }

    public function setIsWithItems(int $isWithItems): self
    {
        $this->isWithItems = $isWithItems;

        return $this;
    }
}
