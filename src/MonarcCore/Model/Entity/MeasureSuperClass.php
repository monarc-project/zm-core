<?php
/**
* @link      https://github.com/monarc-project for the canonical source repository
* @copyright Copyright (c) 2018 SMILE GIE Securitymadein.lu - Licensed under GNU Affero GPL v3
* @license   MONARC is licensed under GNU Affero General Public License version 3
*/

namespace MonarcCore\Model\Entity;

use Doctrine\ORM\Mapping as ORM;
use Ramsey\Uuid\UuidInterface;

/**
* Measure
*
* @ORM\Table(name="measures", indexes={
*      @ORM\Index(name="anr", columns={"anr_id"}),
*      @ORM\Index(name="category", columns={"soacategory_id"}),
*      @ORM\Index(name="referential", columns={"referential_uniqid"})
* })
* @ORM\MappedSuperclass
*/
class MeasureSuperClass extends AbstractEntity
{

  /**
   * @ORM\ManyToMany(targetEntity="MonarcCore\Model\Entity\Measure")
   * @ORM\JoinTable(name="measures_measures",
   *     joinColumns={@ORM\JoinColumn(name="father_id", referencedColumnName="uniqid")},
   *     inverseJoinColumns={@ORM\JoinColumn(name="child_id", referencedColumnName="uniqid")}
   * )
   */
   protected $measuresLinked;

    /**
    * @var integer
    *
    * @ORM\Column(name="uniqid", type="uuid", nullable=false)
    * @ORM\Id
    */
    protected $uniqid;

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
     * @var \MonarcCore\Model\Entity\Referential
     *
     * @ORM\ManyToOne(targetEntity="MonarcCore\Model\Entity\Referential", inversedBy="measures", cascade={"persist"})
     * @ORM\JoinColumns({
     *   @ORM\JoinColumn(name="referential_uniqid", referencedColumnName="uniqid", nullable=true)
     * })
     */
    protected $referential;

    /**
    * @var \MonarcFO\Model\Entity\SoaCategory
    *
    * @ORM\ManyToOne(targetEntity="MonarcCore\Model\Entity\SoaCategory", inversedBy="measures", cascade={"persist"})
    * @ORM\JoinColumns({
    *   @ORM\JoinColumn(name="soacategory_id", referencedColumnName="id", nullable=true)
    * })
    */
    protected $category;

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
    * @var smallint
    *
    * @ORM\Column(name="status", type="smallint", options={"unsigned":true, "default":1})
    */
    protected $status = '1';

    /**
    * @var \Doctrine\Common\Collections\Collection
    * @ORM\ManyToMany(targetEntity="MonarcCore\Model\Entity\Amv", mappedBy="measures", cascade={"persist"})
    */
    protected $amvs;

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
   * Add a linked measure in the two way
   *
   * @param Measure $measure
   * @throws \Exception
   */
    public function addLinkedMeasure( $measure)
    {
      $errors=false;
        $currentMeasures = $this->measuresLinked;
        if ($currentMeasures) {
            foreach ($currentMeasures as $currentMeasure) {
                if ($currentMeasure->uniqid == $measure->uniqid) {
                    $errors = true;
                }
            }
        }
        if (!$errors) {
            $this->measuresLinked[] = $measure;
            $measure->addLinkedMeasure($this); //add the measure in the other way
        }
    }

    /**
   * delete a linked measure in the two way
   *
   * @param Measure $measure
   * @throws \Exception
   */
    public function deleteLinkedMeasure(MeasureSuperClass $measure)
    {
      $delete = false;
      $i=0;
        $currentMeasures = $this->measuresLinked;
        if ($currentMeasures) {
            foreach ($currentMeasures as $currentMeasure) {
                if ($currentMeasure->uniqid == $measure->uniqid) {
                    unset($currentMeasures[$i]);
                    $delete = true;
                }
                $i++;
            }
        }
        if ($delete) {
            $measure->deleteLinkedMeasure($this); //delete the measure in the other way
        }
    }

    /**
     * @return UuidInterface
     */
    public function getUniqid(): UuidInterface
    {
        return $this->uniqid;
    }

    /**
     * @param UuidInterface $uniqid
     * @return Referential
     */
    public function setUniqid($uniqid)
    {
        $this->uniqid = $uniqid;
        return $this;
    }

    /**
    * @return int
    */
    public function getAnr()
    {
        return $this->anr;
    }

    /**
    * @param int $anr
    * @return Measure
    */
    public function setAnr($anr)
    {
        $this->anr = $anr;
        return $this;
    }

    /**
    * @return Category
    */
    public function getCategory()
    {
        return $this->category;
    }

    /**
    * @param Category $category
    */
    public function setCategory($category)
    {
        $this->category = $category;
    }

    /**
    * @return Referential
    */
    public function getReferential()
    {
        return $this->referential;
    }

    /**
    * @param Referential $referential
    */
    public function setReferential($referential)
    {
        $this->referential = $referential;
    }

    /**
    * @return measuresLinked
    */
    public function getMeasuresLinked()
    {
        return $this->measuresLinked;
    }

    /**
    * @param measuresLinked $measuresLinked
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
                    'required' => ((strchr($text, (string)$this->getLanguage())) && (!$partial)) ? true : false,
                    'allow_empty' => false,
                    'filters' => array(),
                    'validators' => array(),
                ));
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

    public function getFiltersForService(){
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
        ];
        $filtersCol = [
            'r.label1',
            'r.label2',
            'r.label3',
            'r.uniqid',
        ];
        return [$filterJoin,$filterLeft,$filtersCol];
    }
}
