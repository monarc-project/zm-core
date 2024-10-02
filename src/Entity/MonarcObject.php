<?php declare(strict_types=1);
/**
 * @link      https://github.com/monarc-project for the canonical source repository
 * @copyright Copyright (c) 2016-2023 Luxembourg House of Cybersecurity LHC.lu - Licensed under GNU Affero GPL v3
 * @license   MONARC is licensed under GNU Affero General Public License version 3
 */

namespace Monarc\Core\Entity;

use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\ORM\Mapping as ORM;

/**
 * @ORM\Table(name="objects", indexes={
 *      @ORM\Index(name="object_category_id", columns={"object_category_id"}),
 *      @ORM\Index(name="asset_id", columns={"asset_id"}),
 *      @ORM\Index(name="rolf_tag_id", columns={"rolf_tag_id"})
 * })
 * @ORM\Entity
 */
class MonarcObject extends ObjectSuperClass
{
    /**
     * @var ArrayCollection|Anr[]
     *
     * @ORM\ManyToMany(targetEntity="Anr", inversedBy="objects", cascade={"persist"})
     * @ORM\JoinTable(name="anrs_objects",
     *  joinColumns={@ORM\JoinColumn(name="object_id", referencedColumnName="uuid")},
     *  inverseJoinColumns={@ORM\JoinColumn(name="anr_id", referencedColumnName="id")}
     * )
     */
    protected $anrs;

    public function __construct()
    {
        parent::__construct();

        $this->anrs = new ArrayCollection();
    }

    public function getAnrs()
    {
        return $this->anrs;
    }

    public function addAnr(Anr $anr): self
    {
        if (!$this->anrs->contains($anr)) {
            $this->anrs->add($anr);
            $anr->addObject($this);
        }

        return $this;
    }

    public function removeAnr(Anr $anr): self
    {
        if ($this->anrs->contains($anr)) {
            $this->anrs->removeElement($anr);
            $anr->removeObject($this);
        }

        return $this;
    }

    public function hasAnrLink(Anr $anr): bool
    {
        return $this->anrs->contains($anr);
    }
}
