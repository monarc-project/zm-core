<?php
/**
 * @link      https://github.com/monarc-project for the canonical source repository
 * @copyright Copyright (c) 2016-2020 SMILE GIE Securitymadein.lu - Licensed under GNU Affero GPL v3
 * @license   MONARC is licensed under GNU Affero General Public License version 3
 */

namespace Monarc\Core\Entity;

use Doctrine\ORM\Mapping as ORM;
use Monarc\Core\Entity\Traits\CreateEntityTrait;
use Monarc\Core\Entity\Traits\DescriptionsEntityTrait;
use Monarc\Core\Entity\Traits\UpdateEntityTrait;
use Monarc\Core\Validator\FieldValidator\UniqueDeliveryModel;

/**
 * @ORM\Table(name="deliveries_models", indexes={
 *      @ORM\Index(name="anr_id", columns={"anr_id"})
 * })
 * @ORM\Entity
 * @ORM\HasLifecycleCallbacks()
 */
class DeliveriesModels extends AbstractEntity
{
    use CreateEntityTrait;
    use UpdateEntityTrait;

    use DescriptionsEntityTrait;

    public const MODEL_CONTEXT_VALIDATION = 1; // Document model for Context validation
    public const MODEL_ASSETS_AND_MODELS_VALIDATION = 2; // Document model for Assets and models validation
    public const MODEL_RISK_ANALYSIS = 3; // Document model for Risk analysis
    public const MODEL_IMPLEMENTATION_PLAN = 4; // Document model for implementation plan
    public const MODEL_STATEMENT_OF_APPLICABILITY = 5; // Document model for Statement of applicability
    public const MODEL_RECORD_OF_PROCESSING_ACTIVITIES = 6; // Document model for Record of processing activities
    public const MODEL_ALL_RECORD_OF_PROCESSING_ACTIVITIES = 7; // Document model for all Rec. of processing activities

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
     * @ORM\Column(name="category", type="smallint", length=4, options={"default":0})
     */
    protected $category;

    /**
     * @var string
     *
     * @ORM\Column(name="path1", type="string", length=255, nullable=true)
     */
    protected $path1;

    /**
     * @var string
     *
     * @ORM\Column(name="path2", type="string", length=255, nullable=true)
     */
    protected $path2;

    /**
     * @var string
     *
     * @ORM\Column(name="path3", type="string", length=255, nullable=true)
     */
    protected $path3;

    /**
     * @var string
     *
     * @ORM\Column(name="path4", type="string", length=255, nullable=true)
     */
    protected $path4;

    /**
     * @var boolean
     *
     * @ORM\Column(name="editable", type="boolean", options={"default":true})
     */
    protected $editable;

    /**
     * @var AnrSuperClass
     *
     * @ORM\ManyToOne(targetEntity="Anr")
     * @ORM\JoinColumns({
     *   @ORM\JoinColumn(name="anr_id", referencedColumnName="id", nullable=true)
     * })
     */
    protected $anr;

    public function getPath(int $languageIndex): string
    {
        if (!\in_array($languageIndex, range(1, 4), true)) {
            return '';
        }

        return (string)$this->{'path' . $languageIndex};
    }

    public function setPath(array $paths): self
    {
        foreach (range(1, 4) as $index) {
            $key = 'path' . $index;
            if (isset($paths[$key])) {
                $this->{$key} = $paths[$key];
            }
        }

        return $this;
    }

    public function getPaths(): array
    {
        return [
            'path1' => $this->path1,
            'path2' => $this->path2,
            'path3' => $this->path3,
            'path4' => $this->path4,
        ];
    }

