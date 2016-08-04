<?php

namespace MonarcCore\Model\Entity;

use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\ORM\Mapping as ORM;

/**
 * Object
 *
 * @ORM\Table(name="objects")
 * @ORM\Entity
 */
class Object extends AbstractEntity
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
     * @var \MonarcCore\Model\Entity\Model
     *
     * @ORM\ManyToOne(targetEntity="MonarcCore\Model\Entity\Model", cascade={"persist"})
     * @ORM\JoinColumns({
     *   @ORM\JoinColumn(name="model_id", referencedColumnName="id", nullable=true)
     * })
     */
    protected $model;

    /**
     * @var \MonarcCore\Model\Entity\ObjectCategory
     *
     * @ORM\ManyToOne(targetEntity="MonarcCore\Model\Entity\ObjectCategory", cascade={"persist"})
     * @ORM\JoinColumns({
     *   @ORM\JoinColumn(name="object_category_id", referencedColumnName="id", nullable=true)
     * })
     */
    protected $category;

    /**
     * @var \MonarcCore\Model\Entity\Asset
     *
     * @ORM\ManyToOne(targetEntity="MonarcCore\Model\Entity\Asset", cascade={"persist"})
     * @ORM\JoinColumns({
     *   @ORM\JoinColumn(name="asset_id", referencedColumnName="id", nullable=true)
     * })
     */
    protected $asset;

    /**
     * @var \MonarcCore\Model\Entity\Object
     *
     * @ORM\ManyToOne(targetEntity="MonarcCore\Model\Entity\Object", cascade={"persist"})
     * @ORM\JoinColumns({
     *   @ORM\JoinColumn(name="source_bdc_object_id", referencedColumnName="id", nullable=true)
     * })
     */
    protected $source;

    /**
     * @var \MonarcCore\Model\Entity\RolfTag
     *
     * @ORM\ManyToOne(targetEntity="MonarcCore\Model\Entity\RolfTag", cascade={"persist"})
     * @ORM\JoinColumns({
     *   @ORM\JoinColumn(name="rolf_tag_id", referencedColumnName="id", nullable=true)
     * })
     */
    protected $rolfTag;

    /**
     * @var string
     *
     * @ORM\Column(name="type", type="string", length=255, nullable=true)
     */
    protected $type = 'anr';

    /**
     * @var smallint
     *
     * @ORM\Column(name="mode", type="smallint", options={"unsigned":true, "default":1})
     */
    protected $mode = '1';

    /**
     * @var smallint
     *
     * @ORM\Column(name="scope", type="smallint", options={"unsigned":true, "default":1})
     */
    protected $scope = '1';

    /**
     * @var string
     *
     * @ORM\Column(name="name1", type="string", length=255, nullable=true)
     */
    protected $name1;

    /**
     * @var string
     *
     * @ORM\Column(name="name2", type="string", length=255, nullable=true)
     */
    protected $name2;

    /**
     * @var string
     *
     * @ORM\Column(name="name3", type="string", length=255, nullable=true)
     */
    protected $name3;

    /**
     * @var string
     *
     * @ORM\Column(name="name4", type="string", length=255, nullable=true)
     */
    protected $name4;

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
     * @var decimal
     *
     * @ORM\Column(name="disponibility", type="decimal", options={"unsigned":true, "default":0})
     */
    protected $disponibility = '0';

    /**
     * @var smallint
     *
     * @ORM\Column(name="position", type="smallint", options={"unsigned":true, "default":1})
     */
    protected $position = '1';

    /**
     * @var string
     *
     * @ORM\Column(name="token_import", type="string", length=255, nullable=true)
     */
    protected $tokenImport;

    /**
     * @var string
     *
     * @ORM\Column(name="original_name", type="string", length=255, nullable=true)
     */
    protected $originalName;

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
     * @return ObjectCategory
     */
    public function getCategory()
    {
        return $this->category;
    }

    /**
     * @param ObjectCategory $category
     * @return Object
     */
    public function setCategory($category)
    {
        $this->category = $category;
        return $this;
    }

    /**
     * @return Asset
     */
    public function getAsset()
    {
        return $this->asset;
    }

    /**
     * @param Asset $asset
     * @return Object
     */
    public function setAsset($asset)
    {
        $this->asset = $asset;
        return $this;
    }

    /**
     * @return RolfTag
     */
    public function getRolfTag()
    {
        return $this->rolfTag;
    }

    /**
     * @param RolfTag $rolfTag
     * @return Object
     */
    public function setRolfTag($rolfTag)
    {
        $this->rolfTag = $rolfTag;
        return $this;
    }

    /**
     * @return source
     */
    public function getsource()
    {
        return $this->source;
    }

    /**
     * @param source $source
     * @return Object
     */
    public function setsource($source)
    {
        $this->source = $source;
        return $this;
    }

    /**
     * @return model
     */
    public function getmodel()
    {
        return $this->model;
    }

    /**
     * @param model $model
     * @return Object
     */
    public function setmodel($model)
    {
        $this->model = $model;
        return $this;
    }

    /**
     * @return smallint
     */
    public function getC()
    {
        return $this->c;
    }

    /**
     * @param smallint $c
     */
    public function setC($c)
    {
        $this->c = $c;
    }

    /**
     * @return smallint
     */
    public function getI()
    {
        return $this->i;
    }

    /**
     * @param smallint $i
     */
    public function setI($i)
    {
        $this->i = $i;
    }

    /**
     * @return smallint
     */
    public function getD()
    {
        return $this->d;
    }

    /**
     * @param smallint $d
     */
    public function setD($d)
    {
        $this->d = $d;
    }

    /**
     * @return int
     */
    public function getAnr()
    {
        return $this->anr;
    }

    /**
     * @param int $anr
     */
    public function setAnr($anr)
    {
        $this->anr = $anr;
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
    public function setId($id)
    {
        $this->id = $id;
    }




    public function getInputFilter($partial = false){
        if (!$this->inputFilter) {
            parent::getInputFilter($partial);

            $texts = [
                'name1', 'name2', 'name3', 'name4',
                'label1', 'label2', 'label3', 'label4',
            ];
            foreach($texts as $text) {
                $this->inputFilter->add(array(
                    'name' => $text,
                    'required' => ((strchr($text, (string) $this->getLanguage())) && (!$partial)) ? true : false,
                    'allow_empty' => false,
                    'filters' => array(
                        array(
                            'name' => '\MonarcCore\Filter\SpecAlnum',
                            'options' => array(
                                'allow_white_space' => true,
                            )
                        ),
                    ),
                    'validators' => array(),
                ));
            }

            $this->inputFilter->add(array(
                'name' => 'c',
                'required' => false,
                'allow_empty' => true,
                'continue_if_empty' => true,
                'filters' => array(),
                'validators' => array(
                    array(
                        'name' => 'InArray',
                        'options' => array(
                            'haystack' => [-1, 0, 1, 2, 3, 4],
                        ),
                        'default' => 0,
                    ),
                ),
            ));

            $this->inputFilter->add(array(
                'name' => 'i',
                'required' => false,
                'allow_empty' => true,
                'continue_if_empty' => true,
                'filters' => array(),
                'validators' => array(
                    array(
                        'name' => 'InArray',
                        'options' => array(
                            'haystack' => [-1, 0, 1, 2, 3, 4],
                        ),
                        'default' => 0,
                    ),
                ),
            ));

            $this->inputFilter->add(array(
                'name' => 'd',
                'required' => false,
                'allow_empty' => true,
                'continue_if_empty' => true,
                'filters' => array(),
                'validators' => array(
                    array(
                        'name' => 'InArray',
                        'options' => array(
                            'haystack' => [-1, 0, 1, 2, 3, 4],
                        ),
                        'default' => 0,
                    ),
                ),
            ));

            $this->inputFilter->add(array(
                'name' => 'scope',
                'required' => false,
                'allow_empty' => true,
                'continue_if_empty' => true,
                'filters' => array(),
                'validators' => array(
                    array(
                        'name' => 'IsInt',
                    ),
                ),
            ));

            $this->inputFilter->add(array(
                'name' => 'mode',
                'required' => false,
                'allow_empty' => true,
                'continue_if_empty' => true,
                'filters' => array(),
                'validators' => array(
                    array(
                        'name' => 'IsInt',
                    ),
                ),
            ));

            $this->inputFilter->add(array(
                'name' => 'asset',
                'required' => true,
                'allow_empty' => true,
                'continue_if_empty' => true,
                'filters' => array(),
                'validators' => array(
                    array(
                        'name' => 'IsInt',
                    ),
                ),
            ));

            $this->inputFilter->add(array(
                'name' => 'category',
                'required' => true,
                'allow_empty' => true,
                'continue_if_empty' => true,
                'filters' => array(),
                'validators' => array(
                    array(
                        'name' => 'IsInt',
                    ),
                ),
            ));

            $this->inputFilter->add(array(
                'name' => 'rolfTag',
                'required' => false,
                'allow_empty' => true,
                'continue_if_empty' => true,
                'filters' => array(),
                'validators' => array(
                    array(
                        'name' => 'IsInt',
                    ),
                ),
            ));

            $this->inputFilter->add(array(
                'name' => 'implicitPosition',
                'required' => false,
                'allow_empty' => false,
                'continue_if_empty' => false,
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

            $this->inputFilter->add(array(
                'name' => 'mode',
                'required' => true,
                'allow_empty' => false,
                'continue_if_empty' => false,
                'filters' => array(),
                'validators' => array(
                    array(
                        'name' => 'InArray',
                        'options' => array(
                            'haystack' => [0, 1],
                        ),
                    ),
                ),
            ));
        }
        return $this->inputFilter;
    }

    public function __construct()
    {
        $this->models = new ArrayCollection();
    }
}

