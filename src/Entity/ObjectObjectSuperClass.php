<?php declare(strict_types=1);
/**
 * @link      https://github.com/monarc-project for the canonical source repository
 * @copyright Copyright (c) 2016-2024 Luxembourg House of Cybersecurity LHC.lu - Licensed under GNU Affero GPL v3
 * @license   MONARC is licensed under GNU Affero General Public License version 3
 */

namespace Monarc\Core\Entity;

use Doctrine\ORM\Mapping as ORM;
use Monarc\Core\Entity\Interfaces\PositionedEntityInterface;
use Monarc\Core\Entity\Traits\CreateEntityTrait;
use Monarc\Core\Entity\Traits\PropertyStateEntityTrait;
use Monarc\Core\Entity\Traits\UpdateEntityTrait;

/**
 * @ORM\Table(name="objects_objects", indexes={
 *      @ORM\Index(name="father_id", columns={"father_id"}),
 *      @ORM\Index(name="child_id", columns={"child_id"})
 * })
 * @ORM\MappedSuperclass
 * @ORM\HasLifecycleCallbacks()
 */
class ObjectObjectSuperClass implements PositionedEntityInterface
{
    use CreateEntityTrait;
    use UpdateEntityTrait;

    use PropertyStateEntityTrait;

    /**
     * @var int
     *
     * @ORM\Column(name="id", type="integer", nullable=false)
     * @ORM\Id
     * @ORM\GeneratedValue(strategy="IDENTITY")
     */
    protected $id;

    /**
     * @var ObjectSuperClass
     *
     * @ORM\ManyToOne(targetEntity="MonarcObject", cascade={"persist"})
     * @ORM\JoinColumns({
     *   @ORM\JoinColumn(name="father_id", referencedColumnName="uuid", nullable=true)
     * })
     */
    protected $parent;

    /**
     * @var ObjectSuperClass
     *
     * @ORM\ManyToOne(targetEntity="MonarcObject", cascade={"persist"})
     * @ORM\JoinColumns({
     *   @ORM\JoinColumn(name="child_id", referencedColumnName="uuid", nullable=true)
     * })
     */
    protected $child;

    /**
     * @var int
     *
     * @ORM\Column(name="position", type="smallint", options={"unsigned":true, "default":1})
     */
    protected $position = 1;

    public function getImplicitPositionRelationsValues(): array
    {
        return ['parent' => $this->parent];
    }

    public function getId()
    {
        return $this->id;
    }

    public function getParent(): ObjectSuperClass
    {
        return $this->parent;
    }

    public function setParent(ObjectSuperClass $parent): self
    {
        $this->trackPropertyState('parent', $this->parent);

        $this->parent = $parent;

        return $this;
    }

    public function getChild(): ObjectSuperClass
    {
        return $this->child;
    }

    public function setChild(ObjectSuperClass $child): self
    {
        $this->child = $child;

        return $this;
    }

    public function getPosition(): int
    {
        return $this->position;
    }

    public function setPosition(int $position): self
    {
        $this->position = $position;

        return $this;
    }
}
