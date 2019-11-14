<?php
/**
 * @link      https://github.com/monarc-project for the canonical source repository
 * @copyright Copyright (c) 2016-2019  SMILE GIE Securitymadein.lu - Licensed under GNU Affero GPL v3
 * @license   MONARC is licensed under GNU Affero General Public License version 3
 */

namespace Monarc\Core\Model\Entity;

use Doctrine\ORM\Mapping as ORM;

/**
 * Scale Comment Super Class
 *
 * @ORM\Table(name="scales_comments", indexes={
 *      @ORM\Index(name="anr", columns={"anr_id"}),
 *      @ORM\Index(name="scale_id", columns={"scale_id"}),
 *      @ORM\Index(name="scale_type_impact_id", columns={"scale_type_impact_id"})
 * })
 * @ORM\MappedSuperclass
 */
class ScaleCommentSuperClass extends AbstractEntity
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
     * @var \Monarc\Core\Model\Entity\Anr
     *
     * @ORM\ManyToOne(targetEntity="Monarc\Core\Model\Entity\Anr", cascade={"persist"})
     * @ORM\JoinColumns({
     *   @ORM\JoinColumn(name="anr_id", referencedColumnName="id", nullable=true, onDelete="CASCADE")
     * })
     */
    protected $anr;

    /**
     * @var \Monarc\Core\Model\Entity\Scale
     *
     * @ORM\ManyToOne(targetEntity="Monarc\Core\Model\Entity\Scale", cascade={"persist"})
     * @ORM\JoinColumns({
     *   @ORM\JoinColumn(name="scale_id", referencedColumnName="id", nullable=true, onDelete="CASCADE")
     * })
     */
    protected $scale;

    /**
     * @var \Monarc\Core\Model\Entity\ScaleImpactType
     *
     * @ORM\ManyToOne(targetEntity="Monarc\Core\Model\Entity\ScaleImpactType", cascade={"persist"})
     * @ORM\JoinColumns({
     *   @ORM\JoinColumn(name="scale_type_impact_id", referencedColumnName="id", nullable=true, onDelete="CASCADE")
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
     * @return int
     */
    public function getAnr()
    {
        return $this->anr;
    }

    /**
     * @param int $anr
     * @return ScaleComment
     */
    public function setAnr($anr)
    {
        $this->anr = $anr;
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
    public function getValValues()
    {

        $values = [-1];

        for ($i = $this->getScale()->min; $i <= $this->getScale()->max; $i++) {
            $values[] = $i;
        }

        return $values;
    }

    public function getInputFilter($partial = false)
    {
        if (!$this->inputFilter) {
            parent::getInputFilter($partial);

            $texts = ['comment1', 'comment2', 'comment3', 'comment4'];

            foreach ($texts as $text) {
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
