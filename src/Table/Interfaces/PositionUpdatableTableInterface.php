<?php declare(strict_types=1);
/**
 * @link      https://github.com/monarc-project for the canonical source repository
 * @copyright Copyright (c) 2016-2023 Luxembourg House of Cybersecurity LHC.lu - Licensed under GNU Affero GPL v3
 * @license   MONARC is licensed under GNU Affero General Public License version 3
 */

namespace Monarc\Core\Table\Interfaces;

interface PositionUpdatableTableInterface
{
    public function incrementPositions(
        int $positionFrom,
        int $positionTo,
        int $increment,
        array $params,
        string $updater
    ): void;

    public function findMaxPosition(array $params): int;
}
