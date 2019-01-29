<?php
/**
 * @link      https://github.com/monarc-project for the canonical source repository
 * @copyright Copyright (c) 2016-2019  SMILE GIE Securitymadein.lu - Licensed under GNU Affero GPL v3
 * @license   MONARC is licensed under GNU Affero General Public License version 3
 */

namespace MonarcCore\Model\Entity;

use Doctrine\ORM\Mapping as ORM;

/**
* Measure Measure
*
* @ORM\Table(name="measures_measures", indexes={
*      @ORM\Index(name="father_id", columns={"father_id"}),
*      @ORM\Index(name="child_id", columns={"child_id"})
* })
 * @ORM\Entity
 */
class MeasureMeasure extends MeasureMeasureSuperClass
{

      /**
       * @var \MonarcCore\Model\Entity\Measure
       * @ORM\Id
       * @ORM\ManyToOne(targetEntity="MonarcCore\Model\Entity\Measure", cascade={"persist"})
       * @ORM\JoinColumns({
       *   @ORM\JoinColumn(name="father_id", referencedColumnName="uuid", nullable=true)
       * })
       */
      protected $father;

      /**
       * @var \MonarcCore\Model\Entity\Measure
       * @ORM\Id
       * @ORM\ManyToOne(targetEntity="MonarcCore\Model\Entity\Measure", cascade={"persist"})
       * @ORM\JoinColumns({
       *   @ORM\JoinColumn(name="child_id", referencedColumnName="uuid", nullable=true)
       * })
       */
      protected $child;
}
