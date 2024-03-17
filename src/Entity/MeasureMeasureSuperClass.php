<?php declare(strict_types=1);
/**
 * @link      https://github.com/monarc-project for the canonical source repository
 * @copyright Copyright (c) 2016-2022 SMILE GIE Securitymadein.lu - Licensed under GNU Affero GPL v3
 * @license   MONARC is licensed under GNU Affero General Public License version 3
 */

namespace Monarc\Core\Entity;

use Doctrine\ORM\Mapping as ORM;
use Monarc\Core\Entity\Traits\CreateEntityTrait;
use Monarc\Core\Entity\Traits\UpdateEntityTrait;

/**
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

    public function getFather()
    {
        return $this->father;
    }

    public function setFather(MeasureSuperClass $father): self
    {
        $this->father = $father;

        return $this;
    }

    public function getChild()
    {
        return $this->child;
    }

    public function setChild(MeasureSuperClass $child): self
    {
        $this->child = $child;

        return $this;
    }
}
