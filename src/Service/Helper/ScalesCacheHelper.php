<?php declare(strict_types=1);
/**
 * @link      https://github.com/monarc-project for the canonical source repository
 * @copyright Copyright (c) 2016-2024 Luxembourg House of Cybersecurity LHC.lu - Licensed under GNU Affero GPL v3
 * @license   MONARC is licensed under GNU Affero General Public License version 3
 */

namespace Monarc\Core\Service\Helper;

use Monarc\Core\Model\Entity\AnrSuperClass;
use Monarc\Core\Model\Entity\OperationalRiskScaleSuperClass;
use Monarc\Core\Model\Entity\ScaleImpactTypeSuperClass;
use Monarc\Core\Model\Entity\ScaleSuperClass;
use Monarc\Core\Table\OperationalRiskScaleTable;
use Monarc\Core\Table\ScaleImpactTypeTable;
use Monarc\Core\Table\ScaleTable;

class ScalesCacheHelper
{
    private array $cachedScales = [];

    private array $cachedScaleImpactTypes = [];

    private array $cachedOperationalRiskScales = [];

    public function __construct(
        private ScaleTable $scaleTable,
        private ScaleImpactTypeTable $scaleImpactTypeTable,
        private OperationalRiskScaleTable $operationalRiskScaleTable
    ) {
    }

    public function getCachedScaleByType(AnrSuperClass $anr, int $scaleType): ScaleSuperClass
    {
        if (empty($this->cachedScales)) {
            $this->cachedScales = $this->scaleTable->findByAnrIndexedByType($anr);
        }
        if (!isset($this->cachedScales[$scaleType])) {
            throw new \LogicException('The passed scale type does not exist.');
        }

        return $this->cachedScales[$scaleType];
    }

    /**
     * @return ScaleSuperClass[]
     */
    public function getCachedScales(AnrSuperClass $anr): array
    {
        if (empty($this->cachedScales)) {
            $this->cachedScales = $this->scaleTable->findByAnrIndexedByType($anr);
        }

        return $this->cachedScales;
    }

    public function getCachedScaleTypeByType(AnrSuperClass $anr, int $scaleType): ScaleSuperClass
    {
        if (empty($this->cachedScaleImpactTypes)) {
            $this->cachedScaleImpactTypes = $this->scaleImpactTypeTable->findByAnrIndexedByType($anr);
        }
        if (!isset($this->cachedScaleImpactTypes[$scaleType])) {
            throw new \LogicException('The passed scale impact type does not exist.');
        }

        return $this->cachedScaleImpactTypes[$scaleType];
    }

    /**
     * @return ScaleImpactTypeSuperClass[]
     */
    public function getCachedScaleImpactTypes(AnrSuperClass $anr): array
    {
        if (empty($this->cachedScaleImpactTypes)) {
            $this->cachedScaleImpactTypes = $this->scaleImpactTypeTable->findByAnrIndexedByType($anr);
        }

        return $this->cachedScaleImpactTypes;
    }

    public function getCachedLikelihoodScale(AnrSuperClass $anr): OperationalRiskScaleSuperClass
    {
        $typeLikelihood = OperationalRiskScaleSuperClass::TYPE_LIKELIHOOD;
        if (!isset($this->cachedOperationalRiskScales[$typeLikelihood])) {
            $this->cachedOperationalRiskScales[$typeLikelihood] = $this->operationalRiskScaleTable->findByAnrAndType(
                $anr,
                $typeLikelihood
            );
        }

        /* There is only one scale of the TYPE_LIKELIHOOD. */
        return current($this->cachedOperationalRiskScales[$typeLikelihood]);
    }
}