    public function getInputFilter($partial = false)
    {
        if ((!$partial)==1) {
            $partial = true;
        } else {
            $partial = false;
        }
        if (!$this->inputFilter) {
            $dirFile = './data/';
            $appconfdir = getenv('APP_CONF_DIR') ?: '';
            if( ! empty($appconfdir) ){
                $dirFile = $appconfdir . '/data/';
            }
            $dirFile .= 'monarc/models/';
            if (!is_dir($dirFile)) {
                mkdir($dirFile, 0775, true);
            }

            parent::getInputFilter($partial);

            $this->inputFilter->add(array(
                'name' => 'category',
                'required' => !($this->get('id') > 0),
                'allow_empty' => false,
                'filters' => array(
                    array('name' => 'ToInt',),
                ),
                'validators' => array(
                    array(
                        'name' => 'InArray',
                        'options' => array(
                            'haystack' => array(
                              self::MODEL_CONTEXT_VALIDATION,
                              self::MODEL_ASSETS_AND_MODELS_VALIDATION,
                              self::MODEL_RISK_ANALYSIS,
                              self::MODEL_IMPLEMENTATION_PLAN,
                              self::MODEL_STATEMENT_OF_APPLICABILITY,
                              self::MODEL_RECORD_OF_PROCESSING_ACTIVITIES,
                              self::MODEL_ALL_RECORD_OF_PROCESSING_ACTIVITIES
                            ),
                        ),
                    ),
                    array(
                        // TODO: move the validator in the creation part, as it requires a db connection.
                        'name' => UniqueDeliveryModel::class,
                        'options' => array(
                            'adapter' => $this->getDbAdapter(),
                            'category' => $this->get('category'),
                            'id' => $this->get('id'),

                        ),
                    ),
                ),
            ));

            for ($i = 1; $i <= 4; $i++) {
                $this->inputFilter->add(array(
                    'name' => 'path' . $i,
                    'required' => ($this->getLanguage() == $i && !$partial && !$this->get('id')),
                    'allow_empty' => false,
                    'filters' => array(
                        array(
                            'name' => 'Laminas\Filter\File\RenameUpload',
                            'options' => array(
                                'randomize' => true,
                                'target' => $dirFile . $this->{"path$i"},
                            ),
                        ),
                    ),
                    'validators' => array(),
                ));

                $this->inputFilter->add(array(
                    'name' => "description$i",
                    'required' => ($this->getLanguage() == $i && !$partial),
                    'allow_empty' => false,
                    'filters' => array(),
                    'validators' => array(),
                ));
            }
        }
        return $this->inputFilter;
    }

    public function exchangeArray(array $options, $partial = false)
    {
        parent::exchangeArray($options);

        $languages = array(
            1 => 'FR',
            2 => 'EN',
            3 => 'DE',
            4 => 'NE',
        );

        $dirFile = './data';
        $appconfdir = getenv('APP_CONF_DIR') ? getenv('APP_CONF_DIR') : '';
        if( ! empty($appconfdir) ){
            $dirFile = $appconfdir . DIRECTORY_SEPARATOR . 'data';
        }
        $dirFile .= DIRECTORY_SEPARATOR . 'monarc/models';
        if (!is_dir($dirFile)) {
            mkdir($dirFile, 0775, true);
        }

        for ($i = 1; $i <= 4; ++$i) {
            if (!empty($this->{'path' . $i}['tmp_name']) && file_exists($this->{'path' . $i}['tmp_name'])) {
                $info = pathinfo($this->{'path' . $i}['tmp_name']);
                $dirFileAbsolute =  $dirFile . DIRECTORY_SEPARATOR . $languages[$i];
                $dirFileRelative = './data/monarc/models' . DIRECTORY_SEPARATOR . $languages[$i];
                if (!is_dir($dirFileAbsolute)) {
                    mkdir($dirFileAbsolute, 0775, true);
                }
                $newFileName = uniqid() . '_' . $this->{'path' . $i}['name'];
                $newFileRelative = $dirFileRelative . DIRECTORY_SEPARATOR . $newFileName;
                $targetFile = $dirFileAbsolute . DIRECTORY_SEPARATOR . $newFileName;
                rename($this->{'path' . $i}['tmp_name'], $targetFile);

                $this->{'path' . $i} = $newFileRelative;
            }
        }

        return $this;
    }
}
