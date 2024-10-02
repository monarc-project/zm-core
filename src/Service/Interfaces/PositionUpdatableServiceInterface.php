<?php declare(strict_types=1);
/**
 * @link      https://github.com/monarc-project for the canonical source repository
 * @copyright Copyright (c) 2016-2024 Luxembourg House of Cybersecurity LHC.lu - Licensed under GNU Affero GPL v3
 * @license   MONARC is licensed under GNU Affero General Public License version 3
 */

namespace Monarc\Core\Service\Interfaces;

use Monarc\Core\Entity\Interfaces\PositionedEntityInterface;
use Monarc\Core\Table\Interfaces\PositionUpdatableTableInterface;

interface PositionUpdatableServiceInterface
{
    public const IMPLICIT_POSITION_START = 1;
    public const IMPLICIT_POSITION_END = 2;
    public const IMPLICIT_POSITION_AFTER = 3;

    public function updatePositions(
        PositionedEntityInterface $entity,
        PositionUpdatableTableInterface $table,
        array $data
    ): void;
}
