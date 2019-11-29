<?php
/**
 * @link      https://github.com/monarc-project for the canonical source repository
 * @copyright Copyright (c) 2016-2019  SMILE GIE Securitymadein.lu - Licensed under GNU Affero GPL v3
 * @license   MONARC is licensed under GNU Affero General Public License version 3
 */

namespace Monarc\Core\Model\Entity;

use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\ORM\Mapping as ORM;
use Monarc\Core\Model\Entity\Traits\CreateEntityTrait;
use Monarc\Core\Model\Entity\Traits\UpdateEntityTrait;

/**
 * Instance
 *
 * @ORM\Table(name="instances", indexes={
 *      @ORM\Index(name="anr", columns={"anr_id"}),
 *      @ORM\Index(name="asset_id", columns={"asset_id"}),
 *      @ORM\Index(name="object_id", columns={"object_id"}),
 *      @ORM\Index(name="root_id", columns={"root_id"}),
 *      @ORM\Index(name="parent_id", columns={"parent_id"})
 * })
 * @ORM\MappedSuperclass
 * @ORM\HasLifecycleCallbacks()
 */
class InstanceSuperClass extends AbstractEntity
{
    use CreateEntityTrait;
    use UpdateEntityTrait;

    const LEVEL_ROOT = 1; //instance de racine d'un objet
    const LEVEL_LEAF = 2; //instance d'une feuille d'un objet
    const LEVEL_INTER = 3; //instance d'une noeud intermédiaire d'un objet

    const MODE_CREA_ROOT = 1;//Mode de création d'une instance qui permet d'instancier directement une racine
    const MODE_CREA_NODE = 2;//Mode de création d'une instance à partir d'un nouveau composant d'objet

    /**
     * @var integer
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
     * @var \Monarc\Core\Model\Entity\Asset
     *
     * @ORM\ManyToOne(targetEntity="Monarc\Core\Model\Entity\Asset", cascade={"persist"})
     * @ORM\JoinColumns({
     *   @ORM\JoinColumn(name="asset_id", referencedColumnName="uuid", nullable=true)
     * })
     */
    protected $asset;

    /**
     * @var \Monarc\Core\Model\Entity\MonarcObject
     *
     * @ORM\ManyToOne(targetEntity="Monarc\Core\Model\Entity\MonarcObject", cascade={"persist"})
     * @ORM\JoinColumns({
     *   @ORM\JoinColumn(name="object_id", referencedColumnName="uuid", nullable=true)
     * })
     */
    protected $object;

    /**
     * @var \Monarc\Core\Model\Entity\Instance
     *
     * @ORM\ManyToOne(targetEntity="Monarc\Core\Model\Entity\Instance", cascade={"persist"})
     * @ORM\JoinColumns({
     *   @ORM\JoinColumn(name="root_id", referencedColumnName="id", nullable=true)
     * })
     */
    protected $root;

    /**
     * @var \Monarc\Core\Model\Entity\Instance
     *
     * @ORM\ManyToOne(targetEntity="Monarc\Core\Model\Entity\Instance", cascade={"persist"})
     * @ORM\JoinColumns({
     *   @ORM\JoinColumn(name="parent_id", referencedColumnName="id", nullable=true)
     * })
     */
    protected $parent;

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
     * @var float
     *
     * @ORM\Column(name="disponibility", type="decimal", options={"unsigned":true, "default":0})
     */
    protected $disponibility = 0;

    /**
     * @var int
     *
     * @ORM\Column(name="level", type="smallint", options={"unsigned":true, "default":1})
     */
    protected $level = 1;

    /**
     * @var int
     *
     * @ORM\Column(name="asset_type", type="smallint", options={"unsigned":true, "default":3})
     */
    protected $assetType = 3;

    /**
     * @var int
     *
     * @ORM\Column(name="exportable", type="smallint", options={"unsigned":true, "default":1})
     */
    protected $exportable = 1;

