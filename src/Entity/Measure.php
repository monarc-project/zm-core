<?php declare(strict_types=1);
/**
 * @link      https://github.com/monarc-project for the canonical source repository
 * @copyright Copyright (c) 2016-2024 Luxembourg House of Cybersecurity LHC.lu - Licensed under GNU Affero GPL v3
 * @license   MONARC is licensed under GNU Affero General Public License version 3
 */

namespace Monarc\Core\Entity;

use Doctrine\ORM\Mapping as ORM;

/**
 * @ORM\Table(name="measures", indexes={
 *      @ORM\Index(name="anr", columns={"anr_id"}),
 *      @ORM\Index(name="soacategory", columns={"soacategory_id"}),
 *      @ORM\Index(name="referential", columns={"referential_uuid"})
 * })
 * @ORM\Entity
 */
class Measure extends MeasureSuperClass
{
}
