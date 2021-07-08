<?php declare(strict_types=1);
/**
 * @link      https://github.com/monarc-project for the canonical source repository
 * @copyright Copyright (c) 2016-2020 SMILE GIE Securitymadein.lu - Licensed under GNU Affero GPL v3
 * @license   MONARC is licensed under GNU Affero General Public License version 3
 */

namespace Monarc\Core\Model\Entity;

use Doctrine\ORM\Mapping as ORM;

/**
 * @ORM\Table(name="instance_risk_owners",
 * uniqueConstraints={@ORM\UniqueConstraint(name="instance_risk_owners_anr_id_name", columns={"anr_id", "name"})},
 * indexes={
 *   @ORM\Index(name="anr", columns={"anr_id"})
 * })
 * @ORM\Entity
 */
class InstanceRiskOwner extends InstanceRiskOwnerSuperClass
{
}
