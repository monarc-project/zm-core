<?php declare(strict_types=1);
/**
 * @link      https://github.com/monarc-project for the canonical source repository
 * @copyright Copyright (c) 2016-2025 Luxembourg House of Cybersecurity LHC.lu - Licensed under GNU Affero GPL v3
 * @license   MONARC is licensed under GNU Affero General Public License version 3
 */

namespace Monarc\Core\Entity;

use Doctrine\ORM\Mapping as ORM;

/**
 * @ORM\Table(name="actions_history")
 * @ORM\MappedSuperclass
 * @ORM\HasLifecycleCallbacks()
 */
class ActionHistorySuperClass
{
    use Traits\CreateEntityTrait;

    public const ACTION_LOGIN_ATTEMPT = 'login_attempt';

    public const STATUS_SUCCESS = 0;
    public const STATUS_FAILURE = 1;

    /**
     * @var int
     *
     * @ORM\Column(name="id", type="integer", nullable=false)
     * @ORM\Id
     * @ORM\GeneratedValue(strategy="IDENTITY")
     */
    protected $id;

    /**
     * @var string
     *
     * @ORM\Column(name="action", type="string", length=100, nullable=false)
     */
    protected $action;

    /**
     * @var string
     *
     * @ORM\Column(name="data", type="string", length=4096, nullable=false)
     */
    protected $data = '';

    /**
     * @var ?UserSuperClass
     *
     * @ORM\ManyToOne(targetEntity="User", cascade={"persist"})
     * @ORM\JoinColumns({
     *   @ORM\JoinColumn(name="user_id", referencedColumnName="id", nullable=true)
     * })
     */
    protected $user;

    /**
     * @var int
     *
     * @ORM\Column(name="status", type="smallint", options={"unsigned":true, "default":0})
     */
    protected $status = self::STATUS_SUCCESS;

    public function getId(): ?int
    {
        return $this->id;
    }

    public function getAction(): string
    {
        return $this->action;
    }

    public function setAction(string $action): self
    {
        $this->action = $action;

        return $this;
    }

    public function getData(): string
    {
        return $this->data;
    }

    public function setData(string $data): self
    {
        $this->data = $data;

        return $this;
    }

    public function getUser(): ?UserSuperClass
    {
        return $this->user;
    }

    public function setUser(?UserSuperClass $user): self
    {
        $this->user = $user;

        return $this;
    }

    public function getStatus(): int
    {
        return $this->status;
    }

    public function setStatus(int $status): self
    {
        $this->status = $status;

        return $this;
    }
}
