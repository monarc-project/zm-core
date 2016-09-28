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
     * @var smallint
     *
     * @ORM\Column(name="position", type="smallint", options={"unsigned":true, "default":1})
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
     * @var int
     * Not mapped to a column - used to determine the actual entity position
     */
    protected $implicitPosition;

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

    /**
     * @return ObjectCategory
     */
    public function getParent()
    {
        return $this->parent;
    }

    /**
     * @param ObjectCategory $parent
     * @return ObjectCategory
     */
    public function setParent($parent)
    {
        $this->parent = $parent;
        return $this;
    }

    /**
     * @return ObjectCategory
     */
    public function getRoot()
    {
        return $this->root;
    }

    /**
     * @param ObjectCategory $root
     * @return ObjectCategory
     */
    public function setRoot($root)
    {
        $this->root = $root;
        return $this;
    }

    /**
     * @return \Doctrine\Common\Collections\Collection
     */
    public function getAnrs()
    {
        return $this->anrs;
    }


    /**
     * Add Anr
     *
     * @param Anr $anr
     * @throws \Exception
     */
    public function addAnr(Anr $anr)
    {
        $currentAnrs = $this->anrs;

        $alreadyUsed = false;
        foreach ($currentAnrs as $currentAnr) {
            if ($currentAnr->id == $anr->id) {
                $alreadyUsed = true;
            }
        }

        if (!$alreadyUsed) {
            $this->anrs[] = $anr;
        }
    }

    public function getInputFilter($partial = false){
        if (!$this->inputFilter) {
            parent::getInputFilter($partial);

            $texts = ['label1', 'label2', 'label3', 'label4'];
            foreach($texts as $text) {
                $this->inputFilter->add(array(
                    'name' => $text,
                    'required' => ((strchr($text, (string) $this->getLanguage())) && (!$partial)) ? true : false,
                    'allow_empty' => false,
                    'filters' => array(),
                    'validators' => array(),
                ));
            }


            $this->inputFilter->add(array(
                'name' => 'implicitPosition',
                'required' => false,
                'allow_empty' => true,
                'continue_if_empty' => true,
                'filters' => array(),
                'validators' => array(
                    array(
                        'name' => 'InArray',
                        'options' => array(
                            'haystack' => [1, 2, 3],
                        ),
                    ),
                ),
            ));

        }
        return $this->inputFilter;
    }
}

