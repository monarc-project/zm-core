<?php
/**
 * @link      https://github.com/monarc-project for the canonical source repository
 * @copyright Copyright (c) 2016-2020 SMILE GIE Securitymadein.lu - Licensed under GNU Affero GPL v3
 * @license   MONARC is licensed under GNU Affero General Public License version 3
 */

namespace Monarc\Core\Model\Entity;

use Doctrine\ORM\Mapping as ORM;

/**
 * @ORM\Table(name="operational_risks_scales_comments", indexes={
 *      @ORM\Index(name="scale_id", columns={"scale_id"})
 * })
 * @ORM\Entity
 */
class OperationalRiskScaleComment extends OperationalRiskScaleCommentSuperClass
{
}
