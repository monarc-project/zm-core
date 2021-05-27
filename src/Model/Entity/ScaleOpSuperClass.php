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
 * Scale Op Super Class
 *
 * @ORM\Table(name="scales_op", indexes={
 *      @ORM\Index(name="anr", columns={"anr_id"})
 * })
 * @ORM\MappedSuperclass
 * @ORM\HasLifecycleCallbacks()
 */
class ScaleOpSuperClass extends AbstractEntity
{
    use CreateEntityTrait;
    use UpdateEntityTrait;

    const TYPE_IMPACT = 1;
    const TYPE_THREAT = 2;

    /**
     * @var int
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
     * @var int
     *
     * @ORM\Column(name="type", type="smallint", options={"unsigned":true})
     */
    protected $type;

    /**
     * @var int
     *
     * @ORM\Column(name="min", type="smallint", options={"unsigned":true})
     */
    protected $min;

    /**
     * @var int
     *
     * @ORM\Column(name="max", type="smallint", options={"unsigned":true})
     */
    protected $max;

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
     * @return AnrSuperClass
     */
    public function getAnr()
    {
        return $this->anr;
    }

    /**
     * @param AnrSuperClass $anr
     */
    public function setAnr($anr): self
    {
        $this->anr = $anr;

        return $this;
    }

    public function getType(): int
    {
        return (int)$this->type;
    }

    public function getMin(): int
    {
        return (int)$this->min;
    }

    public function getMax(): int
    {
        return (int)$this->max;
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

    public function getLabel(int $languageIndex): string
    {
        if (!\in_array($languageIndex, range(1, 4), true)) {
            return '';
        }

        return (string)$this->{'label' . $languageIndex};
    }

    public function getInputFilter($partial = false)
    {
        if (!$this->inputFilter) {
            parent::getInputFilter($partial);

            $this->inputFilter->add([
                'name' => 'min',
                'required' => true,
                'allow_empty' => false,
                'continue_if_empty' => false,
                'filters' => [],
                'validators' => [
                    ['name' => 'IsInt'],
                ],
            ]);

            $this->inputFilter->add([
                'name' => 'max',
                'required' => true,
                'allow_empty' => false,
                'continue_if_empty' => false,
                'filters' => [],
                'validators' => [
                    ['name' => 'IsInt']
                ],
            ]);
        }
        return $this->inputFilter;
    }

    // public function getImpactLangues()
    // {
    //     return [
    //         'fr' => [
    //             'C' => 'Confidentialité',
    //             'I' => 'Intégrité',
    //             'D' => 'Disponibilité',
    //             'R' => 'Réputation',
    //             'O' => 'Opérationnel',
    //             'L' => 'Légal',
    //             'F' => 'Financier',
    //             'P' => 'Personne'
    //         ],
    //         'en' => [
    //             'C' => 'Confidentiality',
    //             'I' => 'Integrity',
    //             'D' => 'Availability',
    //             'R' => 'Reputation',
    //             'O' => 'Operational',
    //             'L' => 'Legal',
    //             'F' => 'Financial',
    //             'P' => 'Personal'
    //         ],
    //         'de' => [
    //             'C' => 'Vertraulichkeit',
    //             'I' => 'Integrität',
    //             'D' => 'Verfügbarkeit',
    //             'R' => 'Ruf',
    //             'O' => 'Einsatzbereit',
    //             'L' => 'Legal',
    //             'F' => 'Finanziellen',
    //             'P' => 'Person'
    //         ],
    //         'ne' => [
    //             'C' => 'Vertrouwelijkheid',
    //             'I' => 'Integriteit',
    //             'D' => 'Beschikbaarheid',
    //             'R' => 'Reputatie',
    //             'O' => 'Operationeel',
    //             'L' => 'Legaal',
    //             'F' => 'Financieel',
    //             'P' => 'Persoon'
    //         ],
    //         '0' => [
    //             'C' => 'Confidentiality',
    //             'I' => 'Integrity',
    //             'D' => 'Availability',
    //             'R' => 'Reputation',
    //             'O' => 'Operational',
    //             'L' => 'Legal',
    //             'F' => 'Financial',
    //             'P' => 'Personal'
    //         ]
    //     ];
    // }
}
