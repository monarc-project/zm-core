<?php declare(strict_types=1);

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
