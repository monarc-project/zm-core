<?php

namespace MonarcCore\Model\Entity;

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
     * @var integer
     *
     * @ORM\Column(name="anr_id", type="integer", nullable=true)
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
     * @ORM\Column(name="status", type="boolean", nullable=true)
     */
    protected $status = '1';

    /**
     * @var boolean
     *
     * @ORM\Column(name="is_scales_updatable", type="boolean", nullable=true)
     */
    protected $isScalesUpdatable = '1';

    /**
     * @var boolean
     *
     * @ORM\Column(name="is_default", type="boolean", nullable=true)
     */
    protected $isDefault = '0';

    /**
     * @var boolean
     *
     * @ORM\Column(name="is_deleted", type="boolean", nullable=true)
     */
    protected $isDeleted = '0';

    /**
     * @var boolean
     *
     * @ORM\Column(name="is_generic", type="boolean", nullable=true)
     */
    protected $isGeneric = '1';

    /**
     * @var boolean
     *
     * @ORM\Column(name="is_regulator", type="boolean", nullable=true)
     */
    protected $isRegulator = '0';

    /**
     * @var boolean
     *
     * @ORM\Column(name="show_rolf_brut", type="boolean", nullable=true)
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


    public function getInputFilter(){
        if (!$this->inputFilter) {
            parent::getInputFilter();
            $this->inputFilter->add(array(
                'name' => 'label1',
                'required' => true,
                'allow_empty' => true,
                'filters' => array(
                    array('name' => 'Alpha'),
                ),
                'validators' => array(),
            ));
            $this->inputFilter->add(array(
                'name' => 'label2',
                'required' => true,
                'allow_empty' => true,
                'filters' => array(
                    array('name' => 'Alpha'),
                ),
                'validators' => array(),
            ));
            $this->inputFilter->add(array(
                'name' => 'label3',
                'required' => true,
                'allow_empty' => true,
                'filters' => array(
                    array('name' => 'Alpha'),
                ),
                'validators' => array(),
            ));
            $this->inputFilter->add(array(
                'name' => 'label4',
                'required' => true,
                'allow_empty' => true,
                'filters' => array(
                    array('name' => 'Alpha'),
                ),
                'validators' => array(),
            ));
            $this->inputFilter->add(array(
                'name' => 'description1',
                'required' => true,
                'allow_empty' => true,
                'filters' => array(
                    array('name' => 'Alpha'),
                ),
                'validators' => array(),
            ));
            $this->inputFilter->add(array(
                'name' => 'description2',
                'required' => true,
                'allow_empty' => true,
                'filters' => array(
                    array('name' => 'Alpha'),
                ),
                'validators' => array(),
            ));
            $this->inputFilter->add(array(
                'name' => 'description3',
                'required' => true,
                'allow_empty' => true,
                'filters' => array(
                    array('name' => 'Alpha'),
                ),
                'validators' => array(),
            ));
            $this->inputFilter->add(array(
                'required' => true,
                'allow_empty' => true,
                'filters' => array(
                    array('name' => 'Alpha'),
                ),
                'validators' => array(),
            ));
        }
        return $this->inputFilter;
    }
}

