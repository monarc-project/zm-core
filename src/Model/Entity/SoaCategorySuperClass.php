<?php
/**
* @link      https://github.com/monarc-project for the canonical source repository
* @copyright Copyright (c) 2016-2020 SMILE GIE Securitymadein.lu - Licensed under GNU Affero GPL v3
* @license   MONARC is licensed under GNU Affero General Public License version 3
*/

namespace Monarc\Core\Model\Entity;

use Doctrine\ORM\Mapping as ORM;
use Monarc\Core\Model\Entity\AbstractEntity;


/**
* CategoriesSuperClass
*
* @ORM\Table(name="soacategory", indexes={
*       @ORM\Index(name="referential", columns={"referential_uuid"})
* })
* @ORM\MappedSuperclass
*/
class SoaCategorySuperClass extends AbstractEntity
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
     * @var \Monarc\Core\Model\Entity\Referential
     *
     * @ORM\ManyToOne(targetEntity="Monarc\Core\Model\Entity\Referential", inversedBy="categories", cascade={"persist"})
     * @ORM\JoinColumns({
     *   @ORM\JoinColumn(name="referential_uuid", referencedColumnName="uuid", nullable=true)
     * })
     */
    protected $referential;

    /**
     * @var \Monarc\Core\Model\Entity\Measure
     *
     * @ORM\OneToMany(targetEntity="Monarc\Core\Model\Entity\Measure", mappedBy="category")
     */
    protected $measures;

    /**
    * @var text
    *
    * @ORM\Column(name="label1", type="text", length=255, nullable=true)
    */
    protected $label1;

    /**
    * @var text
    *
    * @ORM\Column(name="label2", type="text", length=255, nullable=true)
    */
    protected $label2;

    /**
    * @var text
    *
    * @ORM\Column(name="label3", type="text", length=255, nullable=true)
    */
    protected $label3;

    /**
    * @var text
    *
    * @ORM\Column(name="label4", type="text", length=255, nullable=true)
    */
    protected $label4;

    /**
    * @var smallint
    *
    * @ORM\Column(name="status", type="smallint", options={"unsigned":true, "default":1})
    */
    protected $status = '1';

    /**
    * @return int
    */
    public function getId()
    {
        return $this->id;
    }

    /**
    * @param int $id
    * @return Model
    */
    public function setId($id)
    {
        $this->id = $id;
        return $this;
    }

    /**
    * @return measures
    */
    public function getMeasures()
    {
        return $this->measures;
    }

    /**
    * @param int $measures
    * @return Model
    */
    public function setMeasures($measures)
    {
        $this->measures = $measures;
        return $this;
    }

    /**
     * @return UuidInterface
     */
    public function getReferential()
    {
        return $this->referential;
    }

    /**
    * @param Referential $referential
    * @return Model
    */
    public function setReferential($referential)
    {
        $this->referential = $referential;
        return $this;
    }

    /**
    * @return TEXT_LONG
    */
    public function getlabel1()
    {
        return $this->label1;
    }

    /**
    * @param TEXT_LONG $label1
    *
    */
    public function setlabel1($label1)
    {
        $this->label1 = $label1;
    }

    /**
    * @return TEXT_LONG
    */
    public function getlabel2()
    {
        return $this->label2;
    }

    /**
    * @param TEXT_LONG $label2
    *
    */
    public function setlabel2($label2)
    {
        $this->label2 = $label2;
    }

    /**
    * @return TEXT_LONG
    */
    public function getlabel3()
    {
        return $this->label3;
    }

    /**
    * @param TEXT_LONG $label3
    *
    */
    public function setlabel3($label3)
    {
        $this->label3 = $label3;
    }

    /**
    * @return TEXT_LONG
    */
    public function getlabel4()
    {
        return $this->label4;
    }

    /**
    * @param TEXT_LONG $label4
    *
    */
    public function setlabel4($label4)
    {
        $this->label4 = $label4;
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
            'r.label4',
            'r.uuid',
        ];
        return [$filterJoin,$filterLeft,$filtersCol];
    }
}
