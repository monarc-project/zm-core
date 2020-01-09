<?php
/**
 * @link      https://github.com/monarc-project for the canonical source repository
 * @copyright Copyright (c) 2016-2020 SMILE GIE Securitymadein.lu - Licensed under GNU Affero GPL v3
 * @license   MONARC is licensed under GNU Affero General Public License version 3
 */

namespace Monarc\Core\Model\Entity;

use Doctrine\ORM\Mapping as ORM;
use Monarc\Core\Model\Entity\Traits\CreateEntityTrait;
use Monarc\Core\Model\Entity\Traits\UpdateEntityTrait;

/**
 * Instance ConsequenceSuperClass
 *
 * @ORM\Table(name="instances_consequences", indexes={
 *      @ORM\Index(name="anr_id", columns={"anr_id"}),
 *      @ORM\Index(name="instance_id", columns={"instance_id"}),
 *      @ORM\Index(name="object_id", columns={"object_id"}),
 *      @ORM\Index(name="scale_impact_type_id", columns={"scale_impact_type_id"})
 * })
 * @ORM\MappedSuperclass
 * @ORM\HasLifecycleCallbacks()
 */
class InstanceConsequenceSuperClass extends AbstractEntity
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
     * @var \Monarc\Core\Model\Entity\Anr
     *
     * @ORM\ManyToOne(targetEntity="Monarc\Core\Model\Entity\Anr", cascade={"persist"})
     * @ORM\JoinColumns({
     *   @ORM\JoinColumn(name="anr_id", referencedColumnName="id", nullable=true, onDelete="CASCADE")
     * })
     */
    protected $anr;

    /**
     * @var \Monarc\Core\Model\Entity\Instance
     *
     * @ORM\ManyToOne(targetEntity="Monarc\Core\Model\Entity\Instance", cascade={"persist"})
     * @ORM\JoinColumns({
     *   @ORM\JoinColumn(name="instance_id", referencedColumnName="id", nullable=true)
     * })
     */
    protected $instance;

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
     * @var \Monarc\Core\Model\Entity\ScaleImpactType
     *
     * @ORM\ManyToOne(targetEntity="Monarc\Core\Model\Entity\ScaleImpactType", cascade={"persist"})
     * @ORM\JoinColumns({
     *   @ORM\JoinColumn(name="scale_impact_type_id", referencedColumnName="id", nullable=true)
     * })
     */
    protected $scaleImpactType;

    /**
     * @var smallint
     *
     * @ORM\Column(name="is_hidden", type="smallint", options={"unsigned":true, "default":0})
     */
    protected $isHidden = '0';

    /**
     * @var smallint
     *
     * @ORM\Column(name="locally_touched", type="smallint", options={"unsigned":true, "default":0})
     */
    protected $locallyTouched = '0';

    /**
     * @var smallint
     *
     * @ORM\Column(name="c", type="smallint", options={"unsigned":true, "default":-1})
     */
    protected $c = -1;

    /**
     * @var smallint
     *
     * @ORM\Column(name="i", type="smallint", options={"unsigned":true, "default":-1})
     */
    protected $i = -1;

    /**
     * @var smallint
     *
     * @ORM\Column(name="d", type="smallint", options={"unsigned":true, "default":-1})
     */
    protected $d = -1;

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
    public function setId($id)
    {
        $this->id = $id;
        return $this;
    }

    /**
     * @return int
     */
    public function getAnr()
    {
        return $this->anr;
    }

    /**
     * @param int $anr
     * @return Instance
     */
    public function setAnr($anr)
    {
        $this->anr = $anr;
        return $this;
    }

    /**
     * @return Instance
     */
    public function getInstance()
    {
        return $this->instance;
    }

    /**
     * @param Instance $instance
     * @return InstanceConsequence
     */
    public function setInstance($instance)
    {
        $this->instance = $instance;
        return $this;
    }

    /**
     * @return Object
     */
    public function getObject()
    {
        return $this->object;
    }

    /**
     * @param Object $object
     * @return InstanceConsequence
     */
    public function setObject($object)
    {
        $this->object = $object;
        return $this;
    }

    /**
     * @return ScaleImpactType
     */
    public function getScaleImpactType()
    {
        return $this->scaleImpactType;
    }

    /**
     * @param ScaleImpactType $scaleImpactType
     * @return InstanceConsequence
     */
    public function setScaleImpactType($scaleImpactType)
    {
        $this->scaleImpactType = $scaleImpactType;
        return $this;
    }

    public function getInputFilter($partial = false)
    {
        if (!$this->inputFilter) {
            parent::getInputFilter($partial);

            $this->inputFilter->add(array(
                'name' => 'anr',
                'required' => true,
                'allow_empty' => false,
                'filters' => array(),
                'validators' => array(),
            ));

            $fields = ['instance', 'object', 'scaleImpactType'];
            foreach ($fields as $field) {
                $this->inputFilter->add(array(
                    'name' => $field,
                    'required' => ($partial) ? false : true,
                    'allow_empty' => false,
                    'filters' => array(),
                    'validators' => array(),
                ));
            }
        }
        return $this->inputFilter;
    }
}
