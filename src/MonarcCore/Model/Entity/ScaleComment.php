<?php
/**
 * @link      https://github.com/monarc-project for the canonical source repository
 * @copyright Copyright (c) 2018 SMILE GIE Securitymadein.lu - Licensed under GNU Affero GPL v3
 * @license   MONARC is licensed under GNU Affero General Public License version 3
 */

namespace MonarcCore\Model\Entity;

use Doctrine\ORM\Mapping as ORM;

/**
 * Scale Comment
 *
 * @ORM\Table(name="scales_comments", indexes={
 *      @ORM\Index(name="anr", columns={"anr_id"}),
 *      @ORM\Index(name="scale_id", columns={"scale_id"}),
 *      @ORM\Index(name="scale_type_impact_id", columns={"scale_type_impact_id"})
 * })
 * @ORM\Entity
 */
class ScaleComment extends ScaleCommentSuperClass
{
}