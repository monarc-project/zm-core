<?php declare(strict_types=1);
/**
 * @link      https://github.com/monarc-project for the canonical source repository
 * @copyright Copyright (c) 2016-2023 Luxembourg House of Cybersecurity LHC.lu - Licensed under GNU Affero GPL v3
 * @license   MONARC is licensed under GNU Affero General Public License version 3
 */

namespace Monarc\Core\Service\Traits;

use Monarc\Core\Model\Entity\InstanceRiskSuperClass;

trait RiskCalculationTrait
{
    public function recalculateRiskRates(InstanceRiskSuperClass $instanceRisk): void
    {
        $instance = $instanceRisk->getInstance();

        $riskConfidentiality = $this->calculateRiskConfidentiality(
            $instance->getConfidentiality(),
            $instanceRisk->getThreatRate(),
            $instanceRisk->getVulnerabilityRate()
        );
        $riskIntegrity = $this->calculateRiskIntegrity(
            $instance->getIntegrity(),
            $instanceRisk->getThreatRate(),
            $instanceRisk->getVulnerabilityRate()
        );
        $riskAvailability = $this->calculateRiskAvailability(
            $instance->getAvailability(),
            $instanceRisk->getThreatRate(),
            $instanceRisk->getVulnerabilityRate()
        );

        $instanceRisk
            ->setRiskConfidentiality($riskConfidentiality)
            ->setRiskIntegrity($riskIntegrity)
            ->setRiskAvailability($riskAvailability);

        $risks = [];
        $impacts = [];

        if ($instanceRisk->getThreat()->getConfidentiality()) {
            $risks[] = $riskConfidentiality;
            $impacts[] = $instance->getConfidentiality();
        }
        if ($instanceRisk->getThreat()->getIntegrity()) {
            $risks[] = $riskIntegrity;
            $impacts[] = $instance->getIntegrity();
        }
        if ($instanceRisk->getThreat()->getAvailability()) {
            $risks[] = $riskAvailability;
            $impacts[] = $instance->getAvailability();
        }

        $instanceRisk->setCacheMaxRisk(!empty($risks) ? max($risks) : -1);
        $instanceRisk->setCacheTargetedRisk(
            $this->calculateTargetRisk(
                $impacts,
                $instanceRisk->getThreatRate(),
                $instanceRisk->getVulnerabilityRate(),
                $instanceRisk->getReductionAmount()
            )
        );
    }

    private function calculateRiskConfidentiality(
        int $instanceConfidentialityValue,
        int $threatRate,
        int $vulnerabilityRate
    ): int {
        return $instanceConfidentialityValue === -1 || $threatRate === -1 || $vulnerabilityRate === -1
            ? -1
            : $instanceConfidentialityValue * $threatRate * $vulnerabilityRate;
    }

    private function calculateRiskIntegrity(
        int $instanceIntegrityImpact,
        int $threatRate,
        int $vulnerabilityRate
    ): int {
        return $instanceIntegrityImpact === -1 || $threatRate === -1 || $vulnerabilityRate === -1
            ? -1
            : $instanceIntegrityImpact * $threatRate * $vulnerabilityRate;
    }

    private function calculateRiskAvailability(
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
    private function calculateTargetRisk(
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
