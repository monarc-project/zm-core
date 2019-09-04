<?php
/**
 * @link      https://github.com/monarc-project for the canonical source repository
 * @copyright Copyright (c) 2016-2019  SMILE GIE Securitymadein.lu - Licensed under GNU Affero GPL v3
 * @license   MONARC is licensed under GNU Affero General Public License version 3
 */

namespace Monarc\Core\Model\Entity;

use Doctrine\ORM\Mapping as ORM;

/**
 * Scale Super Class
 *
 * @ORM\Table(name="scales", indexes={
 *      @ORM\Index(name="anr", columns={"anr_id"})
 * })
 * @ORM\MappedSuperclass
 */
class ScaleSuperClass extends AbstractEntity
{
    const TYPE_IMPACT = 1;
    const TYPE_THREAT = 2;
    const TYPE_VULNERABILITY = 3;

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
     * @var smallint
     *
     * @ORM\Column(name="type", type="smallint", options={"unsigned":true})
     */
    protected $type;

    /**
     * @var smallint
     *
     * @ORM\Column(name="min", type="smallint", options={"unsigned":true})
     */
    protected $min;

    /**
     * @var smallint
     *
     * @ORM\Column(name="max", type="smallint", options={"unsigned":true})
     */
    protected $max;

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
     * @return Anr
     */
    public function getAnr()
    {
        return $this->anr;
    }

    /**
     * @param Anr $anr
     * @return Scale
     */
    public function setAnr($anr)
    {
        $this->anr = $anr;
        return $this;
    }

    public function getInputFilter($partial = false)
    {
        if (!$this->inputFilter) {
            parent::getInputFilter($partial);

            $this->inputFilter->add(array(
                'name' => 'min',
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
                'name' => 'max',
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
        return $this->inputFilter;
    }

    public function getImpactLangues(){
        return [
            'fr' => [
                'C' => 'Confidentialité',
                'I' => 'Intégrité',
                'D' => 'Disponibilité',
                'R' => 'Réputation',
                'O' => 'Opérationnel',
                'L' => 'Légal',
                'F' => 'Financier',
                'P' => 'Personne'
            ],
            'en' => [
                'C' => 'Confidentiality',
                'I' => 'Integrity',
                'D' => 'Availability',
                'R' => 'Reputation',
                'O' => 'Operational',
                'L' => 'Legal',
                'F' => 'Financial',
                'P' => 'Personal'
            ],
            'de' => [
                'C' => 'Vertraulichkeit',
                'I' => 'Integrität',
                'D' => 'Verfügbarkeit',
                'R' => 'Ruf',
                'O' => 'Einsatzbereit',
                'L' => 'Legal',
                'F' => 'Finanziellen',
                'P' => 'Person'
            ],
            'ne' => [
                'C' => 'Vertrouwelijkheid',
                'I' => 'Integriteit',
                'D' => 'Beschikbaarheid',
                'R' => 'Reputatie',
                'O' => 'Operationeel',
                'L' => 'Legaal',
                'F' => 'Financieel',
                'P' => 'Persoon'
            ],
            '0' => [
                'C' => 'Confidentiality',
                'I' => 'Integrity',
                'D' => 'Availability',
                'R' => 'Reputation',
                'O' => 'Operational',
                'L' => 'Legal',
                'F' => 'Financial',
                'P' => 'Personal'
            ]
        ];
    }
}
