<?php declare(strict_types=1);
/**
 * @link      https://github.com/monarc-project for the canonical source repository
 * @copyright Copyright (c) 2016-2024 Luxembourg House of Cybersecurity LHC.lu - Licensed under GNU Affero GPL v3
 * @license   MONARC is licensed under GNU Affero General Public License version 3
 */

namespace Monarc\Core\Service\Traits;

use Monarc\Core\Exception\Exception;
use Monarc\Core\Entity\OperationalInstanceRiskScaleSuperClass;
use Monarc\Core\Entity\OperationalRiskScaleSuperClass;

trait OperationalRiskScaleVerificationTrait
{
    private function verifyScaleValue(
        OperationalInstanceRiskScaleSuperClass $operationalInstanceRiskScale,
        int $scaleValue
    ): void {
        $operationalRiskScaleType = $operationalInstanceRiskScale->getOperationalRiskScaleType();
        $allowedValues = [];
        foreach ($operationalRiskScaleType->getOperationalRiskScaleComments() as $operationalRiskScaleComment) {
            if (!$operationalRiskScaleComment->isHidden()) {
                $allowedValues[] = $operationalRiskScaleComment->getScaleValue();
            }
        }

        if ($scaleValue !== -1 && !\in_array($scaleValue, $allowedValues, true)) {
            throw new Exception(sprintf(
                'The value %d should be between one of [%s]',
                $scaleValue,
                implode(', ', $allowedValues)
            ), 412);
        }
    }

    private function verifyScaleProbabilityValue(
        int $scaleProbabilityValue,
        OperationalRiskScaleSuperClass $operationalRiskScale
    ): void {
        if ($scaleProbabilityValue !== -1 && (
            $scaleProbabilityValue < $operationalRiskScale->getMin()
            || $scaleProbabilityValue > $operationalRiskScale->getMax()
        )) {
            throw new Exception(sprintf(
                'The value %d should be between %d and %d.',
                $scaleProbabilityValue,
                $operationalRiskScale->getMin(),
                $operationalRiskScale->getMax()
            ), 412);
        }
    }
}
