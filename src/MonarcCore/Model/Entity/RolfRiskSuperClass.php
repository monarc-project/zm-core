<?php
/**
 * @link      https://github.com/monarc-project for the canonical source repository
 * @copyright Copyright (c) 2016-2019  SMILE GIE Securitymadein.lu - Licensed under GNU Affero GPL v3
 * @license   MONARC is licensed under GNU Affero General Public License version 3
 */

namespace MonarcCore\Model\Entity;

use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\ORM\Mapping as ORM;
use Zend\InputFilter\InputFilter;

/**
 * RolfRisk
 *
 * @ORM\Table(name="rolf_risks", indexes={
 *      @ORM\Index(name="anr", columns={"anr_id"})
 * })
 * @ORM\MappedSuperclass
 */
class RolfRiskSuperclass extends AbstractEntity
{
    /**
     * @var integer
     *
     * @ORM\Column(name="id", type="integer", nullable=false)
     * @ORM\Id
     * @ORM\GeneratedValue(strategy="IDENTITY")
     */
    protected $id;

    /**
     * @var \MonarcCore\Model\Entity\Anr
     *
     * @ORM\ManyToOne(targetEntity="MonarcCore\Model\Entity\Anr", cascade={"persist"})
     * @ORM\JoinColumns({
     *   @ORM\JoinColumn(name="anr_id", referencedColumnName="id", nullable=true)
     * })
     */
    protected $anr;

    /**
     * @var \MonarcCore\Model\Entity\RolfTag
     *
     * @ORM\ManyToMany(targetEntity="MonarcCore\Model\Entity\RolfTag", inversedBy="risks", cascade={"persist"})
     * @ORM\JoinTable(name="rolf_risks_tags",
     *  joinColumns={@ORM\JoinColumn(name="rolf_risk_id", referencedColumnName="id")},
     *  inverseJoinColumns={@ORM\JoinColumn(name="rolf_tag_id", referencedColumnName="id")}
     * )
     */
    protected $tags;

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
     * @var string
     *
     * @ORM\Column(name="description1", type="string", length=255, nullable=true)
     */
    protected $description1;

    /**
     * @var string
     *
     * @ORM\Column(name="description2", type="string", length=255, nullable=true)
     */
    protected $description2;

    /**
     * @var string
     *
     * @ORM\Column(name="description3", type="string", length=255, nullable=true)
     */
    protected $description3;

    /**
     * @var string
     *
     * @ORM\Column(name="description4", type="string", length=255, nullable=true)
     */
    protected $description4;

    /**
     * @var string
     *
     * @ORM\Column(name="creator", type="string", length=255, nullable=true)
     */
    protected $creator;

    /**
     * @var \DateTime
     *
     * @ORM\Column(name="created_at", type="datetime", nullable=true)
     */
    protected $createdAt;

    /**
     * @var string
     *
     * @ORM\Column(name="updater", type="string", length=255, nullable=true)
     */
    protected $updater;

    /**
     * @var \DateTime
     *
     * @ORM\Column(name="updated_at", type="datetime", nullable=true)
     */
    protected $updatedAt;

    /**
     * @var \Doctrine\Common\Collections\Collection
     * @ORM\ManyToMany(targetEntity="MonarcCore\Model\Entity\Measure", mappedBy="rolfRisks", cascade={"persist"})
     * @ORM\JoinTable(name="measures_rolf_risks",
     *  joinColumns={@ORM\JoinColumn(name="rolf_risk_id", referencedColumnName="id")},
     *  inverseJoinColumns={@ORM\JoinColumn(name="measure_id", referencedColumnName="uuid")}
     * )
     */
    protected $measures;


    /**
     * @return int
     */
    public function getId()
    {
        return $this->id;
    }

    /**
     * @param int $id
     * @return RolfRisk
     */
    public function setId($id)
    {
        $this->id = $id;
        return $this;
    }

    /**
     * @return Anr
     */
    public function getAnr()
    {
        return $this->anr;
    }

    /**
     * @param Anr $anr
     * @return RolfRisk
     */
    public function setAnr($anr)
    {
        $this->anr = $anr;
        return $this;
    }

    /**
     * Set Rolf Tag
     *
     * @param $id
     * @param RolfTagSuperclass $rolfTag
     */
    public function setTag($id, RolfTagSuperclass $rolfTag)
    {
        $this->tags[$id] = $rolfTag;
    }

    public function setTags($rolfTags)
    {
        $this->tags = $rolfTags;
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

        $validatorsCode = [];
        if (!$partial) {
            $validatorsCode = array(
                array(
                    'name' => '\MonarcCore\Validator\UniqueCode',
                    'options' => array(
                        'entity' => $this
                    ),
                ),
            );
        }

        $this->inputFilter->add(array(
            'name' => 'code',
            'required' => ($partial) ? false : true,
            'allow_empty' => false,
            'filters' => array(),
            'validators' => $validatorsCode
        ));

        return $this->inputFilter;
    }

    public function __construct($obj = null)
    {
        $this->tags = new ArrayCollection();
        parent::__construct($obj);
    }


    /**
     * Get the value of Measures
     *
     * @return \Doctrine\Common\Collections\Collection
     */
    public function getMeasures()
    {
        return $this->measures;
    }


    /**
     * Set the value of Measures
     *
     * @param \Doctrine\Common\Collections\Collection measures
     *
     * @return self
     */
    public function setMeasures(\Doctrine\Common\Collections\Collection $measures)
    {
        $this->measures = $measures;

        return $this;
    }

}
