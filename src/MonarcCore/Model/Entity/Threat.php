<?php

namespace MonarcCore\Model\Entity;

use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\ORM\Mapping as ORM;

/**
 * Threat
 *
 * @ORM\Table(name="threats")
 * @ORM\Entity
 */
class Threat extends AbstractEntity
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
     * @var \MonarcCore\Model\Entity\Theme
     *
     * @ORM\ManyToOne(targetEntity="MonarcCore\Model\Entity\Theme", cascade={"persist"})
     * @ORM\JoinColumns({
     *   @ORM\JoinColumn(name="theme_id", referencedColumnName="id", nullable=true)
     * })
     */
    protected $theme;

    /**
     * @var \MonarcCore\Model\Entity\Model
     *
     * @ORM\ManyToMany(targetEntity="MonarcCore\Model\Entity\Model", inversedBy="threats", cascade={"persist"})
     * @ORM\JoinTable(name="threats_models",
     *  joinColumns={@ORM\JoinColumn(name="threat_id", referencedColumnName="id")},
     *  inverseJoinColumns={@ORM\JoinColumn(name="model_id", referencedColumnName="id")}
     * )
     */
    protected $models;

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
     * @var smallint
     *
     * @ORM\Column(name="status", type="smallint", options={"unsigned":true, "default":1})
     */
    protected $status = '1';

    /**
     * @var smallint
     *
     * @ORM\Column(name="mode", type="smallint", options={"unsigned":true, "default":1})
     */
    protected $mode = '1';

    /**
     * @var string
     *
     * @ORM\Column(name="code", type="string", length=255, nullable=true)
     */
    protected $code;

    /**
     * @var smallint
     *
     * @ORM\Column(name="trend", type="smallint", options={"unsigned":true, "default":0})
     */
    protected $trend = '0';

    /**
     * @var smallint
     *
     * @ORM\Column(name="qualification", type="smallint", options={"unsigned":false, "default":-1})
     */
    protected $qualification = '-1';

    /**
     * @var smallint
     *
     * @ORM\Column(name="c", type="smallint", options={"unsigned":true, "default":0})
     */
    protected $c = '1';

    /**
     * @var smallint
     *
     * @ORM\Column(name="i", type="smallint", options={"unsigned":true, "default":0})
     */
    protected $i = '1';

    /**
     * @var smallint
     *
     * @ORM\Column(name="d", type="smallint", options={"unsigned":true, "default":0})
     */
    protected $d = '1';

    /**
     * @var text
     *
     * @ORM\Column(name="comment", type="text", nullable=true)
     */
    protected $comment;

    /**
     * @var boolean
     *
     * @ORM\Column(name="is_accidental", type="boolean", options={"unsigned":true, "default":0})
     */
    protected $isAccidental = '0';

    /**
     * @var boolean
     *
     * @ORM\Column(name="is_deliberate", type="boolean", options={"unsigned":true, "default":0})
     */
    protected $isDeliberate = '0';

    /**
     * @var text
     *
     * @ORM\Column(name="desc_accidental1", type="text", nullable=true)
     */
    protected $descAccidental1;

    /**
     * @var text
     *
     * @ORM\Column(name="desc_accidental2", type="text", nullable=true)
     */
    protected $descAccidental2;

    /**
     * @var text
     *
     * @ORM\Column(name="desc_accidental3", type="text", nullable=true)
     */
    protected $descAccidental3;

    /**
     * @var text
     *
     * @ORM\Column(name="desc_accidental4", type="text", nullable=true)
     */
    protected $descAccidental4;

    /**
     * @var text
     *
     * @ORM\Column(name="ex_accidental1", type="text", nullable=true)
     */
    protected $exAccidental1;

    /**
     * @var text
     *
     * @ORM\Column(name="ex_accidental2", type="text", nullable=true)
     */
    protected $exAccidental2;

    /**
     * @var text
     *
     * @ORM\Column(name="ex_accidental3", type="text", nullable=true)
     */
    protected $exAccidental3;

    /**
     * @var text
     *
     * @ORM\Column(name="ex_accidental4", type="text", nullable=true)
     */
    protected $exAccidental4;

    /**
     * @var text
     *
     * @ORM\Column(name="desc_deliberate1", type="text", nullable=true)
     */
    protected $descDeliberate1;

    /**
     * @var text
     *
     * @ORM\Column(name="desc_deliberate2", type="text", nullable=true)
     */
    protected $descDeliberate2;

    /**
     * @var text
     *
     * @ORM\Column(name="desc_deliberate3", type="text", nullable=true)
     */
    protected $descDeliberate3;

    /**
     * @var text
     *
     * @ORM\Column(name="desc_deliberate4", type="text", nullable=true)
     */
    protected $descDeliberate4;

    /**
     * @var text
     *
     * @ORM\Column(name="ex_deliberate1", type="text", nullable=true)
     */
    protected $exDeliberate1;

    /**
     * @var text
     *
     * @ORM\Column(name="ex_deliberate2", type="text", nullable=true)
     */
    protected $exDeliberate2;

    /**
     * @var text
     *
     * @ORM\Column(name="ex_deliberate3", type="text", nullable=true)
     */
    protected $exDeliberate3;

    /**
     * @var text
     *
     * @ORM\Column(name="ex_deliberate4", type="text", nullable=true)
     */
    protected $exDeliberate4;

    /**
     * @var text
     *
     * @ORM\Column(name="type_consequences1", type="text", nullable=true)
     */
    protected $typeConsequences1;

    /**
     * @var text
     *
     * @ORM\Column(name="type_consequences2", type="text", nullable=true)
     */
    protected $typeConsequences2;

    /**
     * @var text
     *
     * @ORM\Column(name="type_consequences3", type="text", nullable=true)
     */
    protected $typeConsequences3;

    /**
     * @var text
     *
     * @ORM\Column(name="type_consequences4", type="text", nullable=true)
     */
    protected $typeConsequences4;


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
     * @return Threat
     */
    public function setId($id)
    {
        $this->id = $id;
        return $this;
    }

    /**
     * Set model
     *
     * @param key
     * @param Model $model
     */
    public function setModel($id, Model $model)
    {
        $this->models[$id] = $model;
    }

    /**
     * Set theme
     *
     * @param key
     * @param Theme $theme
     */
    public function setTheme(Theme $theme)
    {
        $this->theme = $theme;
    }

    /**
     * @return Model
     */
    public function getModels()
    {
        return $this->models;
    }

    /**
     * @param Model $models
     * @return Threat
     */
    public function setModels($models)
    {
        $this->models = $models;
        return $this;
    }

    /**
     * Add model
     *
     * @param Model $model
     */
    public function addModel(Model $model)
    {
        $this->models->add($model);
    }

    public function getInputFilter($partial = false){
        if (!$this->inputFilter) {
            parent::getInputFilter($partial);

            $texts = ['label1', 'label2', 'label3', 'label4'];
            foreach($texts as $text) {
                $this->inputFilter->add(array(
                    'name' => $text,
                    'required' => ((strstr($text, (string) $this->getLanguage())) && (!$partial)) ? true : false,
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

            $descriptions = ['description1', 'description2', 'description3', 'description4'];
            foreach($descriptions as $description) {
                $this->inputFilter->add(array(
                    'name' => $description,
                    'required' => false,
                    'allow_empty' => true,
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
                            'haystack' => [0, 1],
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
                            'haystack' => [0, 1],
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
                            'haystack' => [0, 1],
                        ),
                        'default' => 0,
                    ),
                ),
            ));

            $this->inputFilter->add(array(
                'name' => 'code',
                'required' => ($partial) ? false : true,
                'allow_empty' => false,
                'continue_if_empty' => false,
                'filters' => array(),
                'validators' => array(),
            ));

            $this->inputFilter->add(array(
                'name' => 'mode',
                'required' => ($partial) ? false : true,
                'allow_empty' => false,
                'continue_if_empty' => false,
                'filters' => array(),
                'validators' => array(),
            ));

            $this->inputFilter->add(array(
                'name' => 'status',
                'required' => false,
                'allow_empty' => false,
                'filters' => array(
                    array('name' => 'ToInt'),
                ),
                'validators' => array(
                    array(
                        'name' => 'InArray',
                        'options' => array(
                            'haystack' => array(self::STATUS_INACTIVE, self::STATUS_ACTIVE),
                        ),
                    ),
                ),
            ));

            $validatorsCode = [];
            if (!$partial) {
                $validatorsCode = array(
                    array(
                        'name' => '\MonarcCore\Validator\UniqueCode',
                        'options' => array(
                            'entity' => $this
                        ),
                    ),
                );
            }

            $this->inputFilter->add(array(
                'name' => 'code',
                'required' => ($partial) ? false : true,
                'allow_empty' => false,
                'filters' => array(),
                'validators' => $validatorsCode
            ));
        }
        return $this->inputFilter;
    }

    public function __construct()
    {
        $this->models = new ArrayCollection();
    }
}

