<?php
/**
 * @link      https://github.com/monarc-project for the canonical source repository
 * @copyright Copyright (c) 2018 SMILE GIE Securitymadein.lu - Licensed under GNU Affero GPL v3
 * @license   MONARC is licensed under GNU Affero General Public License version 3
 */

namespace MonarcCore\Service;

use Zend\ServiceManager\FactoryInterface;
use Zend\ServiceManager\ServiceLocatorInterface;

/**
 * Abstract Service Factory
 *
 * Class AbstractServiceFactory
 * @package MonarcCore\Service
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

    /**
     * Create Service
     *
     * @param ServiceLocatorInterface $serviceLocator
     * @return bool
     */
    public function createService(ServiceLocatorInterface $serviceLocator)
    {
        $class = (property_exists($this, 'class')) ? $this->class : substr(get_class($this), 0, -7);

        if (class_exists($class)) {
            $ressources = $this->getRessources();
            if (empty($ressources)) {
                $instance = new $class();
            } elseif (is_array($ressources)) {
                $sls = [];
                foreach ($ressources as $key => $value) {
                    $sls[$key] = $serviceLocator->get($value);
                }
                $instance = new $class($sls);
            } else {
                $instance = new $class($serviceLocator->get($ressources));
            }

            $instance->setLanguage($this->getDefaultLanguage($serviceLocator));
            $conf = $serviceLocator->get('Config');
            $instance->setMonarcConf(isset($conf['monarc']) ? $conf['monarc'] : []);

            return $instance;
        } else {
            return false;
        }
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
                    $anrTable = $sm->get('\MonarcFO\Model\Table\AnrTable');
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