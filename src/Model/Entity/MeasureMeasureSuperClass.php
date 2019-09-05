<?php
/**
 * @link      https://github.com/monarc-project for the canonical source repository
 * @copyright Copyright (c) 2016-2019  SMILE GIE Securitymadein.lu - Licensed under GNU Affero GPL v3
 * @license   MONARC is licensed under GNU Affero General Public License version 3
 */

namespace Monarc\Core\Model\Entity;

use Doctrine\ORM\Mapping as ORM;
use Ramsey\Uuid\UuidInterface;

/**
 * Measure Measure
 *
 * @ORM\Table(name="measures_measures", indexes={
 *      @ORM\Index(name="father_id", columns={"father_id"}),
 *      @ORM\Index(name="child_id", columns={"child_id"})
 * })
 * @ORM\MappedSuperclass
 */
class MeasureMeasureSuperClass extends AbstractEntity
{
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
     * @return Model
     */
    public function setId($id)
    {
        $this->id = $id;
        return $this;
    }

    /**
     * @return Measure
     */
    public function getFather()
    {
        return $this->father;
    }

    /**
     * @param Measure $father
     * @return MeasureMeasure
     */
    public function setFather($father)
    {
        $this->father = $father;
        return $this;
    }

    /**
     * @return Measure
     */
    public function getChild()
    {
        return $this->child;
    }

    /**
     * @param Measure $child
     * @return MeasureMeasure
     */
    public function setChild($child)
    {
        $this->child = $child;
        return $this;
    }

}