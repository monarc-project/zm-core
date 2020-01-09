<?php
/**
 * @link      https://github.com/monarc-project for the canonical source repository
 * @copyright Copyright (c) 2016-2020 SMILE GIE Securitymadein.lu - Licensed under GNU Affero GPL v3
 * @license   MONARC is licensed under GNU Affero General Public License version 3
 */

namespace Monarc\Core\Service;

use Interop\Container\ContainerInterface;
use Zend\ServiceManager\Factory\FactoryInterface;

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

            $instance->setLanguage($this->getDefaultLanguage($container));
            $conf = $container->get('Config');
            $instance->setMonarcConf(isset($conf['monarc']) ? $conf['monarc'] : []);

            return $instance;
        }

        return false;
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
     * Get Default Language
     *
     * @param $sm
     * @return mixed
     */
    public function getDefaultLanguage($sm)
    {
        $request = $sm->get('Request');
        if(!$request instanceof \Zend\Console\Request){
            /** @var TreeRouteStack $router */
            $router = $sm->get('Router');
            /** @var RouteMatch $match */
            $match = $router->match($request);
            if($match && strpos($match->getMatchedRouteName(), 'monarc_api_global_client_anr/') === 0){
                $anrId = $match->getParam('anrid', false);

                if ($anrId) {
                    /** @var AnrTable $anrTable */
                    // TODO: the FrontOffice dependency should not be presented in core.
                    $anrTable = $sm->get('Monarc\FrontOffice\Model\Table\AnrTable');
                    $anr = $anrTable->getEntity($anrId);

                    if ($anr->get('language')) {
                        return $anr->get('language');
                    }
                }
            }
        }

        $config = $sm->get('Config');

        return $config['defaultLanguageIndex'];
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
