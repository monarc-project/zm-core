<?php declare(strict_types=1);
/**
 * @link      https://github.com/monarc-project for the canonical source repository
 * @copyright Copyright (c) 2016-2024 Luxembourg House of Cybersecurity LHC.lu - Licensed under GNU Affero GPL v3
 * @license   MONARC is licensed under GNU Affero General Public License version 3
 */

namespace Monarc\Core\Entity;

use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\ORM\Mapping as ORM;
use Monarc\Core\Entity\Traits\CreateEntityTrait;
use Monarc\Core\Entity\Traits\LabelsEntityTrait;
use Monarc\Core\Entity\Traits\UpdateEntityTrait;
use Ramsey\Uuid\Uuid;
use Ramsey\Uuid\UuidInterface;

/**
 * @ORM\Table(name="measures", indexes={
 *      @ORM\Index(name="category", columns={"soacategory_id"}),
 *      @ORM\Index(name="referential", columns={"referential_uuid"})
 * })
 * @ORM\MappedSuperclass
 * @ORM\HasLifecycleCallbacks()
 */
class MeasureSuperClass
{
    use CreateEntityTrait;
    use UpdateEntityTrait;

    use LabelsEntityTrait;

    public const STATUS_ACTIVE = 1;

    /**
     * @var UuidInterface|string
     *
     * @ORM\Column(name="uuid", type="uuid", nullable=false)
     * @ORM\Id
     */
    protected $uuid;

    /**
     * @var ReferentialSuperClass
     *
     * @ORM\ManyToOne(targetEntity="Referential", inversedBy="measures")
     * @ORM\JoinColumns({
     *   @ORM\JoinColumn(name="referential_uuid", referencedColumnName="uuid", nullable=true)
     * })
     */
    protected $referential;

    /**
     * @var SoaCategorySuperClass|null
     *
     * @ORM\ManyToOne(targetEntity="SoaCategory", inversedBy="measures")
     * @ORM\JoinColumns({
     *   @ORM\JoinColumn(name="soacategory_id", referencedColumnName="id", nullable=true, onDelete="SET NULL")
     * })
     */
    protected $category;

    /**
     * @var ArrayCollection|AmvSuperClass[]
     *
     * @ORM\ManyToMany(targetEntity="Amv", inversedBy="measures")
     * @ORM\JoinTable(name="measures_amvs",
     *   inverseJoinColumns={@ORM\JoinColumn(name="amv_id", referencedColumnName="uuid")},
     *   joinColumns={@ORM\JoinColumn(name="measure_id", referencedColumnName="uuid")},
     * )
     */
    protected $amvs;

    /**
     * @var ArrayCollection|RolfRiskSuperClass[]
     *
     * @ORM\ManyToMany(targetEntity="RolfRisk", inversedBy="measures")
     * @ORM\JoinTable(name="measures_rolf_risks",
     *   inverseJoinColumns={@ORM\JoinColumn(name="rolf_risk_id", referencedColumnName="id")},
     *   joinColumns={@ORM\JoinColumn(name="measure_id", referencedColumnName="uuid")},
     * )
     */
    protected $rolfRisks;

    /**
     * @var MeasureSuperClass[]|ArrayCollection
     *
     * @ORM\ManyToMany(targetEntity="Measure")
     * @ORM\JoinTable(name="measures_measures",
     *   joinColumns={@ORM\JoinColumn(name="master_measure_id", referencedColumnName="uuid")},
     *   inverseJoinColumns={@ORM\JoinColumn(name="linked_measure_id", referencedColumnName="uuid")}
     * )
     */
    protected $linkedMeasures;

    /**
     * @var string
     *
     * @ORM\Column(name="code", type="string", length=255, nullable=true)
     */
    protected $code;

    /**
     * @var int
     *
     * @ORM\Column(name="status", type="smallint", options={"unsigned":true, "default":1})
     */
    protected $status = self::STATUS_ACTIVE;

    public function __construct()
    {
        $this->amvs = new ArrayCollection();
        $this->rolfRisks = new ArrayCollection();
        $this->linkedMeasures = new ArrayCollection();
    }

    /**
     * @ORM\PrePersist
     */
    public function generateAndSetUuid(): self
    {
        if ($this->uuid === null) {
            $this->uuid = Uuid::uuid4();
        }

        return $this;
    }

    public function getUuid(): string
    {
        return (string)$this->uuid;
    }

    /**
     * @param string $uuid
     *
     * @return self
     */
    public function setUuid($uuid): self
    {
        $this->uuid = $uuid;

        return $this;
    }

    public function getCategory(): ?SoaCategorySuperClass
    {
        return $this->category;
    }

    public function setCategory(?SoaCategorySuperClass $category): self
    {
        if ($category === null && $this->category !== null) {
            $this->category->removeMeasure($this);
        }

        $this->category = $category;
        $category?->addMeasure($this);

        return $this;
    }

    public function getReferential()
    {
        return $this->referential;
    }

    public function setReferential(ReferentialSuperClass $referential): self
    {
        $this->referential = $referential;
        $referential->addMeasure($this);

        return $this;
    }

    public function getAmvs()
    {
        return $this->amvs;
    }

    public function addAmv(AmvSuperClass $amv): self
    {
        if (!$this->amvs->contains($amv)) {
            $this->amvs->add($amv);
            $amv->addMeasure($this);
        }

        return $this;
    }

    public function removeAmv(AmvSuperClass $amv): self
    {
        if ($this->amvs->contains($amv)) {
            $this->amvs->removeElement($amv);
            $amv->removeMeasure($this);
        }

        return $this;
    }

    public function getLinkedMeasures()
    {
        return $this->linkedMeasures;
    }

    public function addLinkedMeasure(MeasureSuperClass $measure): self
    {
        if (!$this->linkedMeasures->contains($measure)) {
            $this->linkedMeasures->add($measure);
            $measure->addLinkedMeasure($this);
        }

        return $this;
    }

    public function removeLinkedMeasure(MeasureSuperClass $measure): self
    {
        if ($this->linkedMeasures->contains($measure)) {
            $this->linkedMeasures->removeElement($measure);
            $measure->removeLinkedMeasure($this);
        }

        return $this;
    }

    public function getRolfRisks()
    {
        return $this->rolfRisks;
    }

    public function addRolfRisk(RolfRiskSuperClass $rolfRisk): self
    {
        if (!$this->rolfRisks->contains($rolfRisk)) {
            $this->rolfRisks->add($rolfRisk);
            $rolfRisk->addMeasure($this);
        }

        return $this;
    }

    public function removeRolfRisk(RolfRiskSuperClass $rolfRisk): self
    {
        if ($this->rolfRisks->contains($rolfRisk)) {
            $this->rolfRisks->removeElement($rolfRisk);
            $rolfRisk->removeMeasure($this);
        }

        return $this;
    }

    public function getCode(): string
    {
        return $this->code;
    }

    public function setCode(string $code): self
    {
        $this->code = $code;

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
