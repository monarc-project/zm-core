<?php
/**
 * @link      https://github.com/monarc-project for the canonical source repository
 * @copyright Copyright (c) 2016-2020 SMILE GIE Securitymadein.lu - Licensed under GNU Affero GPL v3
 * @license   MONARC is licensed under GNU Affero General Public License version 3
 */

namespace Monarc\Core\Service\Model\Entity;

use Interop\Container\ContainerInterface;
use Monarc\Core\Model\Db;
use Laminas\ServiceManager\Factory\FactoryInterface;

/**
 * Class AbstractServiceModelEntity
 * @package Monarc\Core\Service\Model\Entity
 */
abstract class AbstractServiceModelEntity implements FactoryInterface
{
    protected $ressources = [
        'setDbAdapter' => Db::class,
    ];

    // TODO: Before burning this out, we need to removed DB dependency from Entities.
    public function __invoke(ContainerInterface $container, $requestedName, array $options = null)
    {
        $class = str_replace('Service\\Model\\', '', substr(get_class($this), 0, -18));
        if (class_exists($class)) {
            $ressources = $this->getRessources();
            $instance = new $class();
            if (!empty($ressources) && is_array($ressources)) {
                foreach ($ressources as $key => $value) {
                    if (method_exists($instance, $key)) {
                        $instance->$key($container->get($value));
                    }
                }
            }

            $instance->setLanguage($this->getDefaultLanguage($container));

            return $instance;
        }

        throw new \LogicException(sprintf('The declared service class "%s" can\'t be created', $class));
    }

    public function getRessources()
    {
        return $this->ressources;
    }

    public function getDefaultLanguage($sm)
    {
        $config = $sm->get('Config');

        return $config['defaultLanguageIndex'];
    }
}
