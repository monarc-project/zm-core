<?php
/**
 * @link      https://github.com/monarc-project for the canonical source repository
 * @copyright Copyright (c) 2018 SMILE GIE Securitymadein.lu - Licensed under GNU Affero GPL v3
 * @license   MONARC is licensed under GNU Affero General Public License version 3
 */

namespace MonarcCore\Service\Model\Entity;

use Zend\ServiceManager\FactoryInterface;
use Zend\ServiceManager\ServiceLocatorInterface;

/**
 * Class AbstractServiceModelEntity
 * @package MonarcCore\Service\Model\Entity
 */
abstract class AbstractServiceModelEntity implements FactoryInterface
{
    protected $ressources = [
        'setDbAdapter' => '\MonarcCore\Model\Db',
    ];

    public function createService(ServiceLocatorInterface $serviceLocator)
    {
        $class = str_replace('Service\\', '', substr(get_class($this), 0, -18));
        if (class_exists($class)) {
            $ressources = $this->getRessources();
            $instance = new $class();
            if (!empty($ressources) && is_array($ressources)) {
                foreach ($ressources as $key => $value) {
                    if (method_exists($instance, $key)) {
                        $instance->$key($serviceLocator->get($value));
                    }
                }
            }

            $instance->setLanguage($this->getDefaultLanguage($serviceLocator));

            return $instance;
        } else {
            return false;
        }
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
