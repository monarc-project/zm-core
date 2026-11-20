<?php declare(strict_types=1);
/**
 * @link      https://github.com/monarc-project for the canonical source repository
 * @copyright Copyright (c) 2016-2026 Luxembourg House of Cybersecurity LHC.lu - Licensed under GNU Affero GPL v3
 * @license   MONARC is licensed under GNU Affero General Public License version 3
 */

namespace Monarc\Core\Entity;

use Doctrine\ORM\Mapping as ORM;
use Monarc\Core\Entity\Traits\CreateEntityTrait;
use Monarc\Core\Entity\Traits\LabelsEntityTrait;

#[ORM\Table(name: 'historicals')]
#[ORM\Entity]
#[ORM\HasLifecycleCallbacks]
class Historical
{
    use LabelsEntityTrait;
    use CreateEntityTrait;

    #[ORM\Column(name: 'id', type: 'integer', nullable: false)]
    #[ORM\Id]
    #[ORM\GeneratedValue(strategy: 'IDENTITY')]
    protected int $id;

    #[ORM\Column(name: 'type', type: 'string', length: 255, nullable: true)]
    protected ?string $type = null;

    #[ORM\Column(name: 'source_id', type: 'string', length: 64, nullable: true)]
    protected ?string $sourceId = null;

    #[ORM\Column(name: 'action', type: 'string', length: 255, nullable: true)]
    protected ?string $action = null;

    #[ORM\Column(name: 'details', type: 'text', nullable: true)]
    protected ?string $details = null;

    public function getId(): int
    {
        return $this->id;
    }

    public function getType(): ?string
    {
        return $this->type;
    }

    public function setType(?string $type): self
    {
        $this->type = $type;

        return $this;
    }

    public function getSourceId(): ?string
    {
        return $this->sourceId;
    }

    public function setSourceId(?string $sourceId): self
    {
        $this->sourceId = $sourceId;

        return $this;
    }

    public function getAction(): ?string
    {
        return $this->action;
    }

    public function setAction(?string $action): self
    {
        $this->action = $action;

        return $this;
    }

    public function getDetails(): ?string
    {
        return $this->details;
    }

    public function setDetails(?string $details): self
    {
        $this->details = $details;

        return $this;
    }
}
