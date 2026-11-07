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
 * @ORM\Table(name="guides_items", indexes={
 *      @ORM\Index(name="guide_id", columns={"guide_id"}),
 *      @ORM\Index(name="position", columns={"position"})
 * })
 * @ORM\Entity
 * @ORM\AttributeOverrides({
 *   @ORM\AttributeOverride(name="description1", column=@ORM\Column(name="description1", type="text", nullable=false)),
 *   @ORM\AttributeOverride(name="description2", column=@ORM\Column(name="description2", type="text", nullable=false)),
 *   @ORM\AttributeOverride(name="description3", column=@ORM\Column(name="description3", type="text", nullable=false)),
 *   @ORM\AttributeOverride(name="description4", column=@ORM\Column(name="description4", type="text", nullable=false))
 * })
 * @ORM\HasLifecycleCallbacks()
 */
class GuideItem
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
     * @var Guide
     *
     * @ORM\ManyToOne(targetEntity="Monarc\Core\Entity\Guide")
     * @ORM\JoinColumns({
     *   @ORM\JoinColumn(name="guide_id", referencedColumnName="id", nullable=false)
     * })
     */
    protected Guide $guide;

    /**
     * @var int
     *
     * @ORM\Column(name="position", type="smallint", options={"unsigned":true})
     */
    protected $position;

    public function __construct()
    {
        $this->setDescriptions(array_fill_keys(['description1', 'description2', 'description3', 'description4',], ''));
    }

    public function getId(): int
    {
        return $this->id;
    }

    public function getGuide(): Guide
    {
        return $this->guide;
    }

    public function setGuide(Guide $guide): self
    {
        $this->guide = $guide;

        return $this;
    }

    public function getPosition(): ?int
    {
        return $this->position;
    }

    public function setPosition(int $position): self
    {
        $this->position = $position;

        return $this;
    }
}
