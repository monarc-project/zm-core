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
 * Guide
 *
 * @ORM\Table(name="guides_items", indexes={
 *      @ORM\Index(name="anr", columns={"anr_id"}),
 *      @ORM\Index(name="guide_id", columns={"guide_id"}),
 *      @ORM\Index(name="position", columns={"position"})
 * })
 * @ORM\Entity
 * @ORM\HasLifecycleCallbacks()
 */
class GuideItem extends AbstractEntity
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
     * @var integer
     *
     * @ORM\Column(name="anr_id", type="integer", nullable=true)
     */
    protected $anr;

    /**
     * @var \Monarc\Core\Model\Entity\Guide
     *
     * @ORM\ManyToOne(targetEntity="Monarc\Core\Model\Entity\Guide")
     * @ORM\JoinColumns({
     *   @ORM\JoinColumn(name="guide_id", referencedColumnName="id", nullable=true)
     * })
     */
    protected $guide;

    /**
     * @var string
     *
     * @ORM\Column(name="description1", type="string", length=255, nullable=true)
     */
    protected $description1;

    /**
     * @var string
     *
     * @ORM\Column(name="description2", type="string", length=255, nullable=true)
     */
    protected $description2;

    /**
     * @var string
     *
     * @ORM\Column(name="description3", type="string", length=255, nullable=true)
     */
    protected $description3;

    /**
     * @var string
     *
     * @ORM\Column(name="description4", type="string", length=255, nullable=true)
     */
    protected $description4;

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
     * @return Guide
     */
    public function getGuide()
    {
        return $this->guide;
    }

    /**
     * @param Guide $guide
     * @return GuideItem
     */
    public function setGuide($guide)
    {
        $this->guide = $guide;
        return $this;
    }

    protected $parameters = array(
        'implicitPosition' => array(
            'field' => 'guide',
        ),
    );

    public function getInputFilter($partial = false)
    {
        if (!$this->inputFilter) {
            parent::getInputFilter($partial);

            $descriptions = [
                'description1', 'description2', 'description3', 'description4'
            ];

            foreach ($descriptions as $description) {
                $this->inputFilter->add(array(
                    'name' => $description,
                    'required' => false,
                    'allow_empty' => true,
                    'filters' => array(),
                    'validators' => array(),
                ));
            }

            $this->inputFilter->add(array(
                'name' => 'guide',
                'required' => true,
                'allow_empty' => false,
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
}

