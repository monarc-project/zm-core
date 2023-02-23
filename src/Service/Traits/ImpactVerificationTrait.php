<?php declare(strict_types=1);
/**
 * @link      https://github.com/monarc-project for the canonical source repository
 * @copyright Copyright (c) 2016-2023 Luxembourg House of Cybersecurity LHC.lu - Licensed under GNU Affero GPL v3
 * @license   MONARC is licensed under GNU Affero General Public License version 3
 */

namespace Monarc\Core\Service\Traits;

use Monarc\Core\Exception\Exception;
use Monarc\Core\Model\Entity\ScaleSuperClass;

trait ImpactVerificationTrait
{
    protected function verifyImpactData(ScaleSuperClass $scale, array $data): void
    {
        if (isset($data['c'])) {
            $value = (int)$data['c'];
            if ($value != -1 && ($value < $scaleImpact->getMin() || $value > $scaleImpact->get('max'))) {
                $errors[] = 'Value for ' . $field . ' is not valid';
            }
        }

        if (!empty($errors)) {
            throw new Exception(implode(', ', $errors), 412);
        }
    }
}
