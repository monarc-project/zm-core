<?php
/**
 * @link      https://github.com/monarc-project for the canonical source repository
 * @copyright Copyright (c) 2016-2020 SMILE GIE Securitymadein.lu - Licensed under GNU Affero GPL v3
 * @license   MONARC is licensed under GNU Affero General Public License version 3
 */

namespace Monarc\Core\Model\Entity;

use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\ORM\Mapping as ORM;

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
     * @var Referential
     *
     * @ORM\ManyToOne(targetEntity="Referential", inversedBy="categories", cascade={"persist"})
     * @ORM\JoinColumns({
     *   @ORM\JoinColumn(name="referential_uuid", referencedColumnName="uuid", nullable=true)
     * })
     */
    protected $referential;

    /**
     * @var MeasureSuperClass[]|ArrayCollection
     *
     * @ORM\OneToMany(targetEntity="Measure", mappedBy="category")
     */
    protected $measures;

    /**
     * @var string
     *
     * @ORM\Column(name="label1", type="text", nullable=true)
     */
    protected $label1;

    /**
     * @var string
     *
     * @ORM\Column(name="label2", type="text", nullable=true)
     */
    protected $label2;

    /**
     * @var string
     *
     * @ORM\Column(name="label3", type="text", nullable=true)
     */
    protected $label3;

    /**
     * @var string
     *
     * @ORM\Column(name="label4", type="text", nullable=true)
     */
    protected $label4;

    /**
     * @var int
     *
     * @ORM\Column(name="status", type="smallint", options={"unsigned":true, "default":1})
     */
    protected $status = 1;

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

    /**
     * @return MeasureSuperClass[]
     */
    public function getMeasures()
    {
        return $this->measures;
    }

    /**
     * @param ArrayCollection|MeasureSuperClass[] $measures
     */
    public function setMeasures($measures): self
    {
        $this->measures = $measures;

        return $this;
    }

    /**
     * @return ReferentialSuperClass
     */
    public function getReferential()
    {
        return $this->referential;
    }

    /**
     * @param ReferentialSuperClass $referential
     */
    public function setReferential($referential): self
    {
        $this->referential = $referential;

        return $this;
    }

    public function getLabel(int $languageIndex): string
    {
        if (!\in_array($languageIndex, range(1, 4), true)) {
            return '';
        }

        return (string)$this->{'label' . $languageIndex};
    }

    public function getLabels(): array
    {
        return [
            'label1' => (string)$this->label1,
            'label2' => (string)$this->label2,
            'label3' => (string)$this->label3,
            'label4' => (string)$this->label4,
        ];
    }

    public function setLabels(array $labels): self
    {
        foreach (range(1, 4) as $index) {
            $key = 'label' . $index;
            if (isset($labels[$key])) {
                $this->{$key} = $labels[$key];
            }
        }

        return $this;
    }

    /**
     * @return string
     */
    public function getlabel1()
    {
        return (string)$this->label1;
    }

    /**
     * @param string $label1
     */
    public function setlabel1($label1): self
    {
        $this->label1 = $label1;

        return $this;
    }

    /**
     * @return string
     */
    public function getlabel2()
    {
        return (string)$this->label2;
    }

    /**
     * @param string $label2
     */
    public function setlabel2($label2): self
    {
        $this->label2 = $label2;

        return $this;
    }

    /**
     * @return string
     */
    public function getlabel3()
    {
        return (string)$this->label3;
    }

    /**
     * @param string $label3
     */
    public function setlabel3($label3): self
    {
        $this->label3 = $label3;

        return $this;
    }

    /**
     * @return string
     */
    public function getlabel4()
    {
        return (string)$this->label4;
    }

    /**
     * @param string $label4
     */
    public function setlabel4($label4): self
    {
        $this->label4 = $label4;

        return $this;
    }

    public function getStatus(): int
    {
        return $this->status;
    }

    public function getInputFilter($partial = false)
    {
        if (!$this->inputFilter) {
            parent::getInputFilter($partial);

            $texts = ['label1', 'label2', 'label3', 'label4'];

            foreach ($texts as $text) {
                $this->inputFilter->add([
                    'name' => $text,
                    'required' => ((strchr($text, (string)$this->getLanguage())) && (!$partial)) ? true : false,
                    'allow_empty' => false,
                    'filters' => [],
                    'validators' => [],
                ]);
            }

            $this->inputFilter->add([
                'name' => 'status',
                'required' => false,
                'allow_empty' => false,
                'filters' => [
                    ['name' => 'ToInt'],
                ],
                'validators' => [
                    [
                        'name' => 'InArray',
                        'options' => [
                            'haystack' => [static::STATUS_INACTIVE, static::STATUS_ACTIVE],
                        ],
                    ],
                ],
            ]);
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
        ];
        $filtersCol = [
            'r.label1',
            'r.label2',
            'r.label3',
            'r.label4',
            'r.uuid',
        ];

        return [$filterJoin, $filterLeft, $filtersCol];
    }
}
