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


            'Monarc_cli_fo_driver' => array(
                'class' => 'Doctrine\ORM\Mapping\Driver\AnnotationDriver',
                'cache' => 'array',
                'paths' => array(__DIR__ . '/../src/MonarcCore/Model/Entity'),
            ),
            'orm_cli_fo' => array(
                'class' => 'Doctrine\ORM\Mapping\Driver\DriverChain',
                'drivers' => array(
                    'MonarcCore\Model\Entity' => 'Monarc_cli_fo_driver',
                ),
            ),
        ),
    ),
    // END DOCTRINE CONF

    'router' => array(
        'routes' => array(
            'monarc' => array(
                'type' => 'Zend\Mvc\Router\Http\Literal',
                'options' => array(
                    'route'    => '/',
                    'defaults' => array(
                        'controller' => '\MonarcCore\Controller\Index',
                        'action' => 'index',
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

            'monarc_api_anr' => array(
                'type' => 'segment',
                'options' => array(
                    'route' => '/api/anr[/:id]',
                    'constraints' => array(
                        'id' => '[0-9]+',
                    ),
                    'defaults' => array(
                        'controller' => 'MonarcCore\Controller\ApiAnr',
                    ),
                ),
            ),
            'monarc_api_anr_export' => array(
                'type' => 'segment',
                'options' => array(
                    'route' => '/api/anr-export',
                    'defaults' => array(
                        'controller' => 'MonarcCore\Controller\ApiAnrExport',
                    ),
                ),
            ),

            'monarc_api_anr_instances_consequences' => array(
                'type' => 'segment',
                'options' => array(
                    'route' => '/api/anr/:anrid/instances-consequences[/:id]',
                    'constraints' => array(
                        'id' => '[0-9]+',
                    ),
                    'defaults' => array(
                        'controller' => 'MonarcCore\Controller\ApiAnrInstancesConsequences',
                    ),
                ),
            ),

            'monarc_api_anr_risks' => array(
                'type' => 'segment',
                'options' => array(
                    'route' => '/api/anr/:anrid/risks[/:id]',
                    'constraints' => array(
                        'anrid' => '[0-9]+',
                        'id' => '[0-9]+',
                    ),
                    'defaults' => array(
                        'controller' => 'MonarcCore\Controller\ApiAnrRisks',
                    ),
                ),
            ),

            'monarc_api_anr_risks_op' => array(
                'type' => 'segment',
                'options' => array(
                    'route' => '/api/anr/:anrid/risksop[/:id]',
                    'defaults' => array(
                        'controller' => 'MonarcCore\Controller\ApiAnrRisksOp',
                    ),
                ),
            ),

            'monarc_api_anr_instances' => array(
                'type' => 'segment',
                'options' => array(
                    'route' => '/api/anr/:anrid/instances[/:id]',
                    'constraints' => array(
                        'id' => '[0-9]+',
                    ),
                    'defaults' => array(
                        'controller' => 'MonarcCore\Controller\ApiAnrInstances',
                    ),
                ),
            ),

            'monarc_api_anr_instances_export' => array(
                'type' => 'segment',
                'options' => array(
                    'route' => '/api/anr/:anrid/instances/:id/export',
                    'constraints' => array(
                        'id' => '[0-9]+',
                    ),
                    'defaults' => array(
                        'controller'    => 'MonarcCore\Controller\ApiAnrInstances',
                        'action'        => 'export'
                    ),
                ),
            ),

            'monarc_api_anr_instances_risks' => array(
                'type' => 'segment',
                'options' => array(
                    'route' => '/api/anr/:anrid/instances-risks[/:id]',
                    'constraints' => array(
                        'id' => '[0-9]+',
                    ),
                    'defaults' => array(
                        'controller' => 'MonarcCore\Controller\ApiAnrInstancesRisks',
                    ),
                ),
            ),

            'monarc_api_anr_instances_risksop' => array(
                'type' => 'segment',
                'options' => array(
                    'route' => '/api/anr/:anrid/instances-oprisks[/:id]',
                    'constraints' => array(
                        'id' => '[0-9]+',
                    ),
                    'defaults' => array(
                        'controller' => 'MonarcCore\Controller\ApiAnrInstancesRisksOp',
                    ),
                ),
            ),

            'monarc_api_anr_library_category' => array(
                'type' => 'segment',
                'options' => array(
                    'route' => '/api/anr/:anrid/library-category[/:id]',
                    'constraints' => array(
                        'id' => '[0-9]+',
                    ),
                    'defaults' => array(
                        'controller' => 'MonarcCore\Controller\ApiAnrLibraryCategory',
                    ),
                ),
            ),

            'monarc_api_anr_library' => array(
                'type' => 'segment',
                'options' => array(
                    'route' => '/api/anr/:anrid/library[/:id]',
                    'constraints' => array(
                        'id' => '[0-9]+',
                    ),
                    'defaults' => array(
                        'controller' => 'MonarcCore\Controller\ApiAnrLibrary',
                    ),
                ),
            ),

            'monarc_api_anr_objects' => array(
                'type' => 'segment',
                'options' => array(
                    'route' => '/api/anr/:anrid/objects[/:id]',
                    'constraints' => array(
                        'id' => '[0-9]+',
                    ),
                    'defaults' => array(
                        'controller' => 'MonarcCore\Controller\ApiAnrObject',
                    ),
                ),
            ),

            'monarc_api_anr_objects_parents' => array(
                'type' => 'segment',
                'options' => array(
                    'route' => '/api/anr/:anrid/objects/:id/parents',
                    'constraints' => array(
                        'id' => '[0-9]+',
                    ),
                    'defaults' => array(
                        'controller'    => 'MonarcCore\Controller\ApiAnrObject',
                        'action'        => 'parents'
                    ),
                ),
            ),

            'monarc_api_models_duplication' => array(
                'type' => 'segment',
                'options' => array(
                    'route' => '/api/models-duplication[/:id]',
                    'constraints' => array(
                        'id' => '[0-9]+',
                    ),
                    'defaults' => array(
                        'controller' => 'MonarcCore\Controller\ApiModelsDuplication',
                    ),
                ),
            ),

            'monarc_api_scales' => array(
                'type' => 'segment',
                'options' => array(
                    'route' => '/api/anr/:anrId/scales[/:id]',
                    'constraints' => array(
                        'id' => '[0-9]+',
                    ),
                    'defaults' => array(
                        'controller' => 'MonarcCore\Controller\ApiAnrScales',
                    ),
                ),
            ),

            'monarc_api_scales_comments' => array(
                'type' => 'segment',
                'options' => array(
                    'route' => '/api/anr/:anrId/scales/:scaleId/comments[/:id]',
                    'constraints' => array(
                        'anrId' => '[0-9]+',
                        'scaleId' => '[0-9]+',
                        'id' => '[0-9]+',
                    ),
                    'defaults' => array(
                        'controller' => 'MonarcCore\Controller\ApiAnrScalesComments',
                    ),
                ),
            ),

            'monarc_api_scales_types' => array(
                'type' => 'segment',
                'options' => array(
                    'route' => '/api/anr/:anrId/scales-types[/:id]',
                    'constraints' => array(
                        'id' => '[0-9]+',
                    ),
                    'defaults' => array(
                        'controller' => 'MonarcCore\Controller\ApiAnrScalesTypes',
                    ),
                ),
            ),
        ),
    ),

    "console" => array(
        "router" => array(
            "routes" => array(
                "monarc-mail-tester" => array(
                    "options" => array(
                        "route" => "monarc:mail-tester [--from=] <email>",
                        "defaults" => array(
                            "controller" => 'MonarcCore\Controller\MailTester',
                            "action" => "index",
                        ),
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
            'MonarcCore\Model\Entity\Question'              => 'MonarcCore\Model\Entity\Question',
            'MonarcCore\Model\Entity\QuestionChoice'        => 'MonarcCore\Model\Entity\QuestionChoice',
            'MonarcCore\Model\Entity\PasswordToken'         => 'MonarcCore\Model\Entity\PasswordToken',
            'MonarcCore\Model\Entity\UserRole'              => 'MonarcCore\Model\Entity\UserRole',
            'MonarcCore\Service\Mime\Part'                  => 'Zend\Mime\Part',
            'MonarcCore\Service\Mime\Message'               => 'Zend\Mime\Message',
            'MonarcCore\Service\Mail\Message'               => 'Zend\Mail\Message',
            'MonarcCore\Service\Mail\Transport\Smtp'        => 'Zend\Mail\Transport\Smtp',
            'MonarcCore\Service\Mail\Transport\SmtpOptions' => 'Zend\Mail\Transport\SmtpOptions',
        ),
        'factories' => array(
            // DBs
            '\MonarcCore\Model\Db' => '\MonarcCore\Service\Model\DbFactory',
            '\MonarcCli\Model\Db' => '\MonarcCore\Service\Model\DbCliFactory',

            // Services
            'MonarcCore\Service\AmvService'                 => 'MonarcCore\Service\AmvServiceFactory',
            'MonarcCore\Service\AnrService'                 => 'MonarcCore\Service\AnrServiceFactory',
            'MonarcCore\Service\AssetService'               => 'MonarcCore\Service\AssetServiceFactory',
            'MonarcCore\Service\AssetExportService'         => 'MonarcCore\Service\AssetExportServiceFactory',
            'MonarcCore\Service\AuthenticationService'      => 'MonarcCore\Service\AuthenticationServiceFactory',
            'MonarcCore\Service\ConfigService'              => 'MonarcCore\Service\ConfigServiceFactory',
            'MonarcCore\Service\QuestionService'            => 'MonarcCore\Service\QuestionServiceFactory',
            'MonarcCore\Service\QuestionChoiceService'      => 'MonarcCore\Service\QuestionChoiceServiceFactory',
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
            'MonarcCore\Service\ObjectExportService'        => 'MonarcCore\Service\ObjectExportServiceFactory',
            'MonarcCore\Service\ObjectCategoryService'      => 'MonarcCore\Service\ObjectCategoryServiceFactory',
            'MonarcCore\Service\ObjectObjectService'        => 'MonarcCore\Service\ObjectObjectServiceFactory',
            'MonarcCore\Service\PasswordService'            => 'MonarcCore\Service\PasswordServiceFactory',
            'MonarcCore\Service\RolfRiskService'            => 'MonarcCore\Service\RolfRiskServiceFactory',
            'MonarcCore\Service\RolfTagService'             => 'MonarcCore\Service\RolfTagServiceFactory',
            'MonarcCore\Service\RoleService'                => 'MonarcCore\Service\RoleServiceFactory',
            'MonarcCore\Service\ScaleService'               => 'MonarcCore\Service\ScaleServiceFactory',
            'MonarcCore\Service\ScaleCommentService'        => 'MonarcCore\Service\ScaleCommentServiceFactory',
            'MonarcCore\Service\ScaleImpactTypeService'     => 'MonarcCore\Service\ScaleImpactTypeServiceFactory',
            'MonarcCore\Service\ThemeService'               => 'MonarcCore\Service\ThemeServiceFactory',
            'MonarcCore\Service\ThreatService'              => 'MonarcCore\Service\ThreatServiceFactory',
            'MonarcCore\Service\SoaCategoryService'         => 'MonarcCore\Service\SoaCategoryServiceFactory',
            'MonarcCore\Service\UserRoleService'            => 'MonarcCore\Service\UserRoleServiceFactory',
            'MonarcCore\Service\UserService'                => 'MonarcCore\Service\UserServiceFactory',
            'MonarcCore\Service\VulnerabilityService'       => 'MonarcCore\Service\VulnerabilityServiceFactory',
            'MonarcCore\Service\DeliveriesModelsService'    => 'MonarcCore\Service\DeliveriesModelsServiceFactory',
            'MonarcCore\Service\ModelObjectService'         => 'MonarcCore\Service\ModelObjectServiceFactory',
            'MonarcCore\Service\UserProfileService'         => 'MonarcCore\Service\UserProfileServiceFactory',
            'MonarcCore\Service\AnrObjectService'           => 'MonarcCore\Service\AnrObjectServiceFactory',
            'MonarcCore\Service\MailTesterService'          => 'MonarcCore\Service\MailTesterServiceFactory',

            // Entities
            '\MonarcCore\Model\Entity\DeliveriesModels' => '\MonarcCore\Service\Model\Entity\DeliveriesModelsServiceModelEntity',
            '\MonarcCore\Model\Entity\Asset' => '\MonarcCore\Service\Model\Entity\AssetServiceModelEntity',
            '\MonarcCore\Model\Entity\Measure' => '\MonarcCore\Service\Model\Entity\MeasureServiceModelEntity',
            '\MonarcCore\Model\Entity\SoaCategory' => '\MonarcCore\Service\Model\Entity\SoaCategoryServiceModelEntity',
            '\MonarcCore\Model\Entity\Model' => '\MonarcCore\Service\Model\Entity\ModelServiceModelEntity',
            '\MonarcCore\Model\Entity\Object' => '\MonarcCore\Service\Model\Entity\ObjectServiceModelEntity',
            '\MonarcCore\Model\Entity\Instance' => '\MonarcCore\Service\Model\Entity\InstanceServiceModelEntity',
            '\MonarcCore\Model\Entity\InstanceConsequence' => '\MonarcCore\Service\Model\Entity\InstanceConsequenceServiceModelEntity',
            '\MonarcCore\Model\Entity\InstanceRisk' => '\MonarcCore\Service\Model\Entity\InstanceRiskServiceModelEntity',
            '\MonarcCore\Model\Entity\InstanceRiskOp' => '\MonarcCore\Service\Model\Entity\InstanceRiskOpServiceModelEntity',
            '\MonarcCore\Model\Entity\ObjectCategory' => '\MonarcCore\Service\Model\Entity\ObjectCategoryServiceModelEntity',
            '\MonarcCore\Model\Entity\RolfRisk' => '\MonarcCore\Service\Model\Entity\RolfRiskServiceModelEntity',
            '\MonarcCore\Model\Entity\RolfTag' => '\MonarcCore\Service\Model\Entity\RolfTagServiceModelEntity',
            '\MonarcCore\Model\Entity\Theme' => '\MonarcCore\Service\Model\Entity\ThemeServiceModelEntity',
            '\MonarcCore\Model\Entity\Threat' => '\MonarcCore\Service\Model\Entity\ThreatServiceModelEntity',
            '\MonarcCore\Model\Entity\Soa' => '\MonarcCore\Service\Model\Entity\SoaServiceModelEntity',
            '\MonarcCore\Model\Entity\User' => '\MonarcCore\Service\Model\Entity\UserServiceModelEntity',
            '\MonarcCore\Model\Entity\Vulnerability' => '\MonarcCore\Service\Model\Entity\VulnerabilityServiceModelEntity',
            '\MonarcCore\Model\Entity\Amv' => '\MonarcCore\Service\Model\Entity\AmvServiceModelEntity',
            '\MonarcCore\Model\Entity\GuideItem' => '\MonarcCore\Service\Model\Entity\GuideItemServiceModelEntity',
            '\MonarcCore\Model\Entity\Anr' => '\MonarcCore\Service\Model\Entity\AnrServiceModelEntity',
            '\MonarcCore\Model\Entity\AnrObjectCategory' => '\MonarcCore\Service\Model\Entity\AnrObjectCategoryServiceModelEntity',
            '\MonarcCore\Model\Entity\Guide' => '\MonarcCore\Service\Model\Entity\GuideServiceModelEntity',
            '\MonarcCore\Model\Entity\Historical' => '\MonarcCore\Service\Model\Entity\HistoricalServiceModelEntity',
            '\MonarcCore\Model\Entity\ObjectObject' => '\MonarcCore\Service\Model\Entity\ObjectObjectServiceModelEntity',
            '\MonarcCore\Model\Entity\Role' => '\MonarcCore\Service\Model\Entity\RoleServiceModelEntity',
            '\MonarcCore\Model\Entity\Scale' => '\MonarcCore\Service\Model\Entity\ScaleServiceModelEntity',
            '\MonarcCore\Model\Entity\ScaleComment' => '\MonarcCore\Service\Model\Entity\ScaleCommentServiceModelEntity',
            '\MonarcCore\Model\Entity\ScaleImpactType' => '\MonarcCore\Service\Model\Entity\ScaleImpactTypeServiceModelEntity',

            // Tables
            '\MonarcCore\Model\Table\UserTable' => '\MonarcCore\Service\Model\Table\UserServiceModelTable',
            '\MonarcCore\Model\Table\ModelTable' => '\MonarcCore\Service\Model\Table\ModelServiceModelTable',
            '\MonarcCore\Model\Table\AnrTable' => '\MonarcCore\Service\Model\Table\AnrServiceModelTable',
            '\MonarcCore\Model\Table\AnrObjectCategoryTable' => '\MonarcCore\Service\Model\Table\AnrObjectCategoryServiceModelTable',
            '\MonarcCore\Model\Table\QuestionTable' => '\MonarcCore\Service\Model\Table\QuestionServiceModelTable',
            '\MonarcCore\Model\Table\QuestionChoiceTable' => '\MonarcCore\Service\Model\Table\QuestionChoiceServiceModelTable',
            '\MonarcCore\Model\Table\GuideTable' => '\MonarcCore\Service\Model\Table\GuideServiceModelTable',
            '\MonarcCore\Model\Table\GuideItemTable' => '\MonarcCore\Service\Model\Table\GuideItemServiceModelTable',
            '\MonarcCore\Model\Table\MeasureTable' => '\MonarcCore\Service\Model\Table\MeasureServiceModelTable',
            '\MonarcCore\Model\Table\SoaCategoryTable' => '\MonarcCore\Service\Model\Table\SoaCategoryServiceModelTable',
            '\MonarcCore\Model\Table\ObjectTable' => '\MonarcCore\Service\Model\Table\ObjectServiceModelTable',
            '\MonarcCore\Model\Table\InstanceTable' => '\MonarcCore\Service\Model\Table\InstanceServiceModelTable',
            '\MonarcCore\Model\Table\InstanceConsequenceTable' => '\MonarcCore\Service\Model\Table\InstanceConsequenceServiceModelTable',
            '\MonarcCore\Model\Table\InstanceRiskTable' => '\MonarcCore\Service\Model\Table\InstanceRiskServiceModelTable',
            '\MonarcCore\Model\Table\InstanceRiskOpTable' => '\MonarcCore\Service\Model\Table\InstanceRiskOpServiceModelTable',
            '\MonarcCore\Model\Table\ObjectCategoryTable' => '\MonarcCore\Service\Model\Table\ObjectCategoryServiceModelTable',
            '\MonarcCore\Model\Table\ObjectObjectTable' => '\MonarcCore\Service\Model\Table\ObjectObjectServiceModelTable',
            '\MonarcCore\Model\Table\ThemeTable' => '\MonarcCore\Service\Model\Table\ThemeServiceModelTable',
            '\MonarcCore\Model\Table\HistoricalTable' => '\MonarcCore\Service\Model\Table\HistoricalServiceModelTable',
            '\MonarcCore\Model\Table\AssetTable' => '\MonarcCore\Service\Model\Table\AssetServiceModelTable',
            '\MonarcCore\Model\Table\AmvTable' => '\MonarcCore\Service\Model\Table\AmvServiceModelTable',
            '\MonarcCore\Model\Table\ThreatTable' => '\MonarcCore\Service\Model\Table\ThreatServiceModelTable',
            '\MonarcCore\Model\Table\RolfTagTable' => '\MonarcCore\Service\Model\Table\RolfTagServiceModelTable',
            '\MonarcCore\Model\Table\RolfRiskTable' => '\MonarcCore\Service\Model\Table\RolfRiskServiceModelTable',
            '\MonarcCore\Model\Table\ScaleTable' => '\MonarcCore\Service\Model\Table\ScaleServiceModelTable',
            '\MonarcCore\Model\Table\ScaleCommentTable' => '\MonarcCore\Service\Model\Table\ScaleCommentServiceModelTable',
            '\MonarcCore\Model\Table\ScaleImpactTypeTable' => '\MonarcCore\Service\Model\Table\ScaleImpactTypeServiceModelTable',
            '\MonarcCore\Model\Table\SoaTable' => '\MonarcCore\Service\Model\Table\SoaServiceModelTable',
            '\MonarcCore\Model\Table\VulnerabilityTable' => '\MonarcCore\Service\Model\Table\VulnerabilityServiceModelTable',
            '\MonarcCore\Model\Table\PasswordTokenTable' => '\MonarcCore\Service\Model\Table\PasswordTokenServiceModelTable',
            '\MonarcCore\Model\Table\UserTokenTable' => '\MonarcCore\Service\Model\Table\UserTokenServiceModelTable',
            '\MonarcCore\Model\Table\UserRoleTable' => '\MonarcCore\Service\Model\Table\UserRoleServiceModelTable',
            '\MonarcCore\Model\Table\DeliveriesModelsTable' => '\MonarcCore\Service\Model\Table\DeliveriesModelsServiceModelTable',

            /* Security */
            '\MonarcCore\Service\SecurityService' => '\MonarcCore\Service\SecurityServiceFactory',
            /* Authentification */
            '\MonarcCore\Storage\Authentication' => '\MonarcCore\Storage\AuthentificationFactory',
            '\MonarcCore\Adapter\Authentication' => '\MonarcCore\Adapter\AuthentificationFactory',
            '\MonarcCore\Service\ConnectedUserService' => '\MonarcCore\Service\ConnectedUserServiceFactory',
            /* Translation */
            '\MonarcCore\Service\TranslateService' => '\MonarcCore\Service\TranslateServiceFactory',
        ),
        'shared' => array(
            'MonarcCore\Model\Entity\Scale' => false,
            'MonarcCore\Model\Entity\ScaleImpactType' => false,
        ),
        'initializers' =>
        [
            \MonarcCore\Service\Initializer\ObjectManagerInitializer::class
        ]
    ),
    'controllers' => array(
        'invokables' => array(),
        'factories' => array(
            /* Controller */
            '\MonarcCore\Controller\Index'                          => '\MonarcCore\Controller\IndexControllerFactory',
            '\MonarcCore\Controller\Authentication'                 => '\MonarcCore\Controller\AuthenticationControllerFactory',
            '\MonarcCore\Controller\ApiAnr'                         => '\MonarcCore\Controller\ApiAnrControllerFactory',
            '\MonarcCore\Controller\ApiAnrRisks'                    => '\MonarcCore\Controller\ApiAnrRisksControllerFactory',
            '\MonarcCore\Controller\ApiAnrRisksOp'                  => '\MonarcCore\Controller\ApiAnrRisksOpControllerFactory',
            '\MonarcCore\Controller\ApiAnrExport'                   => '\MonarcCore\Controller\ApiAnrExportControllerFactory',
            '\MonarcCore\Controller\ApiAnrInstances'                => '\MonarcCore\Controller\ApiAnrInstancesControllerFactory',
            '\MonarcCore\Controller\ApiAnrInstancesConsequences'    => '\MonarcCore\Controller\ApiAnrInstancesConsequencesControllerFactory',
            '\MonarcCore\Controller\ApiAnrInstancesRisks'           => '\MonarcCore\Controller\ApiAnrInstancesRisksControllerFactory',
            '\MonarcCore\Controller\ApiAnrInstancesRisksOp'         => '\MonarcCore\Controller\ApiAnrInstancesRisksOpControllerFactory',
            '\MonarcCore\Controller\ApiAnrLibrary'                  => '\MonarcCore\Controller\ApiAnrLibraryControllerFactory',
            '\MonarcCore\Controller\ApiAnrLibraryCategory'          => '\MonarcCore\Controller\ApiAnrLibraryCategoryControllerFactory',
            '\MonarcCore\Controller\ApiAnrObject'                   => '\MonarcCore\Controller\ApiAnrObjectControllerFactory',
            '\MonarcCore\Controller\ApiModels'                      => '\MonarcCore\Controller\ApiModelsControllerFactory',
            '\MonarcCore\Controller\ApiModelsDuplication'           => '\MonarcCore\Controller\ApiModelsDuplicationControllerFactory',
            '\MonarcCore\Controller\ApiAnrScales'                   => '\MonarcCore\Controller\ApiAnrScalesControllerFactory',
            '\MonarcCore\Controller\ApiAnrScalesTypes'              => '\MonarcCore\Controller\ApiAnrScalesTypesControllerFactory',
            '\MonarcCore\Controller\ApiAnrScalesComments'           => '\MonarcCore\Controller\ApiAnrScalesCommentsControllerFactory',
            '\MonarcCore\Controller\MailTester'                     => '\MonarcCore\Controller\MailTesterControllerFactory',
        ),
    ),

    'monarc' => array(
        'ttl' => 20, // timeout
        'doctrineLog' => false, // enable doctrine log (data/log/date('Y-m-d')-doctrine.log)
    ),

    'permissions' => array(
        'monarc',
        'auth',
        'monarc_api_admin_roles',
        'monarc_api_admin_passwords',
        'monarc_api_user_password',
        'monarc_api_config',
    ),

    'cases' => [
        'name' => 'Cases',
        'mail' => 'info@monarc.lu',
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
        'ne' => array(
            'index' => 4,
            'label' => 'Nederlands'
        ),
    ),
);
