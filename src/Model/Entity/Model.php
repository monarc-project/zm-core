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
 * Model
 *
 * @ORM\Table(name="models", indexes={
 *      @ORM\Index(name="anr", columns={"anr_id"})
 * })
 * @ORM\Entity
 * @ORM\HasLifecycleCallbacks()
 */
class Model extends AbstractEntity
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
     * @var Anr
     *
     * @ORM\ManyToOne(targetEntity="Anr", cascade={"persist", "REMOVE"})
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
     * @var int
     *
     * @ORM\Column(name="status", type="smallint", options={"unsigned":true, "default":1})
     */
    protected $status = '1';

    /**
     * @var int
     *
     * @ORM\Column(name="is_scales_updatable", type="smallint", options={"unsigned":true, "default":1})
     */
    protected $isScalesUpdatable = 1;

    /**
     * @var int
     *
     * @ORM\Column(name="is_default", type="smallint", options={"unsigned":true, "default":0})
     */
    protected $isDefault = 0;

    /**
     * @var int
     *
     * @ORM\Column(name="is_deleted", type="smallint", options={"unsigned":true, "default":0})
     */
    protected $isDeleted = 0;

    /**
     * @var int
     *
     * @ORM\Column(name="is_generic", type="smallint", options={"unsigned":true, "default":1})
     */
    protected $isGeneric = 1;

    /**
     * @var int
     *
     * @ORM\Column(name="is_regulator", type="smallint", options={"unsigned":true, "default":0})
     */
    protected $isRegulator = 0;

    /**
     * @var int
     *
     * @ORM\Column(name="show_rolf_brut", type="smallint", options={"unsigned":true, "default":1})
     */
    protected $showRolfBrut = 1;

    /**
     * @var Asset[]|ArrayCollection
     * @ORM\ManyToMany(targetEntity="Asset", mappedBy="models", cascade={"persist"})
     */
    protected $assets;

    /**
     * @var Threat[]|ArrayCollection
     * @ORM\ManyToMany(targetEntity="Threat", mappedBy="models", cascade={"persist"})
     */
    protected $threats;

    /**
     * @var Vulnerability[]|ArrayCollection
     * @ORM\ManyToMany(targetEntity="Vulnerability", mappedBy="models", cascade={"persist"})
     */
    protected $vulnerabilities;

    public function __construct($obj = null)
    {
        $this->assets = new ArrayCollection();
        $this->threats = new ArrayCollection();
        $this->vulnerabilities = new ArrayCollection();

        parent::__construct($obj);
    }

    public function getId()
    {
        return $this->id;
    }

    public function setId($id)
    {
        $this->id = $id;

        return $this;
    }

    public function getAnr(): Anr
    {
        return $this->anr;
    }

    public function setAnr(Anr $anr): self
    {
        $this->anr = $anr;

        return $this;
    }

    public function getLabel(int $languageIndex): string
    {
        if (!\in_array($languageIndex, range(1, 4), true)) {
            return '';
        }

        return (string)$this->{'label' . $languageIndex};
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

    public function getDescription(int $languageIndex): string
    {
        if (!\in_array($languageIndex, range(1, 4), true)) {
            return '';
        }

        return (string)$this->{'description' . $languageIndex};
    }

    public function getDescriptions(): array
    {
        return [
            'description1' => $this->description1,
            'description2' => $this->description2,
            'description3' => $this->description3,
            'description4' => $this->description4,
        ];
    }

    public function setDescriptions(array $descriptions): self
    {
        foreach (range(1, 4) as $index) {
            $key = 'description' . $index;
            if (isset($descriptions[$key])) {
                $this->{$key} = $descriptions[$key];
            }
        }

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

    public function getAssets()
    {
        return $this->assets;
    }

    public function addAsset(Asset $asset): self
    {
        if (!$this->assets->contains($asset)) {
            $this->assets->add($asset);
            $asset->addModel($this);
        }

        return $this;
    }

    public function getThreats()
    {
        return $this->threats;
    }

    public function addThreat(Threat $threat): self
    {
        if (!$this->threats->contains($threat)) {
            $this->threats->add($threat);
            $threat->addModel($this);
        }

        return $this;
    }

    public function getVulnerabilities()
    {
        return $this->vulnerabilities;
    }

    public function addVulnerability(Vulnerability $vulnerability): self
    {
        if (!$this->vulnerabilities->contains($vulnerability)) {
            $this->vulnerabilities->add($vulnerability);
            $vulnerability->addModel($this);
        }

        return $this;
    }

    public function getStatus(): int
    {
        return $this->status;
    }

    public function setStatus(int $status): self
    {
        $this->status = $status;

        return $this;
    }

    public function isActive(): bool
    {
        return $this->status === static::STATUS_ACTIVE;
    }

    public function areScalesUpdatable(): bool
    {
        return (bool)$this->isScalesUpdatable;
    }

    public function setAreScalesUpdatable(bool $areScalesUpdatable): self
    {
        $this->isScalesUpdatable = (int)$areScalesUpdatable;

        return $this;
    }

    public function isDefault(): bool
    {
        return (bool)$this->isDefault;
    }

    public function setIsDefault(bool $isDefault): self
    {
        $this->isDefault = (int)$isDefault;

        return $this;
    }

    public function isGeneric(): bool
    {
        return (bool)$this->isGeneric;
    }

    public function setIsGeneric(bool $isGeneric): self
    {
        $this->isGeneric = (int)$isGeneric;

        return $this;
    }

    public function isRegulator(): bool
    {
        return (bool)$this->isRegulator;
    }

    public function setIsRegulator(bool $isRegulator): self
    {
        $this->isRegulator = (int)$isRegulator;

        return $this;
    }

    public function getShowRolfBrut(): bool
    {
        return (bool)$this->showRolfBrut;
    }

    public function setShowRolfBrut(bool $showRolfBrut): self
    {
        $this->showRolfBrut = (int)$showRolfBrut;

        return $this;
    }

    public function getInputFilter($partial = false)
    {
        if (!$this->inputFilter) {
            parent::getInputFilter($partial);

            $texts = ['label1', 'label2', 'label3', 'label4'];
            foreach ($texts as $text) {
                $this->inputFilter->add(array(
                    'name' => $text,
                    'required' => ((strchr($text, (string)$this->getLanguage())) && (!$partial)) ? true : false,
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

            $booleans = ['isScalesUpdatable', 'isDefault', 'isDeleted', 'isGeneric', 'isRegulator', 'showRolfBrut'];
            foreach ($booleans as $boolean) {
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
                            'haystack' => array(self::STATUS_INACTIVE, self::STATUS_ACTIVE, self::STATUS_DELETED),
                        ),
                    ),
                ),
            ));
        }
        return $this->inputFilter;
    }
}
