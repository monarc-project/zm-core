<?php
/**
 * @link      https://github.com/monarc-project for the canonical source repository
 * @copyright Copyright (c) 2016-2020 SMILE GIE Securitymadein.lu - Licensed under GNU Affero GPL v3
 * @license   MONARC is licensed under GNU Affero General Public License version 3
 */

namespace Monarc\Core\Model\Entity;

use Doctrine\ORM\Mapping as ORM;
use Monarc\Core\Model\Entity\Traits\CreateEntityTrait;
use Monarc\Core\Model\Entity\Traits\UpdateEntityTrait;

/**
 * Scale Impact Type Super Class
 *
 * @ORM\Table(name="scales_impact_types", indexes={
 *      @ORM\Index(name="anr", columns={"anr_id"}),
 *      @ORM\Index(name="scale_id", columns={"scale_id"})
 * })
 * @ORM\MappedSuperclass
 * @ORM\HasLifecycleCallbacks()
 */
class ScaleImpactTypeSuperClass extends AbstractEntity
{
    use CreateEntityTrait;
    use UpdateEntityTrait;

    const SCALE_TYPE_C = 1;
    const SCALE_TYPE_I = 2;
    const SCALE_TYPE_D = 3;
    const SCALE_TYPE_R = 4;
    const SCALE_TYPE_O = 5;
    const SCALE_TYPE_L = 6;
    const SCALE_TYPE_F = 7;
    const SCALE_TYPE_P = 8;

    static function getScaleImpactTypeRolfp()
    {
        return [
            self::SCALE_TYPE_R,
            self::SCALE_TYPE_O,
            self::SCALE_TYPE_L,
            self::SCALE_TYPE_F,
            self::SCALE_TYPE_P,
        ];
    }

    static function getScaleImpactTypeCid()
    {
        return [
            self::SCALE_TYPE_C,
            self::SCALE_TYPE_I,
            self::SCALE_TYPE_D,
        ];
    }

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
     * @var int
     *
     * @ORM\Column(name="type", type="smallint", options={"unsigned":true})
     */
    protected $type;

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
     * @ORM\Column(name="is_sys", type="smallint", options={"unsigned":true})
     */
    protected $isSys;

    /**
     * @var int
     *
     * @ORM\Column(name="is_hidden", type="smallint", options={"unsigned":true})
     */
    protected $isHidden;

    /**
     * @var int
     *
     * @ORM\Column(name="position", type="smallint", options={"unsigned":true})
     */
    protected $position;

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
     * @return Anr
     */
    public function getAnr()
    {
        return $this->anr;
    }

    /**
     * @param Anr $anr
     * @return ScaleImpactType
     */
    public function setAnr($anr)
    {
        $this->anr = $anr;
        return $this;
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

    protected $parameters = array(
        'implicitPosition' => array(
            'field' => 'scale',
        ),
    );

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

            if (!$partial) {
                $this->inputFilter->add(array(
                    'name' => 'anr',
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
            }

        }
        return $this->inputFilter;
    }
}

