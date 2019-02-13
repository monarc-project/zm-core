<?php
/**
* @link      https://github.com/monarc-project for the canonical source repository
* @copyright Copyright (c) 2016-2019  SMILE GIE Securitymadein.lu - Licensed under GNU Affero GPL v3
* @license   MONARC is licensed under GNU Affero General Public License version 3
*/

namespace MonarcCore\Model\Entity;

use Doctrine\ORM\Mapping as ORM;
use Ramsey\Uuid\UuidInterface;

/**
* Measure
*
* @ORM\Table(name="measures", indexes={
*      @ORM\Index(name="category", columns={"soacategory_id"}),
*      @ORM\Index(name="referential", columns={"referential_uuid"})
* })
* @ORM\MappedSuperclass
*/
class MeasureSuperClass extends AbstractEntity
{

  /**
   * @ORM\ManyToMany(targetEntity="MonarcCore\Model\Entity\Measure")
   * @ORM\JoinTable(name="measures_measures",
   *     joinColumns={@ORM\JoinColumn(name="father_id", referencedColumnName="uuid")},
   *     inverseJoinColumns={@ORM\JoinColumn(name="child_id", referencedColumnName="uuid")}
   * )
   */
   protected $measuresLinked;

    /**
    * @var integer
    *
    * @ORM\Column(name="uuid", type="uuid", nullable=false)
    * @ORM\Id
    */
    protected $uuid;

    /**
     * @var \MonarcCore\Model\Entity\Referential
     *
     * @ORM\ManyToOne(targetEntity="MonarcCore\Model\Entity\Referential", inversedBy="measures", cascade={"persist"})
     * @ORM\JoinColumns({
     *   @ORM\JoinColumn(name="referential_uuid", referencedColumnName="uuid", nullable=true)
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
    * @ORM\ManyToMany(targetEntity="MonarcCore\Model\Entity\Amv", inversedBy="measures", cascade={"persist"})
    * @ORM\JoinTable(name="measures_amvs",
    *  inverseJoinColumns={@ORM\JoinColumn(name="amv_id", referencedColumnName="id")},
    *  joinColumns={@ORM\JoinColumn(name="measure_id", referencedColumnName="uuid"),},
    * )
    */
    protected $amvs;

    /**
    * @var \Doctrine\Common\Collections\Collection
    * @ORM\ManyToMany(targetEntity="MonarcCore\Model\Entity\RolfRisk", inversedBy="measures", cascade={"persist"})
    * @ORM\JoinTable(name="measures_rolf_risks",
    *  inverseJoinColumns={@ORM\JoinColumn(name="rolf_risk_id", referencedColumnName="id")},
    *  joinColumns={@ORM\JoinColumn(name="measure_uuid", referencedColumnName="uuid"),},
    * )
    */
    protected $rolfRisks;

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
                if ($currentMeasure->uuid == $measure->uuid) {
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
                if ($currentMeasure->uuid == $measure->uuid) {
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
    public function getuuid(): UuidInterface
    {
        return $this->uuid;
    }

    /**
     * @param UuidInterface $uuid
     * @return Referential
     */
    public function setuuid($uuid)
    {
        $this->uuid = $uuid;
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
     * @return Amv
     */
    public function getAmvs()
    {
        return $this->amvs;
    }

    /**
     * @param Amv $amvs
     * @return Measure
     */
    public function setAmvs($amvs)
    {
        $this->amvs = $amvs;
        return $this;
    }

    /**
     * @param Amv $amv
     * @return Measure
     */
    public function addAmv($amv)
    {
        $this->amvs[] = $amv;
        return $this;
    }

    public function AddOpRisk($riskInput)
    {
        $this->rolfRisks->add($riskInput);
        return $this;
    }

    /**
     * @param Amv $amv
     * @return Measure
     */
    public function deleteAmv($amv)
    {
      $this->amvs->removeElement($amv);
      // $currentAmvs = $this->amvs;
      // $i=0;
      // foreach ($currentAmvs as $currentAmv) {
      //     if ($currentAmv->id == $amv) {
      //         unset($currentAmvs[$i]);
      //         file_put_contents('php://stderr', print_r('$id', TRUE).PHP_EOL);
      //     }
      //     $i++;
      //   }
      // $this->amvs = $currentAmvs;
      // return $this;
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
        return [$filterJoin,$filterLeft,$filtersCol];
    }
}
