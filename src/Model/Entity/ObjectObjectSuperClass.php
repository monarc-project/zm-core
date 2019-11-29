<?php
/**
 * @link      https://github.com/monarc-project for the canonical source repository
 * @copyright Copyright (c) 2016-2019  SMILE GIE Securitymadein.lu - Licensed under GNU Affero GPL v3
 * @license   MONARC is licensed under GNU Affero General Public License version 3
 */

namespace Monarc\Core\Model\Entity;

use Doctrine\ORM\Mapping as ORM;
use Monarc\Core\Model\Entity\Traits\CreateEntityTrait;
use Monarc\Core\Model\Entity\Traits\UpdateEntityTrait;

/**
 * Object Object
 *
 * @ORM\Table(name="objects_objects", indexes={
 *      @ORM\Index(name="anr", columns={"anr_id"}),
 *      @ORM\Index(name="father_id", columns={"father_id"}),
 *      @ORM\Index(name="child_id", columns={"child_id"})
 * })
 * @ORM\MappedSuperclass
 * @ORM\HasLifecycleCallbacks()
 */
class ObjectObjectSuperClass extends AbstractEntity
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
     *   @ORM\JoinColumn(name="anr_id", referencedColumnName="id", nullable=true)
     * })
     */
    protected $anr;

    /**
     * @var \Monarc\Core\Model\Entity\MonarcObject
     *
     * @ORM\ManyToOne(targetEntity="Monarc\Core\Model\Entity\MonarcObject", cascade={"persist"})
     * @ORM\JoinColumns({
     *   @ORM\JoinColumn(name="father_id", referencedColumnName="uuid", nullable=true)
     * })
     */
    protected $father;

    /**
     * @var \Monarc\Core\Model\Entity\MonarcObject
     *
     * @ORM\ManyToOne(targetEntity="Monarc\Core\Model\Entity\MonarcObject", cascade={"persist"})
     * @ORM\JoinColumns({
     *   @ORM\JoinColumn(name="child_id", referencedColumnName="uuid", nullable=true)
     * })
     */
    protected $child;

    /**
     * @var int
     *
     * @ORM\Column(name="position", type="smallint", options={"unsigned":true, "default":1})
     */
    protected $position = 1;

    /**
     * @return int
     */
    public function getId()
    {
        return $this->id;
    }

    /**
     * @param int $id
     * @return Model
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
     * @return ObjectObject
     */
    public function setAnr($anr)
    {
        $this->anr = $anr;
        return $this;
    }

    /**
     * @return Object
     */
    public function getFather()
    {
        return $this->father;
    }

    /**
     * @param Object $father
     * @return ObjectObject
     */
    public function setFather($father)
    {
        $this->father = $father;
        return $this;
    }

    /**
     * @return Object
     */
    public function getChild()
    {
        return $this->child;
    }

    /**
     * @param Object $child
     * @return ObjectObject
     */
    public function setChild($child)
    {
        $this->child = $child;
        return $this;
    }

    public function getPosition()
    {
        return $this->position;
    }

    /**
     * @param int $position
     */
    public function setPosition($position)
    {
        $this->position = $position;
        return $this;
    }

    protected $parameters = array(
        'implicitPosition' => array(
            'field' => 'father',
        ),
    );

    public function getFiltersForService(){
        $filterJoin = [
            [
                'as' => 'f',
                'rel' => 'father',
            ],
        ];
        $filterLeft = [

        ];
        $filtersCol = [

        ];
        return [$filterJoin,$filterLeft,$filtersCol];
    }
}
