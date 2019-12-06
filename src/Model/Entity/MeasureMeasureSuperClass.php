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
 * Measure Measure
 *
 * @ORM\Table(name="measures_measures", indexes={
 *      @ORM\Index(name="father_id", columns={"father_id"}),
 *      @ORM\Index(name="child_id", columns={"child_id"})
 * })
 * @ORM\MappedSuperclass
 * @ORM\HasLifecycleCallbacks()
 */
class MeasureMeasureSuperClass extends AbstractEntity
{
    use CreateEntityTrait;
    use UpdateEntityTrait;

    /**
     * @var MeasureSuperClass
     *
     * @ORM\Id
     * @ORM\ManyToOne(targetEntity="Measure", cascade={"persist"})
     * @ORM\JoinColumns({
     *   @ORM\JoinColumn(name="father_id", referencedColumnName="uuid", nullable=true)
     * })
     */
    protected $father;

    /**
     * @var MeasureSuperClass
     *
     * @ORM\Id
     * @ORM\ManyToOne(targetEntity="Measure", cascade={"persist"})
     * @ORM\JoinColumns({
     *   @ORM\JoinColumn(name="child_id", referencedColumnName="uuid", nullable=true)
     * })
     */
    protected $child;

    /**
     * TODO: The supporting of the Uuid|string types is added for FrontOffice and has to be refactored in the future.
     *
     * @return MeasureSuperClass|Uuid|string
     */
    public function getFather()
    {
        return $this->father;
    }

    /**
     * @param MeasureSuperClass|Uuid|string $father
     *
     * @return MeasureMeasureSuperClass
     */
    public function setFather($father): self
    {
        $this->father = $father;

        return $this;
    }

    /**
     * @return MeasureSuperClass|Uuid|string
     */
    public function getChild()
    {
        return $this->child;
    }

    /**
     * @param MeasureSuperClass|Uuid|string $child
     *
     * @return MeasureMeasureSuperClass
     */
    public function setChild($child): self
    {
        $this->child = $child;

        return $this;
    }
}
