<?php
/**
 * @link      https://github.com/monarc-project for the canonical source repository
 * @copyright Copyright (c) Cases is a registered trademark of SECURITYMADEIN.LU
 * @license   MyCases is licensed under the GNU Affero GPL v3 - See license.txt for more information
 */

namespace MonarcCore\Model\Entity;

use Doctrine\ORM\Mapping as ORM;

/**
 * Asset
 *
 * @ORM\Table(name="assets", indexes={
 *      @ORM\Index(name="anr_id", columns={"anr_id","code"}),
 *      @ORM\Index(name="anr_id2", columns={"anr_id"})
 * })
 * @ORM\MappedSuperclass
 */
class AssetSuperClass extends AbstractEntity
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
     * @var \MonarcCore\Model\Entity\Translation
     *
     * @ORM\ManyToMany(targetEntity="\MonarcCore\Model\Entity\Translation")
     * @ORM\Column(name="label_translation_id")
     * @ORM\JoinTable(name="translation_language",
     *     joinColumns={@ORM\JoinColumn(name="assets_string_id", referencedColumnName="label_translation_id")},
     *     inverseJoinColumns={@ORM\JoinColumn(name="translation_id", referencedColumnName="id")})
     *
     */
    protected $label;

    /**
     * @var \MonarcCore\Model\Entity\Translation
     *
     * @ORM\ManyToMany(targetEntity="\MonarcCore\Model\Entity\Translation")
     * @ORM\Column(name="description_translation_id")
     * @ORM\JoinTable(name="translation_language",
     *     joinColumns={@ORM\JoinColumn(name="assets_string_id", referencedColumnName="description_translation_id")},
     *     inverseJoinColumns={@ORM\JoinColumn(name="translation_id", referencedColumnName="id")})
     *
     */
    protected $description;

    /**
     * @var smallint
     *
     * @ORM\Column(name="status", type="smallint", options={"unsigned":true, "default":1})
     */
    protected $status = 1;

    /**
     * @var smallint
     *
     * @ORM\Column(name="mode", type="smallint", options={"unsigned":true, "default":1})
     */
    protected $mode = '1';

    /**
     * @var smallint
     *
     * @ORM\Column(name="type", type="smallint", options={"unsigned":true, "default":1})
     */
    protected $type = '1';

    /**
     * @var string
     *
     * @ORM\Column(name="code", type="string", length=255, nullable=true)
     */
    protected $code;

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
     * @return Asset
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
     * @return Asset
     */
    public function setAnr($anr)
    {
        $this->anr = $anr;
        return $this;
    }

    public function getInputFilter($partial = true)
    {
        if (!$this->inputFilter) {
            parent::getInputFilter($partial);

            $texts = ['label'];

            foreach ($texts as $text) {
                $this->inputFilter->add(array(
                    'name' => $text,
                    'required' => ((strchr($text, (string)$this->getLanguage())) && (!$partial)) ? true : false,
                    'allow_empty' => false,
                    'filters' => array(),
                    'validators' => array(),
                ));
            }

            $descriptions = ['description'];

            foreach ($descriptions as $description) {
                $this->inputFilter->add(array(
                    'name' => $description,
                    'required' => false,
                    'allow_empty' => true,
                    'filters' => array(),
                    'validators' => array(),
                ));
            }
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

        $this->inputFilter->add(array(
            'name' => 'type',
            'required' => false,
            'allow_empty' => false,
            'filters' => array(
                array('name' => 'ToInt'),
            ),
            'validators' => array(
                array(
                    'name' => 'InArray',
                    'options' => array(
                        'haystack' => array(self::TYPE_PRIMARY, self::TYPE_SECONDARY),
                    ),
                ),
            ),
        ));

        return $this->inputFilter;
    }
}
