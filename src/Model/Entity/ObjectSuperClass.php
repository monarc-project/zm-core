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
use Ramsey\Uuid\Lazy\LazyUuidFromString;
use Ramsey\Uuid\Uuid;

/**
 * ObjectSuperClass
 *
 * @ORM\Table(name="objects", indexes={
 *      @ORM\Index(name="object_category_id", columns={"object_category_id"}),
 *      @ORM\Index(name="asset_id", columns={"asset_id"}),
 *      @ORM\Index(name="rolf_tag_id", columns={"rolf_tag_id"})
 * })
 * @ORM\MappedSuperclass
 * @ORM\HasLifecycleCallbacks()
 */
class ObjectSuperClass extends AbstractEntity
{
    use CreateEntityTrait;
    use UpdateEntityTrait;

    public const SCOPE_LOCAL = 1;
    public const SCOPE_GLOBAL = 2;

    public const MODE_GENERIC = 0;
    public const MODE_SPECIFIC = 1;

    /**
     * @var LazyUuidFromString|string
     *
     * @ORM\Column(name="uuid", type="uuid", nullable=false)
     * @ORM\Id
     */
    protected $uuid;

    /**
     * @var AnrSuperClass
     *
     * @ORM\ManyToOne(targetEntity="Anr", cascade={"persist"})
     * @ORM\JoinColumns({
     *   @ORM\JoinColumn(name="anr_id", referencedColumnName="id", nullable=true)
     * })
     */
    protected $anr;

    /**
     * @var ArrayCollection|AnrSuperClass[]
     *
     * @ORM\ManyToMany(targetEntity="Anr", inversedBy="objects", cascade={"persist"})
     * @ORM\JoinTable(name="anrs_objects",
     *  joinColumns={@ORM\JoinColumn(name="object_id", referencedColumnName="uuid")},
     *  inverseJoinColumns={@ORM\JoinColumn(name="anr_id", referencedColumnName="id")}
     * )
     */
    protected $anrs;

    /**
     * @var ObjectCategorySuperClass
     *
     * @ORM\ManyToOne(targetEntity="ObjectCategory", cascade={"persist"})
     * @ORM\JoinColumns({
     *   @ORM\JoinColumn(name="object_category_id", referencedColumnName="id", nullable=true)
     * })
     */
    protected $category;

    /**
     * @var AssetSuperClass
     *
     * @ORM\ManyToOne(targetEntity="Asset", cascade={"persist"})
     * @ORM\JoinColumns({
     *   @ORM\JoinColumn(name="asset_id", referencedColumnName="uuid", nullable=true, onDelete="SET NULL")
     * })
     */
    protected $asset;

    /**
     * @var RolfTagSuperClass
     *
     * @ORM\ManyToOne(targetEntity="RolfTag", cascade={"persist"})
     * @ORM\JoinColumns({
     *   @ORM\JoinColumn(name="rolf_tag_id", referencedColumnName="id", nullable=true)
     * })
     */
    protected $rolfTag;

    /**
     * @var int
     *
     * @ORM\Column(name="mode", type="smallint", options={"unsigned":true, "default":1})
     */
    protected $mode = 1;

    /**
     * @var int
     *
     * @ORM\Column(name="scope", type="smallint", options={"unsigned":true, "default":1})
     */
    protected $scope = 1;

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
     * @ORM\Column(name="position", type="smallint", options={"unsigned":true, "default":0})
     */
    protected $position = 0;

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

    protected $parameters = array(
        'implicitPosition' => array(
            'field' => 'category',
        ),
    );

    public function getUuid(): ?string
    {
        return $this->uuid;
    }

    /**
     * @param string $uuid
     *
     * @return self
     */
    public function setUuid($uuid): self
    {
        $this->uuid = $uuid;

        return $this;
    }

    /**
     * @ORM\PrePersist
     */
    public function generateAndSetUuid(): self
    {
        if ($this->uuid === null) {
            $this->uuid = Uuid::uuid4();
        }

        return $this;
    }

    /**
     * @return AnrSuperClass|null
     */
    public function getAnr(): ?AnrSuperClass
    {
        return $this->anr;
    }

    /**
     * @param AnrSuperClass|null $anr
     */
    public function setAnr($anr): self
    {
        $this->anr = $anr;

        return $this;
    }

    /**
     * @return ObjectCategorySuperClass
     */
    public function getCategory()
    {
        return $this->category;
    }

    /**
     * @param ObjectCategorySuperClass $category
     */
    public function setCategory($category): self
    {
        $this->category = $category;

        return $this;
    }

    public function getAsset(): ?AssetSuperClass
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

    public function getRolfTag(): ?RolfTagSuperClass
    {
        return $this->rolfTag;
    }

    public function setRolfTag(?RolfTagSuperClass $rolfTag)
    {
        $this->rolfTag = $rolfTag;

        return $this;
    }

