<?php

namespace MonarcCore\Model\Entity;

use Doctrine\ORM\Mapping as ORM;

/**
 * Scale Comment
 *
 * @ORM\Table(name="scales_comments")
 * @ORM\Entity
 */
class ScaleComment extends AbstractEntity
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
     * @var integer
     *
     * @ORM\Column(name="anr_id", type="integer", nullable=true)
     */
    protected $anr;

    /**
     * @var \MonarcCore\Model\Entity\Scale
     *
     * @ORM\ManyToOne(targetEntity="MonarcCore\Model\Entity\Scale", cascade={"persist"})
     * @ORM\JoinColumns({
     *   @ORM\JoinColumn(name="scale_id", referencedColumnName="id", nullable=true)
     * })
     */
    protected $scale;

    /**
     * @var \MonarcCore\Model\Entity\ScaleImpactType
     *
     * @ORM\ManyToOne(targetEntity="MonarcCore\Model\Entity\ScaleImpactType", cascade={"persist"})
     * @ORM\JoinColumns({
     *   @ORM\JoinColumn(name="scale_type_impact_id", referencedColumnName="id", nullable=true)
     * })
     */
    protected $scaleImpactType;

    /**
     * @var integer
     *
     * @ORM\Column(name="val", type="integer", options={"unsigned":true})
     */
    protected $val;

    /**
     * @var text
     *
     * @ORM\Column(name="comment1", type="text", length=255, nullable=true)
     */
    protected $comment1;

    /**
     * @var text
     *
     * @ORM\Column(name="comment2", type="text", length=255, nullable=true)
     */
    protected $comment2;

    /**
     * @var text
     *
     * @ORM\Column(name="comment3", type="text", length=255, nullable=true)
     */
    protected $comment3;

    /**
     * @var text
     *
     * @ORM\Column(name="comment4", type="text", length=255, nullable=true)
     */
    protected $comment4;

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
     * @return int
     */
    public function getId()
    {
        return $this->id;
    }

    /**
     * @param int $id
     * @return Asset
     */
    public function setId($id)
    {
        $this->id = $id;
        return $this;
    }

    /**
     * @return Scale
     */
    public function getScale()
    {
        return $this->scale;
    }

    /**
     * @param Scale $scale
     * @return ScaleImpactType
     */
    public function setScale($scale)
    {
        $this->scale = $scale;
        return $this;
    }

    /**
     * @return ScaleImpactType
     */
    public function getScaleImpactType()
    {
        return $this->scaleImpactType;
    }

    /**
     * @param ScaleImpactType $scaleImpactType
     */
    public function setScaleImpactType($scaleImpactType)
    {
        $this->scaleImpactType = $scaleImpactType;
    }

    /**
     * Get Val Values
     *
     * @return array
     */
    public function getValValues() {

        $values = [-1];

        for($i = $this->getScale()->min; $i <= $this->getScale()->max; $i++) {
            $values[] = $i;
        }

        return $values;
    }

    public function getInputFilter($partial = false){
        if (!$this->inputFilter) {
            parent::getInputFilter($partial);

            $texts = ['comment1', 'comment2', 'comment3', 'comment4'];

            foreach($texts as $text) {
                $this->inputFilter->add(array(
                    'name' => $text,
                    'required' => false,
                    'allow_empty' => true,
                    'filters' => array(),
                    'validators' => array(),
                ));
            }

            $this->inputFilter->add(array(
                'name' => 'val',
                'required' => true,
                'allow_empty' => false,
                'continue_if_empty' => false,
                'filters' => array(),
                'validators' => array(
                    array(
                        'name' => 'InArray',
                        'options' => array(
                            'haystack' => $this->getValValues(),
                        ),
                    ),
                ),
            ));

            $this->inputFilter->add(array(
                'name' => 'scale',
                'required' => true,
                'allow_empty' => false,
                'continue_if_empty' => false,
                'filters' => array(),
                'validators' => array(
                    array(
                        'name' => 'IsInt',
                    ),
                ),
            ));

            if ($this->getScale()->type == 1) {
                $this->inputFilter->add(array(
                    'name' => 'scaleImpactType',
                    'required' => true,
                    'allow_empty' => false,
                    'continue_if_empty' => false,
                    'filters' => array(),
                    'validators' => array(

                        array(
                            'name' => 'IsInt',
                        ),
                    ),
                ));
            }

        }
        return $this->inputFilter;
    }
}

