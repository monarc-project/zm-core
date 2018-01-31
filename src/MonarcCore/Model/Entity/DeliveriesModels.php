<?php
/**
 * @link      https://github.com/monarc-project for the canonical source repository
 * @copyright Copyright (c) 2018 SMILE GIE Securitymadein.lu - Licensed under GNU Affero GPL v3
 * @license   MONARC is licensed under GNU Affero General Public License version 3
 */

namespace MonarcCore\Model\Entity;

use Doctrine\ORM\Mapping as ORM;

/**
 * Amv
 *
 * @ORM\Table(name="deliveries_models", indexes={
 *      @ORM\Index(name="anr_id", columns={"anr_id"})
 * })
 * @ORM\Entity
 */
class DeliveriesModels extends AbstractEntity
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
     * @ORM\Column(name="category", type="smallint", length=4, options={"default":0})
     */
    protected $category;

    /**
     * @var string
     *
     * @ORM\Column(name="description1", type="text", nullable=true)
     */
    protected $description1;

    /**
     * @var string
     *
     * @ORM\Column(name="description2", type="text", nullable=true)
     */
    protected $description2;

    /**
     * @var string
     *
     * @ORM\Column(name="description3", type="text", nullable=true)
     */
    protected $description3;

    /**
     * @var string
     *
     * @ORM\Column(name="description4", type="text", nullable=true)
     */
    protected $description4;

    /**
     * @var string
     *
     * @ORM\Column(name="path1", type="text", length=255, nullable=true)
     */
    protected $path1;

    /**
     * @var resource
     *
     * @ORM\Column(name="content1", type="blob", nullable=true)
     */
    protected $content1;

    /**
     * @var string
     *
     * @ORM\Column(name="path2", type="text", length=255, nullable=true)
     */
    protected $path2;

    /**
     * @var resource
     *
     * @ORM\Column(name="content2", type="blob", nullable=true)
     */
    protected $content2;

    /**
     * @var string
     *
     * @ORM\Column(name="path3", type="text", length=255, nullable=true)
     */
    protected $path3;

    /**
     * @var resource
     *
     * @ORM\Column(name="content3", type="blob", nullable=true)
     */
    protected $content3;

    /**
     * @var string
     *
     * @ORM\Column(name="path4", type="text", length=255, nullable=true)
     */
    protected $path4;

    /**
     * @var resource
     *
     * @ORM\Column(name="content4", type="blob", nullable=true)
     */
    protected $content4;

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
     * @var \MonarcCore\Model\Entity\Anr
     *
     * @ORM\ManyToOne(targetEntity="MonarcCore\Model\Entity\Anr", cascade={"persist"})
     * @ORM\JoinColumns({
     *   @ORM\JoinColumn(name="anr_id", referencedColumnName="id", nullable=true)
     * })
     */
    protected $anr;

    const MODEL_CONTEXT_VALIDATION = 1; // Document model for Context validation
    const MODEL_ASSETS_AND_MODELS_VALIDATION = 2; // Document model for Assets and models validation
    const MODEL_RISK_ANALYSIS = 3; // Document model for Risk analysis
    const MODEL_IMPLEMENTATION_PLAN = 4; // Document model for implementation plan

    public function getInputFilter($partial = false)
    {
        if (!$this->inputFilter) {
            $dirFile = './data/';
            $appconfdir = getenv('APP_CONF_DIR') ? getenv('APP_CONF_DIR') : '';
            if( ! empty($appconfdir) ){
                $dirFile = $appconfdir.'/data/';
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
                            'haystack' => array(self::MODEL_CONTEXT_VALIDATION, self::MODEL_ASSETS_AND_MODELS_VALIDATION, self::MODEL_RISK_ANALYSIS, self::MODEL_IMPLEMENTATION_PLAN),
                        ),
                    ),
                    array(
                        'name' => '\MonarcCore\Validator\UniqueDeliveryModel',
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
                            'name' => 'Zend\Filter\File\RenameUpload',
                            'options' => array(
                                'randomize' => true,
                                'target' => $dirFile . $this->{"path$i"}['name'],
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

        for ($i = 1; $i <= 4; ++$i) {
            if (!empty($this->{'path' . $i}['tmp_name']) && file_exists($this->{'path' . $i}['tmp_name'])) {
                $info = pathinfo($this->{'path' . $i}['tmp_name']);
                $dirFile = $info['dirname'] . DIRECTORY_SEPARATOR . $languages[$i];
                if (!is_dir($dirFile)) {
                    mkdir($dirFile, 0775, true);
                }
                $targetFile = $dirFile . DIRECTORY_SEPARATOR .$this->{'path' . $i}['name'];
                rename($this->{'path' . $i}['tmp_name'], $targetFile);

                $this->{'path' . $i} = $targetFile;
                $this->{"content$i"} = file_get_contents($this->{'path' . $i});
            }
        }

        return $this;
    }

    public function getJsonArray($fields = array())
    {
        $res = parent::getJsonArray($fields);
        for ($i = 1; $i <= 4; ++$i) {
            unset($res['content' . $i]);
        }
        return $res;
    }
}
