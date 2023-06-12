<?php declare(strict_types=1);
/**
 * @link      https://github.com/monarc-project for the canonical source repository
 * @copyright Copyright (c) 2016-2023 Luxembourg House of Cybersecurity LHC.lu - Licensed under GNU Affero GPL v3
 * @license   MONARC is licensed under GNU Affero General Public License version 3
 */

namespace Monarc\Core\Service\Traits;

trait RiskCalculationTrait
{
    protected function calculateRiskConfidentiality(
        int $instanceConfidentialityValue,
        int $threatRate,
        int $vulnerabilityRate
    ): int {
        return $instanceConfidentialityValue === -1 || $threatRate === -1 || $vulnerabilityRate === -1
            ? -1
            : $instanceConfidentialityValue * $threatRate * $vulnerabilityRate;
    }

    protected function calculateRiskIntegrity(
        int $instanceIntegrityImpact,
        int $threatRate,
        int $vulnerabilityRate
    ): int {
        return $instanceIntegrityImpact === -1 || $threatRate === -1 || $vulnerabilityRate === -1
            ? -1
            : $instanceIntegrityImpact * $threatRate * $vulnerabilityRate;
    }

    protected function calculateRiskAvailability(
        int $instanceAvailabilityImpact,
        int $threatRate,
        int $vulnerabilityRate
    ) {
        return $instanceAvailabilityImpact === -1 || $threatRate === -1 || $vulnerabilityRate === -1
            ? -1
            : $instanceAvailabilityImpact * $threatRate * $vulnerabilityRate;
    }

    /**
     * @param int[] $instanceImpacts
     */
    protected function calculateTargetRisk(
        array $instanceImpacts,
        int $threatRate,
        int $vulnerabilityRate,
        int $vulnerabilityReductionRate
    ): int {
        return max($instanceImpacts) === -1 || $threatRate === -1 || $vulnerabilityRate === -1
            ? -1
            : max($instanceImpacts) * $threatRate * ($vulnerabilityRate - $vulnerabilityReductionRate);
    }
}
