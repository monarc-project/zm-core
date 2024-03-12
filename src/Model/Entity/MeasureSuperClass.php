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
use Monarc\Core\Model\Entity\Traits\LabelsEntityTrait;
use Monarc\Core\Model\Entity\Traits\UpdateEntityTrait;
use Ramsey\Uuid\Lazy\LazyUuidFromString;
use Ramsey\Uuid\Uuid;

/**
 * @ORM\Table(name="measures", indexes={
 *      @ORM\Index(name="category", columns={"soacategory_id"}),
 *      @ORM\Index(name="referential", columns={"referential_uuid"})
 * })
 * @ORM\MappedSuperclass
 * @ORM\HasLifecycleCallbacks()
 */
class MeasureSuperClass extends AbstractEntity
{
    use CreateEntityTrait;
    use UpdateEntityTrait;

    use LabelsEntityTrait;

    /**
     * @var LazyUuidFromString|string
     *
     * @ORM\Column(name="uuid", type="uuid", nullable=false)
     * @ORM\Id
     */
    protected $uuid;

    /**
     * @var ReferentialSuperClass
     *
     * @ORM\ManyToOne(targetEntity="Referential", inversedBy="measures", cascade={"persist"})
     * @ORM\JoinColumns({
     *   @ORM\JoinColumn(name="referential_uuid", referencedColumnName="uuid", nullable=true)
     * })
     */
    protected $referential;

    /**
     * @var SoaCategorySuperClass
     *
     * @ORM\ManyToOne(targetEntity="SoaCategory", inversedBy="measures", cascade={"persist"})
     * @ORM\JoinColumns({
     *   @ORM\JoinColumn(name="soacategory_id", referencedColumnName="id", nullable=true)
     * })
     */
    protected $category;

    /**
     * @var MeasureSuperClass[]|ArrayCollection
     *
     * @ORM\ManyToMany(targetEntity="Measure")
     * @ORM\JoinTable(name="measures_measures",
     *     joinColumns={@ORM\JoinColumn(name="father_id", referencedColumnName="uuid")},
     *     inverseJoinColumns={@ORM\JoinColumn(name="child_id", referencedColumnName="uuid")}
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
    protected $status = 1;

    /**
     * @var ArrayCollection|AmvSuperClass[]
     *
     * @ORM\ManyToMany(targetEntity="Amv", inversedBy="measures", cascade={"persist"})
     * @ORM\JoinTable(name="measures_amvs",
     *  inverseJoinColumns={@ORM\JoinColumn(name="amv_id", referencedColumnName="uuid")},
     *  joinColumns={@ORM\JoinColumn(name="measure_id", referencedColumnName="uuid"),},
     * )
     */
    protected $amvs;

    /**
     * @var ArrayCollection|RolfRiskSuperClass[]
     *
     * @ORM\ManyToMany(targetEntity="RolfRisk", inversedBy="measures", cascade={"persist"})
     * @ORM\JoinTable(name="measures_rolf_risks",
     *  inverseJoinColumns={@ORM\JoinColumn(name="rolf_risk_id", referencedColumnName="id")},
     *  joinColumns={@ORM\JoinColumn(name="measure_id", referencedColumnName="uuid"),},
     * )
     */
    protected $rolfRisks;

    public function __construct($obj = null)
    {
        parent::__construct($obj);

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

    public function getCategory()
    {
        return $this->category;
    }

    public function setCategory(SoaCategorySuperClass $category): self
    {
        $this->category = $category;
        $category->addMeasure($this);

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

    public function removeOpRisk(RolfRiskSuperClass $riskInput): self
    {
        if ($this->rolfRisks->contains($riskInput)) {
            $this->rolfRisks->removeElement($riskInput);
            $riskInput->removeMeasure($this);
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

    public function getInputFilter($partial = false)
    {
        if (!$this->inputFilter) {
            parent::getInputFilter($partial);

            $texts = ['label1', 'label2', 'label3', 'label4'];

            foreach ($texts as $text) {
                $this->inputFilter->add(array(
                    'name' => $text,
                    'required' => strpos($text, (string)$this->getLanguage()) !== false && !$partial,
                    'allow_empty' => false,
                    'filters' => array(),
                    'validators' => array(),
                ));
            }

            $this->inputFilter->add(array(
                'name' => 'status',
                'required' => false,
                'allow_empty' => false,
                'filters' => array(
                    array('name' => 'ToInt'),
                ),
                'validators' => array(
                    array(
                        'name' => 'InArray',
                        'options' => array(
                            'haystack' => array(self::STATUS_INACTIVE, self::STATUS_ACTIVE),
                        ),
                    ),
                ),
            ));
        }
        return $this->inputFilter;
    }

    public function getFiltersForService()
    {
        $filterJoin = [
            [
                'as' => 'r',
                'rel' => 'referential',
            ],
        ];
        $filterLeft = [
            [
                'as' => 'r1',
                'rel' => 'referential',
            ],
            [
                'as' => 'c',
                'rel' => 'category',
            ],
        ];
        $filtersCol = [
            'r.label1',
            'r.label2',
            'r.label3',
            'r.label4',
            'c.label1',
            'c.label2',
            'c.label3',
            'c.label4',
            'r.uuid',
            'label1',
            'label2',
            'label3',
            'label4',
            'code',
        ];

        return [$filterJoin, $filterLeft, $filtersCol];
    }
}
