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

    use LabelsEntityTrait;

    const SCALE_TYPE_C = 1;
    const SCALE_TYPE_I = 2;
    const SCALE_TYPE_D = 3;
    const SCALE_TYPE_R = 4;
    const SCALE_TYPE_O = 5;
    const SCALE_TYPE_L = 6;
    const SCALE_TYPE_F = 7;
    const SCALE_TYPE_P = 8;

    /**
     * @var integer
     *
     * @ORM\Column(name="id", type="integer", nullable=false)
     * @ORM\Id
     * @ORM\GeneratedValue(strategy="IDENTITY")
     */
    protected $id;

    /**
     * @ORM\ManyToOne(targetEntity="Anr", cascade={"persist"})
     * @ORM\JoinColumns({
     *   @ORM\JoinColumn(name="anr_id", referencedColumnName="id", nullable=true, onDelete="CASCADE")
     * })
     */
    protected $anr;

    /**
     * @var ScaleSuperClass
     *
     * @ORM\ManyToOne(targetEntity="Scale", cascade={"persist"})
     * @ORM\JoinColumns({
     *   @ORM\JoinColumn(name="scale_id", referencedColumnName="id", nullable=true, onDelete="CASCADE")
     * })
     */
    protected $scale;

    /**
     * @var ScaleCommentSuperClass[]|ArrayCollection
     *
     * @ORM\OneToMany(targetEntity="ScaleComment", mappedBy="scaleImpactType")
     * @ORM\OrderBy({"scaleIndex" = "ASC"})
     */
    protected $scaleComments;

    /**
     * @var int
     *
     * @ORM\Column(name="type", type="smallint", options={"unsigned":true})
     */
    protected $type;

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

    public function __construct($obj = null)
    {
        $this->scaleComments = new ArrayCollection();

        parent::__construct($obj);
    }

    /**
     * @return int
     */
    public function getId()
    {
        return $this->id;
    }

    /**
     * @param int $id
     */
    public function setId($id): self
    {
        $this->id = $id;

        return $this;
    }

    public function getAnr(): AnrSuperClass
    {
        return $this->anr;
    }

    public function setAnr(AnrSuperClass $anr): self
    {
        $this->anr = $anr;

        return $this;
    }

    public function getScale(): ScaleSuperClass
    {
        return $this->scale;
    }

    public function setScale(ScaleSuperClass $scale): self
    {
        $this->scale = $scale;

        return $this;
    }

    public function getScaleComments()
    {
        return $this->scaleComments;
    }

    public function addScaleComment(ScaleCommentSuperClass $scaleComment): self
    {
        if (!$this->scaleComments->contains($scaleComment)) {
            $this->scaleComments->add($scaleComment);
            $scaleComment->setScaleImpactType($this);
        }

        return $this;
    }

    public function getPosition(): int
    {
        return $this->position;
    }

    public function setPosition(int $position): self
    {
        $this->position = $position;

        return $this;
    }

    public function getType(): int
    {
        return (int)$this->type;
    }

    public function setType(int $type): self
    {
        $this->type = $type;

        return $this;
    }

    public function isSys(): bool
    {
        return (bool)$this->isSys;
    }

    public function setIsSys(bool $isSys): self
    {
        $this->isSys = (int)$isSys;

        return $this;
    }

    public function isHidden(): bool
    {
        return (bool)$this->isHidden;
    }

    public function setIsHidden(bool $isHidden): self
    {
        $this->isHidden = (int)$isHidden;

        return $this;
    }

    public static function getScaleImpactTypesRolfp(): array
    {
        return [
            self::SCALE_TYPE_R,
            self::SCALE_TYPE_O,
            self::SCALE_TYPE_L,
            self::SCALE_TYPE_F,
            self::SCALE_TYPE_P,
        ];
    }

    public static function getScaleImpactTypesCid(): array
    {
        return [
            self::SCALE_TYPE_C,
            self::SCALE_TYPE_I,
            self::SCALE_TYPE_D,
        ];
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
