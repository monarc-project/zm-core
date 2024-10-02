<?php declare(strict_types=1);
/**
 * @link      https://github.com/monarc-project for the canonical source repository
 * @copyright Copyright (c) 2016-2024 Luxembourg House of Cybersecurity LHC.lu - Licensed under GNU Affero GPL v3
 * @license   MONARC is licensed under GNU Affero General Public License version 3
 */

namespace Monarc\Core\Entity;

use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\ORM\Mapping as ORM;

/**
 * @ORM\Table(name="objects_categories", indexes={
 *      @ORM\Index(name="root_id", columns={"root_id"}),
 *      @ORM\Index(name="parent_id", columns={"parent_id"}),
 *      @ORM\Index(name="position", columns={"position"})
 * })
 * @ORM\Entity
 */
class ObjectCategory extends ObjectCategorySuperClass
{
    /**
     * @var Anr[]|ArrayCollection
     *
     * @ORM\ManyToMany(targetEntity="Anr", mappedBy="objectCategories", cascade={"persist"})
     */
    protected $anrs;

    public function __construct()
    {
        parent::__construct();

        $this->anrs = new ArrayCollection();
    }

    public function getLinkedAnrs()
    {
        return $this->anrs;
    }

    public function hasAnrLink(Anr $anr): bool
    {
        return $this->anrs->contains($anr);
    }

    public function addAnrLink(Anr $anr): self
    {
        if (!$this->anrs->contains($anr)) {
            $this->anrs->add($anr);
            $anr->addObjectCategory($this);
        }

        return $this;
    }

    public function removeAnrLink(Anr $anr): self
    {
        if ($this->anrs->contains($anr)) {
            $this->anrs->removeElement($anr);
            $anr->removeObjectCategory($this);
        }

        return $this;
    }

    public function removeAllAnrLinks(): self
    {
        foreach ($this->anrs as $anr) {
            $this->removeAnrLink($anr);
        }

        return $this;
    }
}
