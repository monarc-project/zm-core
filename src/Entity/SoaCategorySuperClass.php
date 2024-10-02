<?php declare(strict_types=1);
/**
 * @link      https://github.com/monarc-project for the canonical source repository
 * @copyright Copyright (c) 2016-2024 Luxembourg House of Cybersecurity LHC.lu - Licensed under GNU Affero GPL v3
 * @license   MONARC is licensed under GNU Affero General Public License version 3
 */

namespace Monarc\Core\Entity;

use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\ORM\Mapping as ORM;
use Monarc\Core\Entity\Traits\LabelsEntityTrait;

/**
 * @ORM\Table(name="soacategory", indexes={
 *       @ORM\Index(name="referential", columns={"referential_uuid"})
 * })
 * @ORM\MappedSuperclass
 */
class SoaCategorySuperClass
{
    use LabelsEntityTrait;

    /**
     * @var int
     *
     * @ORM\Column(name="id", type="integer", nullable=false)
     * @ORM\Id
     * @ORM\GeneratedValue(strategy="IDENTITY")
     */
    protected $id;

    /**
     * @var ReferentialSuperClass
     *
     * @ORM\ManyToOne(targetEntity="Referential", inversedBy="categories")
     * @ORM\JoinColumns({
     *   @ORM\JoinColumn(name="referential_uuid", referencedColumnName="uuid", nullable=true)
     * })
     */
    protected $referential;

    /**
     * @var MeasureSuperClass[]|ArrayCollection
     *
     * @ORM\OneToMany(targetEntity="Measure", mappedBy="category")
     */
    protected $measures;

    /**
     * @var int
     *
     * @ORM\Column(name="status", type="smallint", options={"unsigned":true, "default":1})
     */
    protected $status = 1;

    public function __construct()
    {
        $this->measures = new ArrayCollection();
    }

    public function getId()
    {
        return $this->id;
    }

    public function getMeasures()
    {
        return $this->measures;
    }

    public function addMeasure(MeasureSuperClass $measure): self
    {
        if (!$this->measures->contains($measure)) {
            $this->measures->add($measure);
            $measure->setCategory($this);
        }

        return $this;
    }

    public function removeMeasure(MeasureSuperClass $measure): self
    {
        if ($this->measures->contains($measure)) {
            $this->measures->removeElement($measure);
        }

        return $this;
    }

    public function getReferential()
    {
        return $this->referential;
    }

    public function setReferential(ReferentialSuperClass $referential): self
    {
        $this->referential = $referential;
        $referential->addSoaCategory($this);

        return $this;
    }

    public function setStatus(int $status): self
    {
        $this->status = $status;

        return $this;
    }

    public function getStatus(): int
    {
        return $this->status;
    }
}
