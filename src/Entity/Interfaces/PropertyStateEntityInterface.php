<?php declare(strict_types=1);
/**
 * @link      https://github.com/monarc-project for the canonical source repository
 * @copyright Copyright (c) 2016-2024 Luxembourg House of Cybersecurity LHC.lu - Licensed under GNU Affero GPL v3
 * @license   MONARC is licensed under GNU Affero General Public License version 3
 */
namespace Monarc\Core\Entity\Interfaces;

interface PropertyStateEntityInterface
{
    /**
     * All the necessary implicit position relations values have to be tracked to be able to fix positions
     * in case of their changes.
     * For example if parent values is changed the new position is considered with use of a new parent value.
     * The previous parent value is required to fill previous position of the element.
     * Implemented in PropertyStateEntityTrait.
     */
    public function trackPropertyState(string $name, $value): void;

    public function arePropertiesStatesChanged(array $properties): bool;

    public function getPropertiesStates(array $properties): array;
}
