<?php
/**
 * @link      https://github.com/monarc-project for the canonical source repository
 * @copyright Copyright (c) 2016-2020 SMILE GIE Securitymadein.lu - Licensed under GNU Affero GPL v3
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
     * @var AssetSuperClass
     *
     * @ORM\ManyToOne(targetEntity="Asset", cascade={"persist"})
     * @ORM\JoinColumns({
     *   @ORM\JoinColumn(name="asset_id", referencedColumnName="uuid", nullable=true)
     * })
     */
    protected $asset;

    /**
     * @var ObjectSuperClass
     *
     * @ORM\ManyToOne(targetEntity="MonarcObject", cascade={"persist"})
     * @ORM\JoinColumns({
     *   @ORM\JoinColumn(name="object_id", referencedColumnName="uuid", nullable=true)
     * })
     */
    protected $object;

    /**
     * @var InstanceSuperClass
     *
     * @ORM\ManyToOne(targetEntity="Instance", cascade={"persist"})
     * @ORM\JoinColumns({
     *   @ORM\JoinColumn(name="root_id", referencedColumnName="id", nullable=true)
     * })
     */
    protected $root;

    /**
     * @var InstanceSuperClass
     *
     * @ORM\ManyToOne(targetEntity="Instance", cascade={"persist"})
     * @ORM\JoinColumns({
     *   @ORM\JoinColumn(name="parent_id", referencedColumnName="id", nullable=true)
     * })
     */
    protected $parent;

    /**
     * @var InstanceConsequenceSuperClass[]|ArrayCollection
     *
     * @ORM\OneToMany(targetEntity="InstanceConsequence", mappedBy="instance")
     */
    protected $instanceConsequences;

    /**
     * @var InstanceRiskSuperClass[]|ArrayCollection
     *
     * @ORM\OneToMany(targetEntity="InstanceRisk", mappedBy="instance")
     */
    protected $instanceRisks;

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

    public function __construct($obj = null)
    {
        $this->instanceConsequences = new ArrayCollection();
        $this->instanceRisks = new ArrayCollection();

        parent::__construct($obj);
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
     * @return AssetSuperClass
     */
    public function getAsset()
    {
        return $this->asset;
    }

    /**
     * @param AssetSuperClass $asset
     */
    public function setAsset($asset): self
    {
        $this->asset = $asset;

        return $this;
    }

    /**
     * @return ObjectSuperClass
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

    public function getLevel(): int
    {
        return $this->level;
    }

    public function setLevel(int $level): self
    {
        $this->level = $level;

        return $this;
    }

    public function isLevelRoot(): bool
    {
        return $this->level === static::LEVEL_ROOT;
    }

    public function setNames(array $names): self
    {
        foreach (range(1, 4) as $index) {
            $key = 'name' . $index;
            if (isset($names[$key])) {
                $this->{$key} = $names[$key];
            }
        }

        return $this;
    }

    public function getName1(): string
    {
        return (string)$this->name1;
    }

    public function getName2(): string
    {
        return (string)$this->name2;
    }

    public function getName3(): string
    {
        return (string)$this->name3;
    }

    public function getName4(): string
    {
        return (string)$this->name4;
    }

    public function setLabels(array $labels): self
    {
        foreach (range(1, 4) as $index) {
            $key = 'label' . $index;
            if (isset($labels[$key])) {
                $this->{$key} = $labels[$key];
            }
        }

        return $this;
    }

    public function getLabel1(): string
    {
        return (string)$this->label1;
    }

    public function getLabel2(): string
    {
        return (string)$this->label2;
    }

    public function getLabel3(): string
    {
        return (string)$this->label3;
    }

    public function getLabel4(): string
    {
        return (string)$this->label4;
    }

    public function setDisponibility(float $disponibility): self
    {
        $this->disponibility = $disponibility;

        return $this;
    }

    public function getDisponibility(): float
    {
        return $this->disponibility;
    }

    public function setAssetType(int $assetType): self
    {
        $this->assetType = $assetType;

        return $this;
    }

    public function getAssetType(): int
    {
        return $this->assetType;
    }

    public function setExportable(int $exportable): self
    {
        $this->exportable = $exportable;

        return $this;
    }

    public function getExportable(): int
    {
        return $this->exportable;
    }

    public function setConfidentiality(int $c): self
    {
        $this->c = $c;

        return $this;
    }

    public function getConfidentiality(): int
    {
        return $this->c;
    }

    public function setIntegrity(int $i): self
    {
        $this->i = $i;

        return $this;
    }

    public function getIntegrity(): int
    {
        return $this->i;
    }

    public function setAvailability(int $d): self
    {
        $this->d = $d;

        return $this;
    }

    public function getAvailability(): int
    {
        return $this->d;
    }

    public function setInheritedConfidentiality(int $ch): self
    {
        $this->ch = $ch;

        return $this;
    }

    public function getInheritedConfidentiality(): int
    {
        return $this->ch;
    }

    public function setInheritedIntegrity(int $ih): self
    {
        $this->ih = $ih;

        return $this;
    }

    public function getInheritedIntegrity(): int
    {
        return $this->ih;
    }

    public function setInheritedAvailability(int $dh): self
    {
        $this->dh = $dh;

        return $this;
    }

    public function getInheritedAvailability(): int
    {
        return $this->dh;
    }

    public static function getAvailableScalesCriteria(): array
    {
        return [
            'c' => 'Confidentiality',
            'i' => 'Integrity',
            'd' => 'Availability'
        ];
    }

    public function getPosition(): int
    {
        return $this->position;
    }

    public function setPosition(int $position): self
    {
        $this->position = $position;

        return $this;
    }

    /**
     * @return InstanceConsequenceSuperClass[]
     */
    public function getInstanceConsequences()
    {
        return $this->instanceConsequences;
    }

    public function addInstanceConsequence(InstanceConsequenceSuperClass $instanceConsequence): self
    {
        if (!$this->instanceConsequences->contains($instanceConsequence)) {
            $this->instanceConsequences->add($instanceConsequence);
            $instanceConsequence->setInstance($this);
        }

        return $this;
    }

    public function resetInstanceConsequences(): self
    {
        $this->instanceConsequences = new ArrayCollection();

        return $this;
    }

    public function getInstanceRisks()
    {
        return $this->instanceRisks;
    }

    public function addInstanceRisk(InstanceRiskSuperClass $instanceRisk): self
    {
        if (!$this->instanceRisks->contains($instanceRisk)) {
            $this->instanceRisks->add($instanceRisk);
            $instanceRisk->setInstance($this);
        }

        return $this;
    }

    public function resetInstanceRisks(): self
    {
        $this->instanceRisks = new ArrayCollection();

        return $this;
    }

    /**
     * Returns the instance hierarchy array ordered from it's root through all the children to the instance itself.
     * Each element is a normalized array of instance properties.
     */
    public function getHierarchyArray(): array
    {
        if ($this->root === null || $this->id === $this->root->getId()) {
            return [$this->getJsonArray()];
        }

        return $this->getInstanceParents($this);
    }

    private function getInstanceParents(InstanceSuperClass $instance): array
    {
        if ($instance->getRoot() === null || $instance->getId() === $instance->getRoot()->getId()) {
            return [$instance->getJsonArray()];
        }

        return array_merge($this->getInstanceParents($instance->getParent()), [$instance->getJsonArray()]);
    }

    public function getHierarchyString(): string
    {
        return implode(' > ', array_column($this->getHierarchyArray(), 'name' . $this->anr->getLanguage()));
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
}
