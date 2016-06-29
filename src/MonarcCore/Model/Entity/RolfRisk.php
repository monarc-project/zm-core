<?php

namespace MonarcCore\Model\Entity;

use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\ORM\Mapping as ORM;

/**
 * RolfRisk
 *
 * @ORM\Table(name="rolf_risks")
 * @ORM\Entity
 */
class RolfRisk extends AbstractEntity
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
     * @var \MonarcCore\Model\Entity\RolfCategory
     *
     * @ORM\ManyToMany(targetEntity="MonarcCore\Model\Entity\RolfCategory", inversedBy="rolf_categories", cascade={"persist"})
     * @ORM\JoinTable(name="rolf_risks_categories",
     *  joinColumns={@ORM\JoinColumn(name="rolf_risk_id", referencedColumnName="id")},
     *  inverseJoinColumns={@ORM\JoinColumn(name="rolf_category_id", referencedColumnName="id")}
     * )
     */
    protected $categories;

    /**
     * @var \MonarcCore\Model\Entity\RolfTag
     *
     * @ORM\ManyToMany(targetEntity="MonarcCore\Model\Entity\RolfTag", inversedBy="rolf_tags", cascade={"persist"})
     * @ORM\JoinTable(name="rolf_risks_tags",
     *  joinColumns={@ORM\JoinColumn(name="rolf_risk_id", referencedColumnName="id")},
     *  inverseJoinColumns={@ORM\JoinColumn(name="rolf_tag_id", referencedColumnName="id")}
     * )
     */
    protected $tags;

    /**
     * @var string
     *
     * @ORM\Column(name="code", type="string", length=255, nullable=true)
     */
    protected $code;

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
     * Set rolf category
     *
     * @param key
     * @param RolfCategory $rolfCategory
     */
    public function setCategory($id, RolfCategory $rolfCategory)
    {
        $this->categories[$id] = $rolfCategory;
    }

    /**
     * Set rolf tag
     *
     * @param key
     * @param RolfTag $rolfTag
     */
    public function setTag($id, RolfTag $rolfTag)
    {
        $this->tags[$id] = $rolfTag;
    }

    public function getInputFilter($required = false){
        if (!$this->inputFilter) {
            parent::getInputFilter($required);

            $texts = ['label1', 'label2', 'label3', 'label4', 'description1', 'description2', 'description3', 'description4'];

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

    public function __construct()
    {
        $this->rolfCategories = new ArrayCollection();
        $this->rolfTags = new ArrayCollection();
    }
}

