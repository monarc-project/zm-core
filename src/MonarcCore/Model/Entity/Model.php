<?php

namespace MonarcCore\Model\Entity;

use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\ORM\Mapping as ORM;

/**
 * Model
 *
 * @ORM\Table(name="models")
 * @ORM\Entity
 */
class Model extends AbstractEntity
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
     * @var \MonarcCore\Model\Entity\Anr
     *
     * @ORM\ManyToOne(targetEntity="MonarcCore\Model\Entity\Anr", cascade={"persist"})
     * @ORM\JoinColumns({
     *   @ORM\JoinColumn(name="anr_id", referencedColumnName="id", nullable=true)
     * })
     */
    protected $anr;

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
     * @var boolean
     *
     * @ORM\Column(name="status", type="boolean", options={"unsigned":true, "default":1})
     */
    protected $status = '1';

    /**
     * @var boolean
     *
     * @ORM\Column(name="is_scales_updatable", type="boolean", options={"unsigned":true, "default":1})
     */
    protected $isScalesUpdatable = '1';

    /**
     * @var boolean
     *
     * @ORM\Column(name="is_default", type="boolean", options={"unsigned":true, "default":0})
     */
    protected $isDefault = '0';

    /**
     * @var boolean
     *
     * @ORM\Column(name="is_deleted", type="boolean", options={"unsigned":true, "default":0})
     */
    protected $isDeleted = '0';

    /**
     * @var boolean
     *
     * @ORM\Column(name="is_generic", type="boolean", options={"unsigned":true, "default":1})
     */
    protected $isGeneric = '1';

    /**
     * @var boolean
     *
     * @ORM\Column(name="is_regulator", type="boolean", options={"unsigned":true, "default":0})
     */
    protected $isRegulator = '0';

    /**
     * @var boolean
     *
     * @ORM\Column(name="show_rolf_brut", type="boolean", options={"unsigned":true, "default":1})
     */
    protected $showRolfBrut = '1';

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
     * @var \Doctrine\Common\Collections\Collection
     * @ORM\ManyToMany(targetEntity="MonarcCore\Model\Entity\Asset", mappedBy="models", cascade={"persist"})
     */
    protected $assets;

    /**
     * @var \Doctrine\Common\Collections\Collection
     * @ORM\ManyToMany(targetEntity="MonarcCore\Model\Entity\Threat", mappedBy="models", cascade={"persist"})
     */
    protected $threats;

    public function __construct()
    {
        $this->assets = new ArrayCollection();
        $this->threats = new ArrayCollection();
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
     * @return Model
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
     * @return Model
     */
    public function setAnr($anr)
    {
        $this->anr = $anr;
        return $this;
    }

    /**
     * @return boolean
     */
    public function isIsDeleted()
    {
        return $this->isDeleted;
    }

    /**
     * @param boolean $isDeleted
     * @return Model
     */
    public function setIsDeleted($isDeleted)
    {
        $this->isDeleted = $isDeleted;
        return $this;
    }




    public function getInputFilter(){
        if (!$this->inputFilter) {
            parent::getInputFilter();

            $texts = ['label1', 'label2', 'label3', 'label4'];
            $descriptions =  ['description1', 'description2', 'description3', 'description4'];
            $booleans = ['status', 'isScalesUpdatable', 'isDefault', 'isDeleted', 'isGeneric', 'isRegulator', 'showRolfBrut'];

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
                            ),
                        ),
                    ),
                    'validators' => array(),
                ));
            }
            foreach($descriptions as $description) {
                $this->inputFilter->add(array(
                    'name' => $description,
                    'required' => false,
                    'allow_empty' => true,
                    'filters' => array(
                        array(
                            'name' => 'Alnum',
                            'options' => array(
                                'allow_white_space' => true,
                            ),
                        ),
                    ),
                    'validators' => array(),
                ));
            }
            foreach($booleans as $boolean) {
                $this->inputFilter->add(array(
                    'name' => $boolean,
                    'required' => false,
                    'allow_empty' => true,
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
        }
        return $this->inputFilter;
    }
}

