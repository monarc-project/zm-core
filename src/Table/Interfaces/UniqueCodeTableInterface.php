<?php declare(strict_types=1);
/**
 * @link      https://github.com/monarc-project for the canonical source repository
 * @copyright Copyright (c) 2016-2023 Luxembourg House of Cybersecurity LHC.lu - Licensed under GNU Affero GPL v3
 * @license   MONARC is licensed under GNU Affero General Public License version 3
 */

namespace Monarc\Core\Table\Interfaces;

use Monarc\Core\Entity\AnrSuperClass;

interface UniqueCodeTableInterface
{
    public function doesCodeAlreadyExist(string $code, ?AnrSuperClass $anr = null, array $excludeFilter = []): bool;
}
