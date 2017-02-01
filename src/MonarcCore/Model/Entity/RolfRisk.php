<?php
/**
 * @link      https://github.com/CASES-LU for the canonical source repository
 * @copyright Copyright (c) Cases is a registered trademark of SECURITYMADEIN.LU
 * @license   MyCases is licensed under the GNU Affero GPL v3 - See license.txt for more information
 */

namespace MonarcCore\Model\Entity;

use Doctrine\ORM\Mapping as ORM;

/**
 * RolfRisk
 *
 * @ORM\Table(name="rolf_risks", indexes={
 *      @ORM\Index(name="anr", columns={"anr_id"})
 * })
 * @ORM\Entity
 */
class RolfRisk extends RolfRiskSuperclass
{
}