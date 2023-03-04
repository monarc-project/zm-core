<?php declare(strict_types=1);
/**
 * @link      https://github.com/monarc-project for the canonical source repository
 * @copyright Copyright (c) 2016-2023 Luxembourg House of Cybersecurity LHC.lu - Licensed under GNU Affero GPL v3
 * @license   MONARC is licensed under GNU Affero General Public License version 3
 */

namespace Monarc\Core\Model\Entity\Traits;

trait PropertyStateEntityTrait
{
    private array $propertiesStates = [];

    public function trackPropertyState(string $name, $value): void
    {
        $this->propertiesStates[$name] = $value;
    }

    public function arePropertiesStatesChanged(array $properties): bool
    {
        foreach ($properties as $key => $val) {
            if (\array_key_exists($key, $this->propertiesStates) && $this->propertiesStates[$key] !== $val) {
                return true;
            }
        }

        return false;
    }

    /**
     * Return the saved values of passed properties. If the property key is not presented the passed value is returned.
     */
    public function getPropertiesStates(array $properties): array
    {
        $result = [];
        foreach ($properties as $key => $val) {
            $result[$key] = $val;
            if (\array_key_exists($key, $this->propertiesStates) && $this->propertiesStates[$key] !== $val) {
                $result[$key] = $this->propertiesStates[$key];
            }
        }

        return $result;
    }
}
