<?php

use Laminas\Mail\Transport\SmtpOptions;
use Laminas\Mail\Transport\Smtp;
use Laminas\Mime\Message;
use Laminas\Mime\Part;
use Doctrine\Persistence\Mapping\Driver\MappingDriverChain;
use Doctrine\ORM\Mapping\Driver\AnnotationDriver;
use Monarc\Core\Adapter\Authentication as AdapterAuthentication;
use Monarc\Core\Controller;
use Monarc\Core\Model\Db;
use Monarc\Core\Model\DbCli;
use Monarc\Core\Service\Model\DbCliFactory;
use Monarc\Core\Service\Model\DbFactory;
use Monarc\Core\Service;
use Monarc\Core\Storage\Authentication as StorageAuthentication;
use Monarc\Core\Validator\FieldValidator\LanguageValidator;
use Monarc\Core\Validator\InputValidator;
use Ramsey\Uuid\Doctrine\UuidType;
use Laminas\Di\Container\AutowireFactory;
use Monarc\Core\Model\Entity as ModelEntity;
use Monarc\Core\Model\Table as DeprecatedTable;
use Monarc\Core\Table;
use Monarc\Core\Service\Model\Entity as ServiceModelEntity;
use Laminas\ServiceManager\AbstractFactory\ReflectionBasedAbstractFactory;
use Laminas\ServiceManager\Factory\InvokableFactory;
use Laminas\ServiceManager\Proxy\LazyServiceFactory;

$env = getenv('APPLICATION_ENV') ?: 'production';
$appConfigDir = getenv('APP_CONF_DIR') ?? '';

$dataPath = './data';
if (!empty($appConfigDir)) {
    $dataPath = $appConfigDir . '/data';
}

