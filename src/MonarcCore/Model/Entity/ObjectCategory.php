<?php

namespace MonarcCore\Model\Entity;

use Doctrine\ORM\Mapping as ORM;

/**
 * Object Category
 *
 * @ORM\Table(name="objects_categories")
 * @ORM\Entity
 */
class ObjectCategory extends AbstractEntity
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
     * @var \MonarcCore\Model\Entity\ObjectCategory
     *
     * @ORM\ManyToOne(targetEntity="MonarcCore\Model\Entity\ObjectCategory", cascade={"persist"})
     * @ORM\JoinColumns({
     *   @ORM\JoinColumn(name="root_id", referencedColumnName="id", nullable=true)
     * })
     */
    protected $root;

    /**
     * @var \MonarcCore\Model\Entity\ObjectCategory
     *
     * @ORM\ManyToOne(targetEntity="MonarcCore\Model\Entity\ObjectCategory", cascade={"persist"})
     * @ORM\JoinColumns({
     *   @ORM\JoinColumn(name="parent_id", referencedColumnName="id", nullable=true)
     * })
     */
    protected $parent;

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
     * @var boolean
     *
     * @ORM\Column(name="position", type="boolean", options={"unsigned":true, "default":1})
     */
    protected $position = '1';

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
     * @return Model
     */
    public function setId($id)
    {
        $this->id = $id;
        return $this;
    }

    public function getInputFilter(){
        if (!$this->inputFilter) {
            parent::getInputFilter();

            $texts = ['label1', 'label2', 'label3', 'label4'];

            foreach($texts as $text) {
                $this->inputFilter->add(array(
                    'name' => $text,
                    'required' => true,
                    'allow_empty' => true,
                    'filters' => array(
                        array(
                            'name' => 'Alnum',
                            'options' => array(
                                'allow_white_space' => true,
                            )
                        ),
                    ),
                    'validators' => array(),
                ));
            }

        }
        return $this->inputFilter;
    }
}

