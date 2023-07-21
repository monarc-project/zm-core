<?php
/**
 * @link      https://github.com/monarc-project for the canonical source repository
 * @copyright Copyright (c) 2016-2020 SMILE GIE Securitymadein.lu - Licensed under GNU Affero GPL v3
 * @license   MONARC is licensed under GNU Affero General Public License version 3
 */

namespace Monarc\Core\Model\Entity;

use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\ORM\Mapping as ORM;
use Monarc\Core\Model\Entity\Traits\CreateEntityTrait;
use Monarc\Core\Model\Entity\Traits\DescriptionsEntityTrait;
use Monarc\Core\Model\Entity\Traits\LabelsEntityTrait;
use Monarc\Core\Model\Entity\Traits\UpdateEntityTrait;

/**
 * RolfRisk
 *
 * @ORM\Table(name="rolf_risks", indexes={
 *      @ORM\Index(name="anr", columns={"anr_id"})
 * })
 * @ORM\MappedSuperclass
 * @ORM\HasLifecycleCallbacks()
 */
class RolfRiskSuperClass extends AbstractEntity
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
     * @var AnrSuperClass
     *
     * @ORM\ManyToOne(targetEntity="Anr", cascade={"persist"})
     * @ORM\JoinColumns({
     *   @ORM\JoinColumn(name="anr_id", referencedColumnName="id", nullable=true)
     * })
     */
    protected $anr;

    /**
     * @var RolfTagSuperClass[]|ArrayCollection
     *
     * @ORM\ManyToMany(targetEntity="RolfTag", inversedBy="risks", cascade={"persist"})
     * @ORM\JoinTable(name="rolf_risks_tags",
     *  joinColumns={@ORM\JoinColumn(name="rolf_risk_id", referencedColumnName="id")},
     *  inverseJoinColumns={@ORM\JoinColumn(name="rolf_tag_id", referencedColumnName="id")}
     * )
     */
    protected $tags;

    /**
     * @var MeasureSuperClass[]|ArrayCollection
     * @ORM\ManyToMany(targetEntity="Measure", mappedBy="rolfRisks", cascade={"persist"})
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

    public function __construct($obj = null)
    {
        parent::__construct($obj);

        $this->tags = new ArrayCollection();
        $this->measures = new ArrayCollection();
    }

    public function getId()
    {
        return $this->id;
    }

    public function setId($id): self
    {
        $this->id = $id;

        return $this;
    }

    public function getAnr(): AnrSuperClass
    {
        return $this->anr;
    }

    public function setAnr($anr): self
    {
        $this->anr = $anr;

        return $this;
    }

    /**
     * TODO: replace all the method usage as well as setTags() to addTag().
     *
     * Set Rolf Tag
     *
     * @param $id
     * @param RolfTagSuperclass $rolfTag
     */
    public function setTag($id, $rolfTag)
    {
        $this->tags[$id] = $rolfTag;
    }

    public function addTag(RolfTagSuperClass $rolfTag): self
    {
        if (!$this->tags->contains($rolfTag)) {
            $this->tags->add($rolfTag);
            $rolfTag->addRisk($this);
        }

        return $this;
    }

    public function setTags($rolfTags)
    {
        $this->tags = $rolfTags;
    }

    /**
     * @return RolfTagSuperClass[]
     */
    public function getTags()
    {
        return $this->tags;
    }

    /**
     * @return MeasureSuperClass[]
     */
    public function getMeasures()
    {
        return $this->measures;
    }

    /**
     * TODO: remove and use addMeasure instead
     *
     * @param MeasureSuperClass[] measures
     *
     * @return self
     */
    public function setMeasures($measures): self
    {
        $this->measures = $measures;

        return $this;
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
            $measure->deleteOpRisk($this);
        }

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

    public function getInputFilter($partial = false)
    {
        if (!$this->inputFilter) {
            parent::getInputFilter($partial);

            $texts = ['label1', 'label2', 'label3', 'label4'];
            foreach ($texts as $text) {
                $this->inputFilter->add(array(
                    'name' => $text,
                    'required' => false,
                    'allow_empty' => false,
                    'filters' => array(),
                    'validators' => array(),
                ));
            }

            $descriptions = ['description1', 'description2', 'description3', 'description4'];
            foreach ($descriptions as $description) {
                $this->inputFilter->add(array(
                    'name' => $description,
                    'required' => false,
                    'allow_empty' => true,
                    'filters' => array(),
                    'validators' => array(),
                ));
            }
        }

        return $this->inputFilter;
    }
}
