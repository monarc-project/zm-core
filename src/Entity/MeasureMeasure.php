<?php
/**
 * @link      https://github.com/monarc-project for the canonical source repository
 * @copyright Copyright (c) 2016-2020 SMILE GIE Securitymadein.lu - Licensed under GNU Affero GPL v3
 * @license   MONARC is licensed under GNU Affero General Public License version 3
 */

namespace Monarc\Core\Entity;

use Doctrine\ORM\Mapping as ORM;

/**
* @ORM\Table(name="measures_measures", indexes={
*      @ORM\Index(name="father_id", columns={"father_id"}),
*      @ORM\Index(name="child_id", columns={"child_id"})
* })
 * @ORM\Entity
 */
class MeasureMeasure extends MeasureMeasureSuperClass
{
}
