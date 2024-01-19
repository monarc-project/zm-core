<?php declare(strict_types=1);
/**
 * @link      https://github.com/monarc-project for the canonical source repository
 * @copyright Copyright (c) 2016-2023 Luxembourg House of Cybersecurity LHC.lu - Licensed under GNU Affero GPL v3
 * @license   MONARC is licensed under GNU Affero General Public License version 3
 */

namespace Monarc\Core\Service\Traits;

use Monarc\Core\Exception\Exception;
use Monarc\Core\Model\Entity\AnrSuperClass;
use Monarc\Core\Model\Entity\InstanceRiskSuperClass;
use Monarc\Core\Model\Entity\ScaleSuperClass;
use Monarc\Core\Table\ScaleTable;

trait ImpactVerificationTrait
{
    private $verificationErrorMessages = [];

    /**
     * @throws Exception
     */
    private function verifyImpacts(AnrSuperClass $anr, ScaleTable $scaleTable, array $data): void
    {
        $scale = $scaleTable->findByAnrAndType($anr, ScaleSuperClass::TYPE_IMPACT);
        $this->verificationErrorMessages = [];
        if (isset($data['confidentiality'])) {
            $value = (int)$data['confidentiality'];
            $this->verifyValue($value, $scale, 'confidentiality');
        }
        if (isset($data['integrity'])) {
            $value = (int)$data['integrity'];
            $this->verifyValue($value, $scale, 'integrity');
        }
        if (isset($data['availability'])) {
            $value = (int)$data['availability'];
            $this->verifyValue($value, $scale, 'availability');
        }

        if (!empty($this->verificationErrorMessages)) {
            throw new Exception(implode(', ', $this->verificationErrorMessages), 412);
        }
    }

    private function verifyInstanceRiskRates(
        InstanceRiskSuperClass $instanceRisk,
        ScaleTable $scaleTable,
        array $data
    ): void {
        $this->verificationErrorMessages = [];
        if (isset($data['threatRate'])) {
            $threatScale = $scaleTable->findByAnrAndType($instanceRisk->getAnr(), ScaleSuperClass::TYPE_THREAT);
            $value = (int)$data['threatRate'];
            $this->verifyValue($value, $threatScale, 'threat probability');
        }
        if (isset($data['vulnerabilityRate'])) {
            $vulnerabilityScale = $scaleTable
                ->findByAnrAndType($instanceRisk->getAnr(), ScaleSuperClass::TYPE_VULNERABILITY);
            $value = (int)$data['vulnerabilityRate'];
            $this->verifyValue($value, $vulnerabilityScale, 'vulnerability qualification');
        }
        if (isset($data['reductionAmount'])) {
            $reductionAmount = (int)$data['reductionAmount'];
            $vulnerabilityRate = (int)($data['vulnerabilityRate'] ?? $instanceRisk->getVulnerabilityRate());
            if ($vulnerabilityRate !== -1 && ($reductionAmount < 0 || $reductionAmount > $vulnerabilityRate)) {
                $this->verificationErrorMessages[] = sprintf(
                    'The value for reduction amount (%d) is not valid (min %d).',
                    $reductionAmount,
                    $vulnerabilityRate
                );
            }
        }

        if (!empty($this->verificationErrorMessages)) {
            throw new Exception(implode(', ', $this->verificationErrorMessages), 412);
        }
    }

    private function verifyValue(int $value, ScaleSuperClass $scale, string $scaleType): void
    {
        if ($value !== -1 && ($value < $scale->getMin() || $value > $scale->getMax())) {
            $this->verificationErrorMessages[] = sprintf(
                'The value %d of "%s" is out of bounds. min: %d max: %d.',
                $value,
                $scaleType,
                $scale->getMin(),
                $scale->getMax()
            );
        }
    }

    /**
     * Determines whether the instance risk's impacts are higher than passed data. Only for global objects.
     *
     * @param InstanceRiskSuperClass $instanceRisk
     * @param array $valuesToCompare ['max_risk' => INT, 'c_impact' => INT, 'i_impact' => INT, 'd_impact' => INT]
     *
     * @return bool
     */
    private function areInstanceRiskImpactsHigher(
        InstanceRiskSuperClass $instanceRisk,
        array $valuesToCompare
    ): bool {
        $instance = $instanceRisk->getInstance();
        $isMaxRiskSet = false;
        foreach ($instance->getInstanceRisks() as $instanceRiskToValidate) {
            if ($instanceRiskToValidate->getCacheMaxRisk() !== -1) {
                $isMaxRiskSet = true;
                break;
            }
        }
        if ($isMaxRiskSet) {
            return $valuesToCompare['max_risk'] < $instanceRisk->getCacheMaxRisk();
        }

        /* We compare CIA criteria in case if max risk value is not set. */
        $maxExistedCia = max($valuesToCompare['c_impact'], $valuesToCompare['i_impact'], $valuesToCompare['d_impact']);
        $maxCurrentCia = max($instance->getConfidentiality(), $instance->getIntegrity(), $instance->getAvailability());
        if ($maxExistedCia === $maxCurrentCia) {
            $sumExistedCia = $valuesToCompare['c_impact'] + $valuesToCompare['i_impact'] + $valuesToCompare['d_impact'];
            $sumCurrentCia = $instance->getConfidentiality() + $instance->getIntegrity() + $instance->getAvailability();

            return $sumExistedCia < $sumCurrentCia;
        }

        return $maxExistedCia < $maxCurrentCia;
    }
}
