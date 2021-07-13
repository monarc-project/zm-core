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
 * @ORM\Table(name="scales_comments", indexes={
 *      @ORM\Index(name="anr", columns={"anr_id"}),
 *      @ORM\Index(name="scale_id", columns={"scale_id"}),
 *      @ORM\Index(name="scale_type_impact_id", columns={"scale_type_impact_id"})
 * })
 * @ORM\MappedSuperclass
 * @ORM\HasLifecycleCallbacks()
 */
class ScaleCommentSuperClass extends AbstractEntity
{
    use CreateEntityTrait;
    use UpdateEntityTrait;

    /**
     * @var integer
     *
     * @ORM\Column(name="id", type="integer", nullable=false)
     * @ORM\Id
     * @ORM\GeneratedValue(strategy="IDENTITY")
     */
    protected $id;

    /**
     * @var AnrSuperClass
     *
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
     * @var ScaleImpactTypeSuperClass
     *
     * @ORM\ManyToOne(targetEntity="ScaleImpactType", cascade={"persist"})
     * @ORM\JoinColumns({
     *   @ORM\JoinColumn(name="scale_type_impact_id", referencedColumnName="id", nullable=true, onDelete="CASCADE")
     * })
     */
    protected $scaleImpactType;

    /**
     * @var int
     *
     * @ORM\Column(name="scale_index", type="integer", options={"unsigned":true})
     */
    protected $scaleIndex;

    /**
     * @var int
     *
     * @ORM\Column(name="scale_value", type="integer", options={"unsigned":true})
     */
    protected $scaleValue;

    /**
     * @var string
     *
     * @ORM\Column(name="comment1", type="text", nullable=true)
     */
    protected $comment1;

    /**
     * @var string
     *
     * @ORM\Column(name="comment2", type="text", nullable=true)
     */
    protected $comment2;

    /**
     * @var string
     *
     * @ORM\Column(name="comment3", type="text", nullable=true)
     */
    protected $comment3;

    /**
     * @var string
     *
     * @ORM\Column(name="comment4", type="text", nullable=true)
     */
    protected $comment4;

    /**
     * @return int
     */
    public function getId()
    {
        return $this->id;
    }

    public function setId(int $id): self
    {
        $this->id = $id;

        return $this;
    }

    public function getAnr(): ?AnrSuperClass
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

    public function getScaleImpactType(): ?ScaleImpactTypeSuperClass
    {
        return $this->scaleImpactType;
    }

    public function setScaleImpactType(ScaleImpactTypeSuperClass $scaleImpactType): self
    {
        $this->scaleImpactType = $scaleImpactType;
        $scaleImpactType->addScaleComment($this);

        return $this;
    }

    public function getScaleIndex(): int
    {
        return $this->scaleIndex;
    }

    public function setScaleIndex(int $scaleIndex): self
    {
        $this->scaleIndex = $scaleIndex;

        return $this;
    }

    public function getScaleValue(): int
    {
        return $this->scaleValue;
    }

    public function setScaleValue(int $scaleValue): self
    {
        $this->scaleValue = $scaleValue;

        return $this;
    }

    public function getComment(int $languageIndex): string
    {
        if (!\in_array($languageIndex, range(1, 4), true)) {
            return '';
        }

        return (string)$this->{'comment' . $languageIndex};
    }

    public function setComments(array $comments): self
    {
        foreach (range(1, 4) as $index) {
            $key = 'comment' . $index;
            if (isset($comments[$key])) {
                $this->{$key} = $comments[$key];
            }
        }

        return $this;
    }

    public function getScaleIndexAvailableValues()
    {
        $values = [];

        for ($i = $this->scale->getMin(); $i <= $this->scale->getMax(); $i++) {
            $values[] = $i;
        }

        return $values;
    }

    public function getInputFilter($partial = false)
    {
        if (!$this->inputFilter) {
            parent::getInputFilter($partial);

            $this->inputFilter->add([
                'name' => 'scaleIndex',
                'required' => true,
                'allow_empty' => false,
                'continue_if_empty' => false,
                'filters' => [],
                'validators' => [
                    [
                        'name' => 'InArray',
                        'options' => [
                            'haystack' => $this->getScaleIndexAvailableValues(),
                        ],
                    ],
                ],
            ]);

            $this->inputFilter->add([
                'name' => 'scale',
                'required' => true,
                'allow_empty' => false,
                'continue_if_empty' => false,
                'filters' => [],
                'validators' => [
                    [
                        'name' => 'IsInt',
                    ],
                ],
            ]);

            if ($this->getScale()->getType() === 1) {
                $this->inputFilter->add([
                    'name' => 'scaleImpactType',
                    'required' => true,
                    'allow_empty' => false,
                    'continue_if_empty' => false,
                    'filters' => [],
                    'validators' => [
                        [
                            'name' => 'IsInt',
                        ],
                    ],
                ]);
            }

        }

        return $this->inputFilter;
    }
}
