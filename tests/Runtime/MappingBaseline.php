<?php declare(strict_types=1);
/**
 * @link      https://github.com/monarc-project for the canonical source repository
 * @copyright Copyright (c) 2016-2026 Luxembourg House of Cybersecurity LHC.lu - Licensed under GNU Affero GPL v3
 * @license   MONARC is licensed under GNU Affero General Public License version 3
 */

namespace Monarc\Core\Tests\Runtime;

final class MappingBaseline
{
    public static function unexpectedErrors(array $actual, array $baseline): array
    {
        $unexpected = [];
        foreach ($actual as $entity => $messages) {
            $newMessages = array_values(array_diff($messages, $baseline[$entity] ?? []));
            if ($newMessages !== []) {
                $unexpected[$entity] = $newMessages;
            }
        }

        return $unexpected;
    }
}
