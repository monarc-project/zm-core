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

    // Must be 16, 24 or 32 characters
    const SALT = '__$$00_C4535_5M1L3_00$$__XMP0)XW';

    const SCOPE_LOCAL = 1;
    const SCOPE_GLOBAL = 2;

    /**
     * @var Uuid
     *
     * @ORM\Column(name="uuid", type="uuid", nullable=false)
     * @ORM\Id
     */
    protected $uuid;

    /**
     * @var \Monarc\Core\Model\Entity\Anr
     *
     * @ORM\ManyToOne(targetEntity="Monarc\Core\Model\Entity\Anr", cascade={"persist"})
     * @ORM\JoinColumns({
     *   @ORM\JoinColumn(name="anr_id", referencedColumnName="id", nullable=true)
     * })
     */
    protected $anr;

    /**
     * @var \Doctrine\Common\Collections\Collection
     * @ORM\ManyToMany(targetEntity="Monarc\Core\Model\Entity\Anr", inversedBy="objects", cascade={"persist"})
     * @ORM\JoinTable(name="anrs_objects",
     *  joinColumns={@ORM\JoinColumn(name="object_id", referencedColumnName="uuid")},
     *  inverseJoinColumns={@ORM\JoinColumn(name="anr_id", referencedColumnName="id")}
     * )
     */
    protected $anrs;

    /**
     * @var \Monarc\Core\Model\Entity\ObjectCategory
     *
     * @ORM\ManyToOne(targetEntity="Monarc\Core\Model\Entity\ObjectCategory", cascade={"persist"})
     * @ORM\JoinColumns({
     *   @ORM\JoinColumn(name="object_category_id", referencedColumnName="id", nullable=true)
     * })
     */
    protected $category;

    /**
     * @var \Monarc\Core\Model\Entity\Asset
     *
     * @ORM\ManyToOne(targetEntity="Monarc\Core\Model\Entity\Asset", cascade={"persist"})
     * @ORM\JoinColumns({
     *   @ORM\JoinColumn(name="asset_id", referencedColumnName="uuid", nullable=true, onDelete="SET NULL")
     * })
     */
    protected $asset;

    /**
     * @var \Monarc\Core\Model\Entity\RolfTag
     *
     * @ORM\ManyToOne(targetEntity="Monarc\Core\Model\Entity\RolfTag", cascade={"persist"})
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
    protected $disponibility = '0';

    /**
     * @var int
     *
     * @ORM\Column(name="position", type="smallint", options={"unsigned":true, "default":0})
     */
    protected $position = '0';

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
     * @return Uuid
     */
    public function getUuid()
    {
        return $this->uuid;
    }

    /**
     * @param Uuid $id
     * @return Object
     */
    public function setUuid($id)
    {
        $this->uuid = $id;
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
     * @return Asset
     */
    public function setAnr($anr)
    {
        $this->anr = $anr;
        return $this;
    }

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
     * @return Anr
     */
    public function getAnrs()
    {
        return $this->anrs;
    }

    /**
     * @param Anr $anrs
     * @return Object
     */
    public function setAnrs($anrs)
    {
        $this->anrs = $anrs;
        return $this;
    }

    /**
     * Add Anr
     *
     * @param Anr $anr
     * @throws \Exception
     */
    public function addAnr(AnrSuperClass $anr)
    {
        $currentAnrs = $this->anrs;

        $errors = false;
        if ($currentAnrs) {
            foreach ($currentAnrs as $currentAnr) {
                if ($currentAnr->id == $anr->id) {
                    $errors = true;
                }
            }
        }

        if (!$errors) {
            $this->anrs[] = $anr;
        }
    }

    protected $parameters = array(
        'implicitPosition' => array(
            'field' => 'category',
        ),
    );

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

    public function __construct($obj = null)
    {
        $this->models = new ArrayCollection();
        parent::__construct($obj);
    }

    public function getFiltersForService(){
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
        return [$filterJoin,$filterLeft,$filtersCol];
    }
}
