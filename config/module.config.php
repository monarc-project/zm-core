<?php
return array(
    // DOCTRINE CONF
    'doctrine' => array(
        'driver' => array(
            'Monarc_core_driver' => array(
                'class' => 'Doctrine\ORM\Mapping\Driver\AnnotationDriver',
                'cache' => 'array',
                'paths' => array(__DIR__ . '/../src/MonarcCore/Model/Entity'),
            ),
            'orm_default' => array(
                'drivers' => array(
                    'MonarcCore\Model\Entity' => 'Monarc_core_driver',
                ),
            ),
            'Monarc_cli_driver' => array(
                'class' => 'Doctrine\ORM\Mapping\Driver\AnnotationDriver',
                'cache' => 'array',
                'paths' => array(__DIR__ . '/../src/MonarcCore/Model/Entity'),
            ),
            'orm_cli' => array(
                'class' => 'Doctrine\ORM\Mapping\Driver\DriverChain',
                'drivers' => array(
                    'MonarcCore\Model\Entity' => 'Monarc_cli_driver',
                ),
            ),
        ),
    ),
    // END DOCTRINE CONF

    'router' => array(
        'routes' => array(
            'monarc' => array(
                'type'    => 'segment',
                'options' => array(
                    'route'    => '/index[/:id]',
                    'constraints' => array(
                        'id'     => '[0-9]+',
                    ),
                    'defaults' => array(
                        'controller' => '\MonarcCore\Controller\Index',
                    ),
                ),
            ),
            'auth' => array(
                'type'    => 'segment',
                'options' => array(
                    'route'    => '/auth[/:id]',
                    'constraints' => array(
                        'id'     => '.+',
                    ),
                    'defaults' => array(
                        'controller' => '\MonarcCore\Controller\Authentication',
                    ),
                ),
            ),
        ),
    ),

    'view_manager' => array(
        'strategies' => array(
            'ViewJsonStrategy',
        ),
    ),

    'service_manager' => array(
        /*'abstract_factories' => array(
            'MonarcCore\Service\AbstractFactory',
        ),*/
        'invokables' => array(
            'MonarcCore\Model\Entity\Anr'                   => 'MonarcCore\Model\Entity\Anr',
            'MonarcCore\Model\Entity\Guide'                 => 'MonarcCore\Model\Entity\Guide',
            'MonarcCore\Model\Entity\GuideItem'             => 'MonarcCore\Model\Entity\GuideItem',
            'MonarcCore\Model\Entity\Historical'            => 'MonarcCore\Model\Entity\Historical',
            'MonarcCore\Model\Entity\ObjectObject'          => 'MonarcCore\Model\Entity\ObjectObject',
            'MonarcCore\Model\Entity\PasswordToken'         => 'MonarcCore\Model\Entity\PasswordToken',
            'MonarcCore\Model\Entity\Role'                  => 'MonarcCore\Model\Entity\Role',
            'MonarcCore\Model\Entity\Scale'                 => 'MonarcCore\Model\Entity\Scale',
            'MonarcCore\Model\Entity\ScaleComment'          => 'MonarcCore\Model\Entity\ScaleComment',
            'MonarcCore\Model\Entity\ScaleType'             => 'MonarcCore\Model\Entity\ScaleType',
            'MonarcCore\Model\Entity\UserRole'              => 'MonarcCore\Model\Entity\UserRole',
            'MonarcCore\Service\Mime\Part'                  => 'Zend\Mime\Part',
            'MonarcCore\Service\Mime\Message'               => 'Zend\Mime\Message',
            'MonarcCore\Service\Mail\Message'               => 'Zend\Mail\Message',
            'MonarcCore\Service\Mail\Transport\Smtp'        => 'Zend\Mail\Transport\Smtp',
            'MonarcCore\Service\Mail\Transport\SmtpOptions' => 'Zend\Mail\Transport\SmtpOptions',
        ),
        'factories' => array(
            'MonarcCore\Service\AmvService'                 => 'MonarcCore\Service\AmvServiceFactory',
            'MonarcCore\Service\AnrService'                 => 'MonarcCore\Service\AnrServiceFactory',
            'MonarcCore\Service\AssetService'               => 'MonarcCore\Service\AssetServiceFactory',
            'MonarcCore\Service\AuthenticationService'      => 'MonarcCore\Service\AuthenticationServiceFactory',
            'MonarcCore\Service\CityService'                => 'MonarcCore\Service\CityServiceFactory',
            'MonarcCore\Service\ConfigService'              => 'MonarcCore\Service\ConfigServiceFactory',
            'MonarcCore\Service\CountryService'             => 'MonarcCore\Service\CountryServiceFactory',
            'MonarcCore\Service\GuideService'               => 'MonarcCore\Service\GuideServiceFactory',
            'MonarcCore\Service\GuideItemService'           => 'MonarcCore\Service\GuideItemServiceFactory',
            'MonarcCore\Service\HistoricalService'          => 'MonarcCore\Service\HistoricalServiceFactory',
            'MonarcCore\Service\IndexService'               => 'MonarcCore\Service\IndexServiceFactory',
            'MonarcCore\Service\InstanceService'            => 'MonarcCore\Service\InstanceServiceFactory',
            'MonarcCore\Service\InstanceRiskService'        => 'MonarcCore\Service\InstanceRiskServiceFactory',
            'MonarcCore\Service\InstanceRiskOpService'      => 'MonarcCore\Service\InstanceRiskOpServiceFactory',
            'MonarcCore\Service\InstanceConsequenceService' => 'MonarcCore\Service\InstanceConsequenceServiceFactory',
            'MonarcCore\Service\MailService'                => 'MonarcCore\Service\MailServiceFactory',
            'MonarcCore\Service\MeasureService'             => 'MonarcCore\Service\MeasureServiceFactory',
            'MonarcCore\Service\ModelService'               => 'MonarcCore\Service\ModelServiceFactory',
            'MonarcCore\Service\ObjectService'              => 'MonarcCore\Service\ObjectServiceFactory',
            'MonarcCore\Service\ObjectCategoryService'      => 'MonarcCore\Service\ObjectCategoryServiceFactory',
            'MonarcCore\Service\ObjectObjectService'        => 'MonarcCore\Service\ObjectObjectServiceFactory',
            'MonarcCore\Service\PasswordService'            => 'MonarcCore\Service\PasswordServiceFactory',
            'MonarcCore\Service\RolfRiskService'            => 'MonarcCore\Service\RolfRiskServiceFactory',
            'MonarcCore\Service\RolfTagService'             => 'MonarcCore\Service\RolfTagServiceFactory',
            'MonarcCore\Service\RoleService'                => 'MonarcCore\Service\RoleServiceFactory',
            'MonarcCore\Service\ScaleService'               => 'MonarcCore\Service\ScaleServiceFactory',
            'MonarcCore\Service\ScaleCommentService'        => 'MonarcCore\Service\ScaleCommentServiceFactory',
            'MonarcCore\Service\ScaleTypeService'           => 'MonarcCore\Service\ScaleTypeServiceFactory',
            'MonarcCore\Service\ThemeService'               => 'MonarcCore\Service\ThemeServiceFactory',
            'MonarcCore\Service\ThreatService'              => 'MonarcCore\Service\ThreatServiceFactory',
            'MonarcCore\Service\UserRoleService'            => 'MonarcCore\Service\UserRoleServiceFactory',
            'MonarcCore\Service\UserService'                => 'MonarcCore\Service\UserServiceFactory',
            'MonarcCore\Service\VulnerabilityService'       => 'MonarcCore\Service\VulnerabilityServiceFactory',
            'MonarcCore\Service\DocModelService'            => 'MonarcCore\Service\DocModelServiceFactory',
            'MonarcCore\Service\ModelObjectService'         => 'MonarcCore\Service\ModelObjectServiceFactory',
            'MonarcCore\Service\UserProfileService'         => 'MonarcCore\Service\UserProfileServiceFactory',
        ),
        'shared' => array(
            'MonarcCore\Model\Entity\Scale' => false,
            'MonarcCore\Model\Entity\ScaleType' => false,
        ),
        'initializers' =>
        [
            \MonarcCore\Service\Initializer\ObjectManagerInitializer::class
        ]
    ),

    'monarc' => array(
        'ttl' => 20, // timeout
        'salt' => '', // salt privé pour chiffrement pwd
    ),

    'permissions' => array(
        'monarc',
        'home',
        'auth',
        'monarc_api_admin_roles',
        'monarc_api_admin_passwords',
        'monarc_api_config',
        'monarc_api_guides',
        'monarc_api_guides_items',
        'monarc_api_guides_types',
        'monarc_api_themes',
        'monarc_api_models',
    ),

    'cases' => [
        'name' => 'Cases',
        'mail' => 'info@cases.lu',
    ],

    'defaultLanguageIndex' => 1,
    'languages' => array(
        'fr' => array(
            'index' => 1,
            'label' => 'Français'
        ),
        'en' => array(
            'index' => 2,
            'label' => 'English'
        ),
        'de' => array(
            'index' => 3,
            'label' => 'Deutsch'
        ),
    ),
);
