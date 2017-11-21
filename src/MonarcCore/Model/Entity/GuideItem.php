<?php
/**
 * @link      https://github.com/monarc-project for the canonical source repository
 * @copyright Copyright (c) Cases is a registered trademark of SECURITYMADEIN.LU
 * @license   MyCases is licensed under the GNU Affero GPL v3 - See license.txt for more information
 */

namespace MonarcCore\Model\Entity;

use Doctrine\ORM\Mapping as ORM;

/**
 * Guide
 *
 * @ORM\Table(name="guides_items", indexes={
 *      @ORM\Index(name="anr", columns={"anr_id"}),
 *      @ORM\Index(name="guide_id", columns={"guide_id"}),
 *      @ORM\Index(name="position", columns={"position"})
 * })
 * @ORM\Entity
 */
class GuideItem extends AbstractEntity
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
     * @var \MonarcCore\Model\Entity\Guide
     *
     * @ORM\ManyToOne(targetEntity="MonarcCore\Model\Entity\Guide")
     * @ORM\JoinColumns({
     *   @ORM\JoinColumn(name="guide_id", referencedColumnName="id", nullable=true)
     * })
     */
    protected $guide;

    /**
     * @var \MonarcCore\Model\Entity\Translation
     *
     * @ORM\OneToOne(targetEntity="\MonarcCore\Model\Entity\Translation")
     * @ORM\JoinColumn(name="description_translation_id", referencedColumnName="id")
     */
    protected $description;

    /**
     * @var smallint
     *
     * @ORM\Column(name="position", type="smallint", options={"unsigned":true})
     */
    protected $position;

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

