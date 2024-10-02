<?php
/**
 * @link      https://github.com/monarc-project for the canonical source repository
 * @copyright Copyright (c) 2016-2020 SMILE GIE Securitymadein.lu - Licensed under GNU Affero GPL v3
 * @license   MONARC is licensed under GNU Affero General Public License version 3
 */

namespace Monarc\Core\Service;

use Interop\Container\ContainerInterface;
use Laminas\ServiceManager\Factory\FactoryInterface;

/**
 * TODO: Remove me.
 *
 * Abstract Service Factory
 *
 * Class AbstractServiceFactory
 * @package Monarc\Core\Service
 */
abstract class AbstractServiceFactory implements FactoryInterface
{
    /**
     * The list of ressources loaded for the associated service (AbstractService)
     * @var string[]
     */
    protected $ressources;
    /**
     * The index of the default language defined for this API
     * @var int
     */
    protected $language;
    /**
     * Monarc configuration defined in local.php & module.config.php
     * @var array
     */
    protected $monarcConf = [];

    public function __invoke(ContainerInterface $container, $requestedName, array $options = null)
    {
        $class = (property_exists($this, 'class')) ? $this->class : substr(get_class($this), 0, -7);

        if (class_exists($class)) {
            $ressources = $this->getRessources();
            if (empty($ressources)) {
                $instance = new $class();
            } elseif (is_array($ressources)) {
                $sls = [];
                foreach ($ressources as $key => $value) {
                    $sls[$key] = $container->get($value);
                }
                $instance = new $class($sls);
            } else {
                $instance = new $class($container->get($ressources));
            }

            $conf = $container->get('Config');
            $instance->setLanguage($conf['defaultLanguageIndex'] ?? 1);
            $instance->setMonarcConf(isset($conf['monarc']) ? $conf['monarc'] : []);

            return $instance;
        }

        throw new \LogicException(sprintf('The declared class "%s" can\'t be created', $class));
    }

    /**
     * Get Ressources
     *
     * @return mixed
     */
    public function getRessources()
    {
        return $this->ressources;
    }

    /**
     * Get Language
     *
     * @return mixed
     */
    public function getLanguage()
    {
        return $this->language;
    }

    /**
     * Set Language
     *
     * @param mixed $language
     */
    public function setLanguage($language)
    {
        $this->language = $language;
    }

    /**
     * Get Monarc Conf
     *
     * @return mixed
     */
    public function getMonarcConf()
    {
        return $this->monarcConf;
    }

    /**
     * Set Monarc Conf
     *
     * @param $conf
     * @return array
     */
    public function setMonarcConf($conf)
    {
        $this->monarcConf = $conf;
        return $this->monarcConf;
    }
}
