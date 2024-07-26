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
use Monarc\Core\Entity\Traits\DescriptionsEntityTrait;
use Monarc\Core\Entity\Traits\LabelsEntityTrait;
use Monarc\Core\Entity\Traits\UpdateEntityTrait;

/**
 * @ORM\Table(name="rolf_risks", indexes={
 *      @ORM\Index(name="code", columns={"code"})
 * })
 * @ORM\MappedSuperclass
 * @ORM\HasLifecycleCallbacks()
 */
class RolfRiskSuperClass
{
    use CreateEntityTrait;
    use UpdateEntityTrait;

    use LabelsEntityTrait;
    use DescriptionsEntityTrait;

    /**
     * @var int
     *
     * @ORM\Column(name="id", type="integer", nullable=false)
     * @ORM\Id
     * @ORM\GeneratedValue(strategy="IDENTITY")
     */
    protected $id;

    /**
     * @var RolfTagSuperClass[]|ArrayCollection
     *
     * @ORM\ManyToMany(targetEntity="RolfTag", inversedBy="risks")
     * @ORM\JoinTable(name="rolf_risks_tags",
     *  joinColumns={@ORM\JoinColumn(name="rolf_risk_id", referencedColumnName="id")},
     *  inverseJoinColumns={@ORM\JoinColumn(name="rolf_tag_id", referencedColumnName="id")}
     * )
     */
    protected $tags;

    /**
     * @var MeasureSuperClass[]|ArrayCollection
     *
     * @ORM\ManyToMany(targetEntity="Measure", mappedBy="rolfRisks")
     * @ORM\JoinTable(name="measures_rolf_risks",
     *  joinColumns={@ORM\JoinColumn(name="rolf_risk_id", referencedColumnName="id")},
     *  inverseJoinColumns={@ORM\JoinColumn(name="measure_id", referencedColumnName="uuid")}
     * )
     */
    protected $measures;

    /**
     * @var string
     *
     * @ORM\Column(name="code", type="string", length=255, nullable=true)
     */
    protected $code;

    public function __construct()
    {
        $this->tags = new ArrayCollection();
        $this->measures = new ArrayCollection();
    }

    public function getId()
    {
        return $this->id;
    }

    public function addTag(RolfTagSuperClass $rolfTag): self
    {
        if (!$this->tags->contains($rolfTag)) {
            $this->tags->add($rolfTag);
            $rolfTag->addRisk($this);
        }

        return $this;
    }

    public function removeTag(RolfTagSuperClass $rolfTag): self
    {
        if ($this->tags->contains($rolfTag)) {
            $this->tags->removeElement($rolfTag);
            $rolfTag->removeRisk($this);
        }

        return $this;
    }

    public function hasRolfTag(RolfTagSuperClass $rolfTag): bool
    {
        return $this->tags->contains($rolfTag);
    }

    public function getTags()
    {
        return $this->tags;
    }

    public function getMeasures()
    {
        return $this->measures;
    }

    public function addMeasure(MeasureSuperClass $measure): self
    {
        if (!$this->measures->contains($measure)) {
            $this->measures->add($measure);
            $measure->addRolfRisk($this);
        }

        return $this;
    }

    public function removeMeasure(MeasureSuperClass $measure): self
    {
        if ($this->measures->contains($measure)) {
            $this->measures->removeElement($measure);
            $measure->removeRolfRisk($this);
        }

        return $this;
    }

    public function removeAllMeasures(): self
    {
        $this->measures->clear();

        return $this;
    }

    public function getCode(): string
    {
        return (string)$this->code;
    }

    public function setCode(string $code): self
    {
        $this->code = $code;

        return $this;
    }

    public function areLabelsDifferent(array $data): bool
    {
        foreach ([1, 2, 3, 4] as $labelNum) {
            $labelKey = 'label' . $labelNum;
            if (isset($data[$labelKey]) && $data[$labelKey] !== $this->getLabel($labelNum)) {
                return true;
            }
        }

        return false;
    }

    public function areDescriptionsDifferent(array $data): bool
    {
        foreach ([1, 2, 3, 4] as $descriptionNum) {
            $descriptionKey = 'label' . $descriptionNum;
            if (isset($data[$descriptionKey]) && $data[$descriptionKey] !== $this->getDescription($descriptionNum)) {
                return true;
            }
        }

        return false;
    }
}
