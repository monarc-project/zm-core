<?php
/**
 * @link      https://github.com/monarc-project for the canonical source repository
 * @copyright Copyright (c) 2016-2020 SMILE GIE Securitymadein.lu - Licensed under GNU Affero GPL v3
 * @license   MONARC is licensed under GNU Affero General Public License version 3
 */

namespace Monarc\Core\Model\Entity;

use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\ORM\Mapping as ORM;
use Monarc\Core\Model\Entity\Traits\CreateEntityTrait;
use Monarc\Core\Model\Entity\Traits\UpdateEntityTrait;
use Ramsey\Uuid\Lazy\LazyUuidFromString;

/**
 * Measure
 *
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
    protected $measuresLinked;

    /**
     * @var string
     *
     * @ORM\Column(name="code", type="string", length=255, nullable=true)
     */
    protected $code;

    /**
     * @var string
     *
     * @ORM\Column(name="label1", type="string", length=255, nullable=true)
     */
    protected $label1;

    /**
     * @var string
     *
     * @ORM\Column(name="label2", type="string", length=255, nullable=true)
     */
    protected $label2;

    /**
     * @var string
     *
     * @ORM\Column(name="label3", type="string", length=255, nullable=true)
     */
    protected $label3;

    /**
     * @var string
     *
     * @ORM\Column(name="label4", type="string", length=255, nullable=true)
     */
    protected $label4;

    /**
     * @var int
     *
     * @ORM\Column(name="status", type="smallint", options={"unsigned":true, "default":1})
     */
    protected $status = '1';

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
        $this->measuresLinked = new ArrayCollection();
        $this->amvs = new ArrayCollection();
        $this->rolfRisks = new ArrayCollection();

        parent::__construct($obj);
    }

    public function getUuid(): ?string
    {
        return $this->uuid;
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

    public function setCategory($category): self
    {
        $this->category = $category;

        return $this;
    }

    public function getReferential()
    {
        return $this->referential;
    }

    public function setReferential(ReferentialSuperClass $referential): self
    {
        $this->referential = $referential;

        return $this;
    }

    public function getAmvs()
    {
        return $this->amvs;
    }

    /**
     * @param AmvSuperClass[] $amvs
     */
    public function setAmvs($amvs): self
    {
        $this->amvs = $amvs;

        return $this;
    }

    public function addAmv(AmvSuperClass $amv): self
    {
        if (!$this->amvs->contains($amv)) {
            $this->amvs->add($amv);
            $amv->addMeasure($this);
        }

        return $this;
    }

    public function addOpRisk(RolfRiskSuperClass $riskInput): self
    {
        if (!$this->rolfRisks->contains($riskInput)) {
            $this->rolfRisks->add($riskInput);
            $riskInput->addMeasure($this);
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

    public function deleteOpRisk(RolfRiskSuperClass $riskInput): self
    {
        if ($this->rolfRisks->contains($riskInput)) {
            $this->rolfRisks->removeElement($riskInput);
            $riskInput->removeMeasure($this);
        }

        return $this;
    }

    public function addLinkedMeasure(MeasureSuperClass $measure): self
    {
        if (!$this->measuresLinked->contains($measure)) {
            $this->measuresLinked->add($measure);
            $measure->addLinkedMeasure($this);
        }

        return $this;
    }

    public function deleteLinkedMeasure(MeasureSuperClass $measure): self
    {
        if ($this->measuresLinked->contains($measure)) {
            $this->measuresLinked->removeElement($measure);
            $measure->deleteLinkedMeasure($this);
        }

        return $this;
    }

    // TODO: rename to getLinkedMeasures, and variable name to linkedMeasures.
    public function getMeasuresLinked()
    {
        return $this->measuresLinked;
    }

    /**
     * @param MeasureSuperClass[] $measuresLinked
     */
    public function setMeasuresLinked($measuresLinked)
    {
        $this->measuresLinked = $measuresLinked;

        return $this;
    }

    public function getRolfRisks()
    {
        return $this->rolfRisks;
    }

    public function setRolfRisks($rolfRisks): self
    {
        $this->rolfRisks = $rolfRisks;

        return $this;
    }

    public function addRolfRisk(RolfRiskSuperClass $rolfRisk): self
    {
        if (!$this->rolfRisks->contains($rolfRisk)) {
            $this->rolfRisks->add($rolfRisk);
            $rolfRisk->addMeasure($this);
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

    public function getLabel1(): string
    {
        return (string)$this->label1;
    }

    public function getLabel2(): string
    {
        return (string)$this->label2;
    }

    public function getLabel3(): string
    {
        return (string)$this->label3;
    }

    public function getLabel4(): string
    {
        return (string)$this->label4;
    }

    public function setLabels(array $labels): self
    {
        foreach (range(1, 4) as $labelIndex) {
            $labelKey = 'label' . $labelIndex;
            if (isset($labels[$labelKey])) {
                $this->{$labelKey} = $labels[$labelKey];
            }
        }

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
            $validatorsCode = [];
            if (!$partial) {
                $validatorsCode = array(
                    array(
                        'name' => 'Monarc\Core\Validator\UniqueCode',
                        'options' => array(
                            'entity' => $this
                        ),
                    ),
                );
            }

            $this->inputFilter->add(array(
                'name' => 'code',
                'required' => $partial ? false : true,
                'allow_empty' => false,
                'filters' => array(),
                'validators' => $validatorsCode
            ));

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
