<?php declare(strict_types=1);
/**
 * @link      https://github.com/monarc-project for the canonical source repository
 * @copyright Copyright (c) 2016-2024 Luxembourg House of Cybersecurity LHC.lu - Licensed under GNU Affero GPL v3
 * @license   MONARC is licensed under GNU Affero General Public License version 3
 */

namespace Monarc\Core\Entity;

use Doctrine\ORM\Mapping as ORM;
use Monarc\Core\Entity\Traits\CreateEntityTrait;

/**
 * @ORM\Table(name="measures_measures", indexes={
 *      @ORM\Index(name="master_measure_id", columns={"master_measure_id"}),
 *      @ORM\Index(name="linked_measure_id", columns={"linked_measure_id"})
 * })
 * @ORM\MappedSuperclass
 * @ORM\HasLifecycleCallbacks()
 */
class MeasureMeasureSuperClass
{
    /**
     * @var int
     *
     * @ORM\Id
     * @ORM\Column(name="id", type="integer", nullable=false)
     * @ORM\GeneratedValue(strategy="IDENTITY")
     */
    protected $id;

    /**
     * @var MeasureSuperClass
     *
     * @ORM\ManyToOne(targetEntity="Measure", cascade={"persist"})
     * @ORM\JoinColumns({
     *   @ORM\JoinColumn(name="master_measure_id", referencedColumnName="uuid", nullable=true)
     * })
     */
    protected $masterMeasure;

    /**
     * @var MeasureSuperClass
     *
     * @ORM\ManyToOne(targetEntity="Measure", cascade={"persist"})
     * @ORM\JoinColumns({
     *   @ORM\JoinColumn(name="linked_measure_id", referencedColumnName="uuid", nullable=true)
     * })
     */
    protected $linkedMeasure;

    public function getId()
    {
        return $this->id;
    }

    public function getMasterMeasure()
    {
        return $this->masterMeasure;
    }

    public function setMasterMeasure(MeasureSuperClass $masterMeasure): self
    {
        $this->masterMeasure = $masterMeasure;

        return $this;
    }

    public function getLinkedMeasure()
    {
        return $this->linkedMeasure;
    }

    public function setLinkedMeasure(MeasureSuperClass $linkedMeasure): self
    {
        $this->linkedMeasure = $linkedMeasure;

        return $this;
    }
}
