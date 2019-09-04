<?php
/**
 * @see       https://github.com/zendframework/zend-di for the canonical source repository
 * @copyright Copyright (c) 2017 Zend Technologies USA Inc. (https://www.zend.com)
 * @license   https://github.com/zendframework/zend-di/blob/master/LICENSE.md New BSD License
 */

namespace Zend\Di\Resolver;

use Psr\Container\ContainerInterface;
use ReflectionMethod;
use Zend\Di\Exception\LogicException;

use function is_array;
use function is_object;
use function is_scalar;
use function method_exists;
use function trigger_error;
use function var_export;

use const E_USER_DEPRECATED;

/**
 * Wrapper for values that should be directly injected
 */
class ValueInjection implements InjectionInterface
{
    /**
     * Holds the value to inject
     *
     * @var mixed
     */
    protected $value;

    /**
     * @param mixed $value
     */
    public function __construct($value)
    {
        $this->value = $value;
    }

    /**
     * @param array $state
     */
    public static function __set_state(array $state) : self
    {
        return new self($state['value']);
    }

    /**
     * Exports the encapsulated value to php code
     *
     * @return string
     * @throws LogicException
     */
    public function export() : string
    {
        if (! $this->isExportable()) {
            throw new LogicException('Unable to export value');
        }

        if ($this->value === null) {
            return 'null';
        }

        return var_export($this->value, true);
    }

    /**
     * Checks wether the value can be exported for code generation or not
     *
     * @return bool
     */
    public function isExportable() : bool
    {
        return $this->isExportableRecursive($this->value);
    }

    /**
     * Check if the provided value is exportable.
     * For arrays it uses recursion.
     *
     * @param mixed $value
     */
    private function isExportableRecursive($value) : bool
    {
        if (is_scalar($value) || $value === null) {
            return true;
        }

        if (is_array($value)) {
            foreach ($value as $item) {
                if (! $this->isExportableRecursive($item)) {
                    return false;
                }
            }

            return true;
        }

        if (is_object($value) && method_exists($value, '__set_state')) {
            $method = new ReflectionMethod($value, '__set_state');

            return $method->isStatic() && $method->isPublic();
        }

        return false;
    }

    public function toValue(ContainerInterface $container)
    {
        return $this->value;
    }

    /**
     * Get the value to inject
     *
     * @deprecated Since 3.1.0
     * @see toValue()
     */
    public function getValue()
    {
        trigger_error(
            __METHOD__ . ' is deprecated, please migrate to ' . __CLASS__ . '::toValue().',
            E_USER_DEPRECATED
        );

        return $this->value;
    }
}
