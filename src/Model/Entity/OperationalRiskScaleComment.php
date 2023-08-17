<?php declare(strict_types=1);
/**
 * @link      https://github.com/monarc-project for the canonical source repository
 * @copyright Copyright (c) 2016-2023 Luxembourg House of Cybersecurity LHC.lu - Licensed under GNU Affero GPL v3
 * @license   MONARC is licensed under GNU Affero General Public License version 3
 */

namespace Monarc\Core\Model\Entity;

use Doctrine\ORM\Mapping as ORM;
use Monarc\Core\Model\Entity\Traits\LabelTranslationKeyEntityTrait;

/**
 * @ORM\Table(name="operational_risks_scales_comments", indexes={
 *      @ORM\Index(name="scale_id", columns={"scale_id"})
 * })
 * @ORM\Entity
 */
class OperationalRiskScaleComment extends OperationalRiskScaleCommentSuperClass
{
    use LabelTranslationKeyEntityTrait;

    public static function constructFromObject(OperationalRiskScaleComment $operationalRiskScaleComment): self
    {
        return (new self())
            ->setScaleValue($operationalRiskScaleComment->getScaleValue())
            ->setScaleIndex($operationalRiskScaleComment->getScaleIndex())
            ->setLabelTranslationKey($operationalRiskScaleComment->getLabelTranslationKey())
            ->setIsHidden($operationalRiskScaleComment->isHidden());
    }
}