    /**
     * @var int
     *
     * @ORM\Column(name="c", type="smallint", options={"unsigned":true, "default":1})
     */
    protected $c = 1;

    /**
     * @var int
     *
     * @ORM\Column(name="i", type="smallint", options={"unsigned":true, "default":1})
     */
    protected $i = 1;

    /**
     * @var int
     *
     * @ORM\Column(name="d", type="smallint", options={"unsigned":true, "default":1})
     */
    protected $d = 1;

    /**
     * @var int
     *
     * @ORM\Column(name="ch", type="smallint", options={"unsigned":true, "default":0})
     */
    protected $ch = 0;

    /**
     * @var int
     *
     * @ORM\Column(name="ih", type="smallint", options={"unsigned":true, "default":0})
     */
    protected $ih = 0;

    /**
     * @var int
     *
     * @ORM\Column(name="dh", type="smallint", options={"unsigned":true, "default":0})
     */
    protected $dh = 0;

    /**
     * @var int
     *
     * @ORM\Column(name="position", type="smallint", options={"unsigned":true, "default":1})
     */
    protected $position = 1;

    /**
     * @return int
     */
    public function getId()
    {
        return $this->id;
    }

    /**
     * @param int $id
     * @return Instance
     */
    public function setId($id): self
    {
        $this->id = $id;

        return $this;
    }

    public function getAnr(): AnrSuperClass
    {
        return $this->anr;
    }

    public function setAnr(?AnrSuperClass $anr): self
    {
        $this->anr = $anr;

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
     * @return Instance
     */
    public function setAsset($asset): self
    {
        $this->asset = $asset;

        return $this;
    }

    /**
     * @return Object
     */
    public function getObject()
    {
        return $this->object;
    }

    public function setObject(?ObjectSuperClass $object): self
    {
        $this->object = $object;

        return $this;
    }

    public function getRoot(): ?InstanceSuperClass
    {
        return $this->root;
    }

    public function setRoot(?InstanceSuperClass $root)
    {
        $this->root = $root;
        return $this;
    }

    public function getParent(): ?InstanceSuperClass
    {
        return $this->parent;
    }

    public function setParent(?InstanceSuperClass $parent): self
    {
        $this->parent = $parent;

        return $this;
    }

    /**
     * @return int
     */
    public function getLevel()
    {
        return $this->level;
    }

    public function setLevel(int $level): self
    {
        $this->level = $level;

        return $this;
    }

    protected $parameters = array(
        'implicitPosition' => array(
            'field' => 'parent',
            'root' => 'root',
            'subField' => [
                'anr',
            ],
        ),
    );

    public function getInputFilter($partial = false)
    {
        parent::getInputFilter($partial);

        $texts = [
            'name1', 'name2', 'name3', 'name4',
            'label1', 'label2', 'label3', 'label4',
        ];
        foreach ($texts as $text) {
            $this->inputFilter->add(array(
                'name' => $text,
                'required' => strstr($text, (string)$this->getLanguage()) && (!$partial),
                'allow_empty' => false,
                'filters' => array(),
                'validators' => array(),
            ));
        }

        $fields = ['c', 'i', 'd', 'asset', 'object'];
        foreach ($fields as $field) {
            $this->inputFilter->add(array(
                'name' => $field,
                'required' => (!$partial) ? true : false,
                'allow_empty' => false,
                'filters' => array(),
                'validators' => array(),
            ));
        }

        $descriptions = ['description1', 'description2', 'description3', 'description4'];
        foreach ($descriptions as $description) {
            $this->inputFilter->add(array(
                'name' => $description,
                'required' => false,
                'allow_empty' => true,
                'filters' => array(),
                'validators' => array(),
            ));
        }
        return $this->inputFilter;
    }

    public function __construct($obj = null)
    {
        $this->instances = new ArrayCollection();
        parent::__construct($obj);
    }
}