return [
    'doctrine' => [
        'configuration' => [
            'orm_default' => [
                'types' => [
                    UuidType::NAME => UuidType::class,
                ]
            ]
        ],
        'driver' => [
            'Monarc_core_driver' => [
                'class' => AnnotationDriver::class,
                'cache' => 'array',
                'paths' => [__DIR__ . '/../src/Model/Entity'],
            ],
            'orm_default' => [
                'drivers' => [
                    'Monarc\Core\Model\Entity' => 'Monarc_core_driver',
                ],
            ],
            'Monarc_cli_driver' => [
                'class' => AnnotationDriver::class,
                'cache' => 'array',
                'paths' => [__DIR__ . '/../src/Model/Entity'],
            ],
            'orm_cli' => [
                'class' => MappingDriverChain::class,
                'drivers' => [
                    'Monarc\Core\Model\Entity' => 'Monarc_cli_driver',
                ],
            ],
            'Monarc_cli_fo_driver' => [
                'class' => AnnotationDriver::class,
                'cache' => 'array',
                'paths' => [__DIR__ . '/../src/Model/Entity'],
            ],
            'orm_cli_fo' => [
                'class' => MappingDriverChain::class,
                'drivers' => [
                    'Monarc\Core\Model\Entity' => 'Monarc_cli_fo_driver',
                ],
            ],
        ],
    ],

    'router' => [
        'routes' => [
            'monarc' => [
                'type' => 'literal',
                'verb' => 'get',
                'options' => [
                    'route' => '/',
                    'defaults' => [
                        'controller' => Controller\IndexController::class,
                        'action' => 'index',
                    ],
                ],
            ],

            'auth' => [
                'type' => 'segment',
                'options' => [
                    'route' => '/auth[/:id]',
                    'constraints' => [
                        'id' => '.+',
                    ],
                    'defaults' => [
                        'controller' => Controller\AuthenticationController::class,
                    ],
                ],
            ],

            'monarc_api_anr' => [
                'type' => 'segment',
                'options' => [
                    'route' => '/api/anr[/:id]',
                    'constraints' => [
                        'id' => '[0-9]+',
                    ],
                    'defaults' => [
                        'controller' => Controller\ApiAnrController::class,
                    ],
                ],
            ],
            'monarc_api_anr_export' => [
                'type' => 'segment',
                'options' => [
                    'route' => '/api/anr-export',
                    'defaults' => [
                        'controller' => Controller\ApiAnrExportController::class,
                    ],
                ],
            ],

            'monarc_api_anr_risk_owners' => [
                'type' => 'segment',
                'options' => [
                    'route' => '/api/anr/:anrid/risk-owners[/:id]',
                    'constraints' => [
                        'id' => '[0-9]+',
                    ],
                    'defaults' => [
                        'controller' => Controller\ApiAnrRiskOwnersController::class,
                    ],
                ],
            ],

            'monarc_api_anr_instances_consequences' => [
                'type' => 'segment',
                'options' => [
                    'route' => '/api/anr/:anrid/instances-consequences[/:id]',
                    'constraints' => [
                        'id' => '[0-9]+',
                    ],
                    'defaults' => [
                        'controller' => Controller\ApiAnrInstancesConsequencesController::class,
                    ],
                ],
            ],

            'monarc_api_anr_risks' => [
                'type' => 'segment',
                'options' => [
                    'route' => '/api/anr/:anrid/risks[/:id]',
                    'constraints' => [
                        'anrid' => '[0-9]+',
                        'id' => '[0-9]+',
                    ],
                    'defaults' => [
                        'controller' => Controller\ApiAnrRisksController::class,
                    ],
                ],
            ],

            'monarc_api_anr_risks_op' => [
                'type' => 'segment',
                'options' => [
                    'route' => '/api/anr/:anrid/risksop[/:id]',
                    'defaults' => [
                        'controller' => Controller\ApiAnrRisksOpController::class,
                    ],
                ],
            ],

            'monarc_api_anr_instances' => [
                'type' => 'segment',
                'options' => [
                    'route' => '/api/anr/:anrid/instances[/:id]',
                    'constraints' => [
                        'id' => '[0-9]+',
                    ],
                    'defaults' => [
                        'controller' => Controller\ApiAnrInstancesController::class,
                    ],
                ],
            ],

            'monarc_api_anr_instances_export' => [
                'type' => 'segment',
                'options' => [
                    'route' => '/api/anr/:anrid/instances/:id/export',
                    'constraints' => [
                        'id' => '[0-9]+',
                    ],
                    'defaults' => [
                        'controller' => Controller\ApiAnrInstancesController::class,
                        'action' => 'export'
                    ],
                ],
            ],

            'monarc_api_anr_instances_risks' => [
                'type' => 'segment',
                'options' => [
                    'route' => '/api/anr/:anrid/instances-risks[/:id]',
                    'constraints' => [
                        'id' => '[0-9]+',
                    ],
                    'defaults' => [
                        'controller' => Controller\ApiAnrInstancesRisksController::class,
                    ],
                ],
            ],

            'monarc_api_anr_instances_risksop' => [
                'type' => 'segment',
                'options' => [
                    'route' => '/api/anr/:anrid/instances-oprisks[/:id]',
                    'constraints' => [
                        'id' => '[0-9]+',
                    ],
                    'defaults' => [
                        'controller' => Controller\ApiAnrInstancesRisksOpController::class,
                    ],
                ],
            ],

            'monarc_api_models_duplication' => [
                'type' => 'segment',
                'options' => [
                    'route' => '/api/models-duplication[/:id]',
                    'constraints' => [
                        'id' => '[0-9]+',
                    ],
                    'defaults' => [
                        'controller' => Controller\ApiModelsDuplicationController::class,
                    ],
                ],
            ],

            'monarc_api_scales' => [
                'type' => 'segment',
                'options' => [
                    'route' => '/api/anr/:anrId/scales[/:id]',
                    'constraints' => [
                        'id' => '[0-9]+',
                    ],
                    'defaults' => [
                        'controller' => Controller\ApiAnrScalesController::class,
                    ],
                ],
            ],

            'monarc_api_operational_scales' => [
                'type' => 'segment',
                'options' => [
                    'route' => '/api/anr/:anrId/operational-scales[/:id]',
                    'constraints' => [
                        'id' => '[0-9]+',
                    ],
                    'defaults' => [
                        'controller' => Controller\ApiOperationalRisksScalesController::class,
                    ],
                ],
            ],

            'monarc_api_operational_scales_comment' => [
                'type' => 'segment',
                'options' => [
                    'route' => '/api/anr/:anrId/operational-scales/:scaleid/comments[/:id]',
                    'constraints' => [
                        'id' => '[0-9]+',
                        'scaleid' => '[0-9]+',
                    ],
                    'defaults' => [
                        'controller' => Controller\ApiOperationalRisksScalesCommentsController::class,
                    ],
                ],
            ],

            'monarc_api_scales_comments' => [
                'type' => 'segment',
                'options' => [
                    'route' => '/api/anr/:anrId/scales/:scaleId/comments[/:id]',
                    'constraints' => [
                        'anrId' => '[0-9]+',
                        'scaleId' => '[0-9]+',
                        'id' => '[0-9]+',
                    ],
                    'defaults' => [
                        'controller' => Controller\ApiAnrScalesCommentsController::class,
                    ],
                ],
            ],

            'monarc_api_scales_types' => [
                'type' => 'segment',
                'options' => [
                    'route' => '/api/anr/:anrId/scales-types[/:id]',
                    'constraints' => [
                        'id' => '[0-9]+',
                    ],
                    'defaults' => [
                        'controller' => Controller\ApiAnrScalesTypesController::class,
                    ],
                ],
            ],
        ],
    ],

    'view_manager' => [
        'strategies' => [
            'ViewJsonStrategy',
        ],
    ],

    'service_manager' => [
        'invokables' => [
            ModelEntity\Question::class => ModelEntity\Question::class,
            ModelEntity\QuestionChoice::class => ModelEntity\QuestionChoice::class,

            // TODO: fix the classes and dependencies.
            'Monarc\Core\Service\Mime\Part' => Part::class,
            'Monarc\Core\Service\Mime\Message' => Message::class,
            'Monarc\Core\Service\Mail\Message' => \Laminas\Mail\Message::class,
            'Monarc\Core\Service\Mail\Transport\Smtp' => Smtp::class,
            'Monarc\Core\Service\Mail\Transport\SmtpOptions' => SmtpOptions::class,
        ],
        'factories' => [
            Db::class => DbFactory::class,
            DbCli::class => DbCliFactory::class,

            // TODO: replace to autowiring.
            Service\AmvService::class => AutowireFactory::class,
            Service\AnrService::class => Service\AnrServiceFactory::class,
            Service\UserRoleService::class => AutowireFactory::class,
            Service\UserProfileService::class => AutowireFactory::class,
            Service\AuthenticationService::class => AutowireFactory::class,
            Service\AssetService::class => AutowireFactory::class,
            Service\AssetExportService::class => Service\AssetExportServiceFactory::class,
            Service\ConfigService::class => ReflectionBasedAbstractFactory::class,
            Service\QuestionService::class => Service\QuestionServiceFactory::class,
            Service\QuestionChoiceService::class => Service\QuestionChoiceServiceFactory::class,
            Service\GuideService::class => Service\GuideServiceFactory::class,
            Service\GuideItemService::class => Service\GuideItemServiceFactory::class,
            Service\HistoricalService::class => Service\HistoricalServiceFactory::class,
            Service\InstanceService::class => Service\InstanceServiceFactory::class,
            Service\InstanceRiskService::class => Service\InstanceRiskServiceFactory::class,
            Service\InstanceRiskOpService::class => AutowireFactory::class,
            Service\InstanceConsequenceService::class => Service\InstanceConsequenceServiceFactory::class,
            Service\MailService::class => AutowireFactory::class,
            Service\ReferentialService::class => Service\ReferentialServiceFactory::class,
            Service\MeasureService::class => Service\MeasureServiceFactory::class,
            Service\MeasureMeasureService::class => Service\MeasureMeasureServiceFactory::class,
            Service\ModelService::class => AutowireFactory::class,
            Service\ObjectService::class => AutowireFactory::class,
            Service\ObjectExportService::class => AutowireFactory::class,
            Service\ObjectCategoryService::class => AutowireFactory::class,
            Service\ObjectObjectService::class => AutowireFactory::class,
            Service\PasswordService::class => AutowireFactory::class,
            Service\RolfRiskService::class => Service\RolfRiskServiceFactory::class,
            Service\RolfTagService::class => Service\RolfTagServiceFactory::class,
            Service\RoleService::class => Service\RoleServiceFactory::class,
            Service\ScaleService::class => Service\ScaleServiceFactory::class,
            Service\ScaleCommentService::class => Service\ScaleCommentServiceFactory::class,
            Service\ScaleImpactTypeService::class => Service\ScaleImpactTypeServiceFactory::class,
            Service\ThemeService::class => AutowireFactory::class,
            Service\ThreatService::class => AutowireFactory::class,
            Service\SoaCategoryService::class => Service\SoaCategoryServiceFactory::class,
            Service\UserService::class => ReflectionBasedAbstractFactory::class,
            Service\VulnerabilityService::class => AutowireFactory::class,
            Service\DeliveriesModelsService::class => Service\DeliveriesModelsServiceFactory::class,
            Service\AssetImportService::class => AutowireFactory::class,
            Service\ObjectImportService::class => AutowireFactory::class,
            Service\OperationalRiskScaleService::class => AutowireFactory::class,
            Service\OperationalRiskScaleCommentService::class => AutowireFactory::class,
            Service\OperationalRiskScalesExportService::class => AutowireFactory::class,

            // TODO: Entities are created from the code. Should be removed.
            ModelEntity\DeliveriesModels::class => ServiceModelEntity\DeliveriesModelsServiceModelEntity::class,
            ModelEntity\Referential::class => ServiceModelEntity\ReferentialServiceModelEntity::class,
            ModelEntity\Measure::class => ServiceModelEntity\MeasureServiceModelEntity::class,
            ModelEntity\MeasureMeasure::class => ServiceModelEntity\MeasureMeasureServiceModelEntity::class,
            ModelEntity\SoaCategory::class => ServiceModelEntity\SoaCategoryServiceModelEntity::class,
            ModelEntity\MonarcObject::class => ServiceModelEntity\MonarcObjectServiceModelEntity::class,
            ModelEntity\Instance::class => ServiceModelEntity\InstanceServiceModelEntity::class,
            ModelEntity\InstanceConsequence::class => ServiceModelEntity\InstanceConsequenceServiceModelEntity::class,
            ModelEntity\InstanceRisk::class => ServiceModelEntity\InstanceRiskServiceModelEntity::class,
            ModelEntity\InstanceRiskOp::class => ServiceModelEntity\InstanceRiskOpServiceModelEntity::class,
            ModelEntity\ObjectCategory::class => ServiceModelEntity\ObjectCategoryServiceModelEntity::class,
            ModelEntity\RolfRisk::class => ServiceModelEntity\RolfRiskServiceModelEntity::class,
            ModelEntity\RolfTag::class => ServiceModelEntity\RolfTagServiceModelEntity::class,
            ModelEntity\GuideItem::class => ServiceModelEntity\GuideItemServiceModelEntity::class,
            ModelEntity\Anr::class => ServiceModelEntity\AnrServiceModelEntity::class,
            ModelEntity\AnrObjectCategory::class => ServiceModelEntity\AnrObjectCategoryServiceModelEntity::class,
            ModelEntity\Guide::class => ServiceModelEntity\GuideServiceModelEntity::class,
            ModelEntity\Historical::class => ServiceModelEntity\HistoricalServiceModelEntity::class,
            ModelEntity\ObjectObject::class => ServiceModelEntity\ObjectObjectServiceModelEntity::class,
            ModelEntity\Scale::class => ServiceModelEntity\ScaleServiceModelEntity::class,
            ModelEntity\ScaleComment::class => ServiceModelEntity\ScaleCommentServiceModelEntity::class,
            ModelEntity\ScaleImpactType::class => ServiceModelEntity\ScaleImpactTypeServiceModelEntity::class,

            Table\ModelTable::class => Table\Factory\CoreEntityManagerFactory::class,
            DeprecatedTable\AnrTable::class => AutowireFactory::class,
            Table\AnrObjectCategoryTable::class => Table\Factory\CoreEntityManagerFactory::class,
            DeprecatedTable\QuestionTable::class => AutowireFactory::class,
            DeprecatedTable\QuestionChoiceTable::class => AutowireFactory::class,
            DeprecatedTable\GuideTable::class => AutowireFactory::class,
            DeprecatedTable\GuideItemTable::class => AutowireFactory::class,
            DeprecatedTable\ReferentialTable::class => AutowireFactory::class,
            DeprecatedTable\MeasureTable::class => AutowireFactory::class,
            DeprecatedTable\MeasureMeasureTable::class => AutowireFactory::class,
            DeprecatedTable\SoaCategoryTable::class => AutowireFactory::class,
            Table\MonarcObjectTable::class => Table\Factory\CoreEntityManagerFactory::class,
            DeprecatedTable\InstanceTable::class => AutowireFactory::class,
            DeprecatedTable\InstanceConsequenceTable::class => AutowireFactory::class,
            DeprecatedTable\InstanceRiskTable::class => AutowireFactory::class,
            DeprecatedTable\InstanceRiskOpTable::class => AutowireFactory::class,
            Table\ObjectCategoryTable::class => Table\Factory\CoreEntityManagerFactory::class,
            DeprecatedTable\ObjectObjectTable::class => AutowireFactory::class,
            Table\ThemeTable::class => Table\Factory\CoreEntityManagerFactory::class,
            DeprecatedTable\HistoricalTable::class => AutowireFactory::class,
            Table\AssetTable::class => Table\Factory\CoreEntityManagerFactory::class,
            Table\AmvTable::class => Table\Factory\CoreEntityManagerFactory::class,
            Table\ThreatTable::class => Table\Factory\CoreEntityManagerFactory::class,
            DeprecatedTable\RolfTagTable::class => AutowireFactory::class,
            DeprecatedTable\RolfRiskTable::class => AutowireFactory::class,
            DeprecatedTable\ScaleTable::class => AutowireFactory::class,
            DeprecatedTable\ScaleCommentTable::class => AutowireFactory::class,
            DeprecatedTable\ScaleImpactTypeTable::class => AutowireFactory::class,
            Table\VulnerabilityTable::class => Table\Factory\CoreEntityManagerFactory::class,
            DeprecatedTable\DeliveriesModelsTable::class => AutowireFactory::class,
            Table\OperationalRiskScaleTable::class => Table\Factory\CoreEntityManagerFactory::class,
            Table\OperationalRiskScaleTypeTable::class => Table\Factory\CoreEntityManagerFactory::class,
            Table\OperationalRiskScaleCommentTable::class => Table\Factory\CoreEntityManagerFactory::class,
            Table\OperationalInstanceRiskScaleTable::class => Table\Factory\CoreEntityManagerFactory::class,
            Table\InstanceRiskOwnerTable::class => Table\Factory\CoreEntityManagerFactory::class,
            Table\UserTable::class => Table\Factory\ClientEntityManagerFactory::class,
            Table\UserTokenTable::class => Table\Factory\ClientEntityManagerFactory::class,
            Table\PasswordTokenTable::class => Table\Factory\ClientEntityManagerFactory::class,

            /* Authentication */
            StorageAuthentication::class => ReflectionBasedAbstractFactory::class,
            AdapterAuthentication::class => AutowireFactory::class,
            Service\ConnectedUserService::class => AutowireFactory::class,
            /* Translation */
            Service\TranslateService::class => Service\TranslateServiceFactory::class,

            /* Validators */
            InputValidator\User\PostUserDataInputValidator::class => ReflectionBasedAbstractFactory::class,
            InputValidator\Model\PostModelDataInputValidator::class => ReflectionBasedAbstractFactory::class,
            InputValidator\Asset\PostAssetDataInputValidator::class => ReflectionBasedAbstractFactory::class,
            InputValidator\Threat\PostThreatDataInputValidator::class => ReflectionBasedAbstractFactory::class,
            InputValidator\Vulnerability\PostVulnerabilityDataInputValidator::class =>
                ReflectionBasedAbstractFactory::class,
            InputValidator\Amv\PostAmvDataInputValidator::class => ReflectionBasedAbstractFactory::class,
            InputValidator\Theme\PostThemeDataInputValidator::class => ReflectionBasedAbstractFactory::class,
            InputValidator\Object\PostObjectDataInputValidator::class => ReflectionBasedAbstractFactory::class,
            InputValidator\ObjectCategory\PostObjectCategoryDataInputValidator::class =>
                ReflectionBasedAbstractFactory::class,
            LanguageValidator::class => ReflectionBasedAbstractFactory::class,
        ],
        'shared' => [
            ModelEntity\Scale::class => false,
            ModelEntity\ScaleImpactType::class => false,
        ],
        'lazy_services' => [
            'class_map' => [
                Table\UserTokenTable::class => Table\UserTokenTable::class,
                Service\AssetService::class => Service\AssetService::class,
                Service\ThreatService::class => Service\ThreatService::class,
                Service\VulnerabilityService::class => Service\VulnerabilityService::class,
            ],
            'proxies_target_dir' => $dataPath . '/LazyServices/Proxy',
            'write_proxy_files' => $env === 'production',
        ],
        'delegators' => [
            Table\UserTokenTable::class => [
                LazyServiceFactory::class,
            ],
            Service\AssetService::class => [
                LazyServiceFactory::class,
            ],
            Service\ThreatService::class => [
                LazyServiceFactory::class,
            ],
            Service\VulnerabilityService::class => [
                LazyServiceFactory::class,
            ],
        ],
    ],
    'controllers' => [
        'factories' => [
            // TODO: replace to AutowireFactory before refactor the service injection.
            Controller\IndexController::class => InvokableFactory::class,
            Controller\AuthenticationController::class => AutowireFactory::class,
            Controller\ApiAnrController::class => Controller\ApiAnrControllerFactory::class,
            Controller\ApiAnrRiskOwnersController::class => AutowireFactory::class,
            Controller\ApiAnrRisksController::class => AutowireFactory::class,
            Controller\ApiAnrRisksOpController::class => AutowireFactory::class,
            Controller\ApiAnrExportController::class => AutowireFactory::class,
            Controller\ApiAnrInstancesController::class => Controller\ApiAnrInstancesControllerFactory::class,
            Controller\ApiAnrInstancesConsequencesController::class
                => Controller\ApiAnrInstancesConsequencesControllerFactory::class,
            Controller\ApiAnrInstancesRisksController::class => Controller\ApiAnrInstancesRisksControllerFactory::class,
            Controller\ApiAnrInstancesRisksOpController::class => AutowireFactory::class,
            Controller\ApiModelsDuplicationController::class => AutowireFactory::class,
            Controller\ApiAnrScalesController::class => Controller\ApiAnrScalesControllerFactory::class,
            Controller\ApiAnrScalesTypesController::class => Controller\ApiAnrScalesTypesControllerFactory::class,
            Controller\ApiAnrScalesCommentsController::class => Controller\ApiAnrScalesCommentsControllerFactory::class,
            Controller\ApiOperationalRisksScalesController::class => AutowireFactory::class,
            Controller\ApiOperationalRisksScalesCommentsController::class => AutowireFactory::class,
        ],
    ],

    'monarc' => [
        'ttl' => 20, // Authentication tokens lifetime.
        'doctrineLog' => false, // enable doctrine log (data/log/date('Y-m-d')-doctrine.log)
    ],

    'permissions' => [
        'monarc',
        'auth',
        'monarc_api_admin_roles',
        'monarc_api_admin_passwords',
        'monarc_api_admin_user_reset_password',
        'monarc_api_user_password',
        'monarc_api_config',
    ],

    'cases' => [
        'name' => 'Cases',
        'mail' => 'info@monarc.lu',
    ],

    'defaultLanguageIndex' => 1,
    'languages' => [
        'fr' => [
            'index' => 1,
            'label' => 'Français',
        ],
        'en' => [
            'index' => 2,
            'label' => 'English',
        ],
        'de' => [
            'index' => 3,
            'label' => 'Deutsch',
        ],
        'nl' => [
            'index' => 4,
            'label' => 'Nederlands',
        ],
        'es' => [
            'index' => 5,
            'label' => 'Spanish',
        ],
        'ro' => [
            'index' => 6,
            'label' => 'Romanian',
        ],
        'it' => [
            'index' => 7,
            'label' => 'Italian',
        ],
        'ja' => [
            'index' => 8,
            'label' => 'Japanese',
        ],
        'pl' => [
            'index' => 9,
            'label' => 'Polish',
        ],
        'pt' => [
            'index' => 10,
            'label' => 'Portuguese',
        ],
        'zh' => [
            'index' => 11,
            'label' => 'Chinese',
        ],
    ],

    'dependencies' => [
        'auto' => [
            'aot' => [
                'namespace' => 'AppAoT\Generated',
                'directory' => __DIR__ . '/../src/var/dependencies',
            ],
        ],
    ],
];