    /**
     * @return AnrSuperClass[]
     */
    public function getAnrs()
    {
        return $this->anrs;
    }

    /**
     * @param ArrayCollection|AnrSuperClass[] $anrs
     */
    public function setAnrs($anrs): self
    {
        $this->anrs = $anrs;

        return $this;
    }

    /**
     * @param AnrSuperClass $anr
     */
    public function addAnr(AnrSuperClass $anr): self
    {
        if ($this->anrs === null) {
            $this->anrs = new ArrayCollection();
        }

        if (!$this->anrs->contains($anr)) {
            $this->anrs->add($anr);
        }

        return $this;
    }

    public function setName(string $nameKey, string $nameValue): self
    {
        if (in_array($nameKey, ['name1', 'name2', 'name3', 'name4'], true)) {
            $this->{$nameKey} = $nameValue;
        }

        return $this;
    }

    public function getName(int $languageIndex): string
    {
        if (!in_array($languageIndex, range(1, 4), true)) {
            return '';
        }

        return (string)$this->{'name' . $languageIndex};
    }

    public function setLabel(string $labelKey, string $labelValue): self
    {
        if (in_array($labelKey, ['label1', 'label2', 'label3', 'label4'], true)) {
            $this->{$labelKey} = $labelValue;
        }

        return $this;
    }

    public function getLabel(int $languageIndex): string
    {
        if (!\in_array($languageIndex, range(1, 4), true)) {
            return '';
        }

        return (string)$this->{'label' . $languageIndex};
    }

    public function getScope(): int
    {
        return $this->scope;
    }

    public function setScope(int $scope): self
    {
        $this->scope = $scope;

        return $this;
    }

    public function getScopeName(): string
    {
        return $this->scope === static::SCOPE_LOCAL ? 'local' : 'global';
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

    public function setPosition(int $position): self
    {
        $this->position = $position;

        return $this;
    }

    public function getPosition(): int
    {
        return $this->position;
    }

    public function setMode(int $mode): self
    {
        $this->mode = $mode;

        return $this;
    }

    public function getMode(): int
    {
        return $this->mode;
    }

    public function isModeSpecific(): bool
    {
        return $this->mode === static::MODE_SPECIFIC;
    }

    public function isModeGeneric(): bool
    {
        return $this->mode === static::MODE_GENERIC;
    }

    public function isScopeGlobal(): bool
    {
        return $this->scope === static::SCOPE_GLOBAL;
    }

    public function isEqualTo(ObjectSuperClass $object): bool
    {
        return $this->getUuid() === $object->getUuid();
    }

    public function getInputFilter($partial = false)
    {
        if (!$this->inputFilter) {
            parent::getInputFilter($partial);

            $names = ['name1', 'name2', 'name3', 'name4'];
            foreach ($names as $name) {
                $validatorsName = [];
                if (!$partial) {
                    $validatorsName = array(
                        array(
                            'name' => 'Monarc\Core\Validator\UniqueName',
                            'options' => array(
                                'entity' => $this,
                                'adapter' => $this->getDbAdapter(),
                                'field' => $name
                            ),
                        ),
                    );
                }

                $this->inputFilter->add(array(
                    'name' => $name,
                    'required' => false,
                    'allow_empty' => false,
                    'filters' => array(),
                    //  'validators' => $validatorsName,
                ));
            }

            $labels = ['label1', 'label2', 'label3', 'label4'];
            foreach ($labels as $label) {
                $this->inputFilter->add(array(
                    'name' => $label,
                    'required' => false,
                    'allow_empty' => false,
                    'filters' => array(),
                    'validators' => array(),
                ));
            }

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
                'required' => (!$partial) ? true : false,
                'allow_empty' => true,
                'continue_if_empty' => true,
                'filters' => array(),
                // 'validators' => array(
                //     array(
                //         'name' => 'IsInt',
                //     ),
                // ),
            ));

            // Dans certains cas, la catégorie n'est pas fourni. On n'empêche pas le save mais du coup l'objet n'est pas attaché à une categorie
            $this->inputFilter->add(array(
                'name' => 'category',
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
                'name' => 'rolfTag',
                'required' => false,
                'allow_empty' => true,
                'continue_if_empty' => true,
                'filters' => array(),
                'validators' => array(/*array(
                        'name' => 'IsInt',
                    ),*/
                ),
            ));

            $this->inputFilter->add(array(
                'name' => 'mode',
                'required' => (!$partial) ? true : false,
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

    public function getFiltersForService()
    {
        $filterJoin = [
            [
                'as' => 'a',
                'rel' => 'anrs',
            ],
        ];
        $filterLeft = [

        ];
        $filtersCol = [

        ];

        return [$filterJoin, $filterLeft, $filtersCol];
    }
}
