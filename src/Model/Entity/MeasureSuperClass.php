<?php
/**
* @link      https://github.com/monarc-project for the canonical source repository
* @copyright Copyright (c) 2016-2019  SMILE GIE Securitymadein.lu - Licensed under GNU Affero GPL v3
* @license   MONARC is licensed under GNU Affero General Public License version 3
*/

namespace Monarc\Core\Model\Entity;

use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\ORM\Mapping as ORM;
use Monarc\Core\Model\Entity\Traits\CreateEntityTrait;
use Monarc\Core\Model\Entity\Traits\UpdateEntityTrait;
use Ramsey\Uuid\Uuid;

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
     * @var Uuid
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
        parent::__construct($obj);

        $this->measuresLinked = new ArrayCollection();
        $this->amvs = new ArrayCollection();
        $this->rolfRisks = new ArrayCollection();
    }

    public function getUuid(): Uuid
    {
        return $this->uuid;
    }

    /**
     * @param Uuid $uuid
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

    public function setCategory($category)
    {
        $this->category = $category;
    }

    public function getReferential()
    {
        return $this->referential;
    }

    /**
    * @param Referential $referential
    */
    public function setReferential($referential): self
    {
        $this->referential = $referential;

        return $this;
    }

    /**
     * @return Amv[]
     */
    public function getAmvs()
    {
        return $this->amvs;
    }

    /**
     * @param Amv[] $amvs
     */
    public function setAmvs($amvs): self
    {
        $this->amvs = $amvs;

        return $this;
    }

    /**
     * @param Amv $amv
     */
    public function addAmv($amv): self
    {
        if (!$this->getAmvs()->contains($amv)) {
            $this->amvs->add($amv);
        }

        return $this;
    }

    /**
     * @param RolfRisk $riskInput
     */
    public function addOpRisk($riskInput): self
    {
        if (!$this->getRolfRisks()->contains($riskInput)) {
            $this->rolfRisks->add($riskInput);
        }

        return $this;
    }

    /**
     * @param Amv $amv
     */
    public function deleteAmv($amv): self
    {
        $this->amvs->removeElement($amv);

        return $this;
    }

    /**
     * @param RolfRisk $riskInput
     * @return Measure
     */
    public function deleteOpRisk($riskInput): self
    {
        $this->rolfRisks->removeElement($riskInput);

        return $this;
    }

    public function addLinkedMeasure(MeasureSuperClass $measure)
    {
        if (!$this->measuresLinked->contains($measure)) {
            $this->measuresLinked->add($measure);
            $measure->addLinkedMeasure($this);
        }
    }

    public function deleteLinkedMeasure(MeasureSuperClass $measure)
    {
        if ($this->measuresLinked->contains($measure)) {
            $this->measuresLinked->removeElement($measure);
            $measure->deleteLinkedMeasure($this);
        }
    }

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

    /**
     * Get the value of Rolf Risks
     *
     * @return Collection
     */
    public function getRolfRisks()
    {
        return $this->rolfRisks;
    }

    /**
     * Set the value of Rolf Risks
     *
     * @param Collection rolfRisks
     *
     * @return self
     */
    public function setRolfRisks(Collection $rolfRisks)
    {
        $this->rolfRisks = $rolfRisks;

        return $this;
    }
}
