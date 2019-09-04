<?php
/**
 * @see       https://github.com/zendframework/zend-di for the canonical source repository
 * @copyright Copyright (c) 2018 Zend Technologies USA Inc. (https://www.zend.com)
 * @license   https://github.com/zendframework/zend-di/blob/master/LICENSE.md New BSD License
 */

namespace Zend\Di\Resolver;

use Psr\Container\ContainerInterface;
use Zend\Di\Exception\LogicException;

/**
 * Encapsulates the injection to perform for a parameter
 *
 * Implementations of this class will handle how the resolved dependency is provided and how (if possible) it can
 * be generated to php code for AoT compilation.
 *
 * For example the `TypeInjection`, that implements this interface, handles when the injections resolves to a specific
 * type. It will provide the injection with help of the di container. `ValueInjection` on the other hand handles
 * when a concrete value or instance should be injected.
 *
 * `DependencyResolverInterface::resolveParameters()` will provide an instance of this type for each injection.
 * `InjectorInterface` implementations will use these to obtain the actual injection value with `toValue()` while code
 * generators will use it to generate a factory.
 *
 * @internal
 * @see DependencyResolverInterface::resolveParameters() The resolver method's return type
 * @see \Zend\Di\Injector::getInjectionValue()           The default injector implementation
 * @see TypeInjection                                    Implementation for injecting an instance of a specific type
 * @see ValueInjection                                   Implementation for injection an existing value
 */
interface InjectionInterface
{
    /**
     * Provides the actual value for injection, that will be passed to the constructor
     *
     * Implementations may utilize the provided ioc container to fulfill this purpose.
     *
     * @return mixed The resulting injection value
     */
    public function toValue(ContainerInterface $container);

    /**
     * Export the injection to PHP code
     *
     * This will be used by code generators to provide AoT factories
     *
     * @throws LogicException When the injection is not exportable
     */
    public function export() : string;

    /**
     * Whether this injection can be exported as code or not
     *
     * Implementations may use this method to indicate if they may be exported to PHP code. This may not be possible
     * in some situations, for example when the injection is a `resource` that cannot be provided with a piece
     * of php code.
     *
     * When this method returns false, a call to `export()` should throw a
     * `Zend\Di\Exception\LogicException`
     */
    public function isExportable() : bool;
}
