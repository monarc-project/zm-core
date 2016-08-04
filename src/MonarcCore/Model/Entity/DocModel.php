<?php

namespace MonarcCore\Model\Entity;

use Doctrine\ORM\Mapping as ORM;

/**
 * Amv
 *
 * @ORM\Table(name="doc_models")
 * @ORM\Entity
 */
class DocModel extends AbstractEntity
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
     * @ORM\Column(name="description", type="text", nullable=true)
     */
    protected $description;

    /**
     * @var string
     *
     * @ORM\Column(name="path", type="text", length=255, nullable=true)
     */
    protected $path;

    /**
     * @var resource
     *
     * @ORM\Column(name="content", type="blob", nullable=true)
     */
    protected $content;

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

    const MODEL_CONTEXT_VALIDATION = 1; // Document model for Context validation
    const MODEL_ASSETS_AND_MODELS_VALIDATION = 2; // Document model for Assets and models validation
    const MODEL_RISK_ANALYSIS = 3; // Document model for Risk analysis

    public function getInputFilter($partial = false){
        if (!$this->inputFilter) {
            $dirFile = './data/monarc/models/';
            if(!is_dir($dirFile)){
                mkdir($dirFile,0775,true);
            }

            parent::getInputFilter($partial);

            $this->inputFilter->add(array(
                'name' => 'description',
                'required' => false,
                'allow_empty' => true,
                'filters' => array(
                    array(
                        'name' => 'StringTrim',
                    ),
                ),
                'validators' => array(),
            ));
            $this->inputFilter->add(array(
                'name' => 'path',
                'required' => !$this->get('id'),
                'allow_empty' => false,
                'filters' => array(
                    array(
                        'name' => 'Zend\Filter\File\RenameUpload',
                        'options' => array(
                            'randomize' => true,
                            'target' => $dirFile.$this->path['name'],
                        ),
                    ),
                ),
                'validators' => array(),
            ));
            $this->inputFilter->add(array(
                'name' => 'category',
                'required' => !($this->get('id')>0),
                'allow_empty' => false,
                'filters' => array(
                    array('name' => 'ToInt',),
                ),
                'validators' => array(
                    array(
                        'name' => 'InArray',
                        'options' => array(
                            'haystack' => array(self::MODEL_CONTEXT_VALIDATION,self::MODEL_ASSETS_AND_MODELS_VALIDATION,self::MODEL_RISK_ANALYSIS),
                        ),
                    ),
                    array(
                        'name' => '\MonarcCore\Validator\UniqueDocModel',
                        'options' => array(
                            'adapter' => $this->getDbAdapter(),
                            'category' => $this->get('category'),
                            'id' => $this->get('id'),

                        ),
                    ),
                ),
            ));
        }
        return $this->inputFilter;
    }

    public function exchangeArray(array $options, $partial = false)
    {
        parent::exchangeArray($options);
        if(!empty($this->path['tmp_name']) && file_exists($this->path['tmp_name'])){
            $info = pathinfo($this->path['tmp_name']);
            $targetFile = $info['dirname'] . DIRECTORY_SEPARATOR.uniqid().'_'.$this->path['name'];
            rename($this->path['tmp_name'], $targetFile);

            $this->path = $targetFile;
            $this->content = file_get_contents($this->path);
        }
        return $this;
    }

    public function getJsonArray($fields = array())
    {
        $res = parent::getJsonArray($fields);
        unset($res['content']);
        return $res;
    }
}

