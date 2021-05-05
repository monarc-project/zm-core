<?php
/**
 * @link      https://github.com/monarc-project for the canonical source repository
 * @copyright Copyright (c) 2016-2020 SMILE GIE Securitymadein.lu - Licensed under GNU Affero GPL v3
 * @license   MONARC is licensed under GNU Affero General Public License version 3
 */

namespace Monarc\Core\Traits;

// TODO: use bcmath lib for the calculations.
trait RiskTrait
{
    /**
     * Calculates the risk's confidentiality value based on the provided parameters
     * @param int $c The base confidentiality value
     * @param int $tRate Threat rate
     * @param int $vRate Vulnerability rate
     * @return int The risk's confidentiality value
     */
    protected function getRiskC($c, $tRate, $vRate)
    {
        return $c !== -1 && $tRate !== -1 && $vRate !== -1
            ? $c * $tRate * $vRate
            : -1;
    }

    /**
     * Calculates the risk's integrity value based on the provided parameters
     * @param int $i The base integrity value
     * @param int $tRate Threat rate
     * @param int $vRate Vulnerability rate
     * @return int The risk's integrity value
     */
    protected function getRiskI($i, $tRate, $vRate)
    {
        return $i !== -1 && $tRate !== -1 && $vRate !== -1
            ? $i * $tRate * $vRate
            : -1;
    }

    /**
     * Calculates the risk's availability (Disponibilité) value based on the provided parameters
     * @param int $d The base availability value
     * @param int $tRate Threat rate
     * @param int $vRate Vulnerability rate
     * @return int The risk's availability value
     */
    protected function getRiskD($d, $tRate, $vRate)
    {
        return $d !== -1 && $tRate !== -1 && $vRate !== -1
            ? $d * $tRate * $vRate
            : -1;
    }

    /**
     * Calculates the target risk based on the provided parameters
     * @param array[int] $impacts The impacts values
     * @param int $tRate Threat rate
     * @param int $vRate Vulnerability rate
     * @param int $vRateReduc Vulnerability rate reduction
     * @return int The target risk
     */
    protected function getTargetRisk($impacts, $tRate, $vRate, $vRateReduc)
    {
        return max($impacts) !== -1 && $tRate !== -1 && $vRate !== -1
            ? max($impacts) * $tRate * ($vRate - $vRateReduc)
            : -1;
    }
}
