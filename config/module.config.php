<?php declare(strict_types=1);
/**
 * @link      https://github.com/monarc-project for the canonical source repository
 * @copyright Copyright (c) 2016-2022 SMILE GIE Securitymadein.lu - Licensed under GNU Affero GPL v3
 * @license   MONARC is licensed under GNU Affero General Public License version 3
 */

use Interop\Container\Containerinterface;
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
$dataPath = './data';
if (defined('DATA_PATH')) {
    $dataPath = DATA_PATH;
} elseif (!empty(getenv('APP_CONF_DIR'))) {
    $dataPath = getenv('APP_CONF_DIR') . '/data';
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
        ],
    ],

    'view_manager' => [
        'strategies' => [
            'ViewJsonStrategy',
        ],
    ],
    'controllers' => [
        'factories' => [
            Controller\IndexController::class => InvokableFactory::class,
            Controller\AuthenticationController::class => AutowireFactory::class,
        ],
    ],
    'service_manager' => [
        'invokables' => [
            ModelEntity\Question::class => ModelEntity\Question::class,
            ModelEntity\QuestionChoice::class => ModelEntity\QuestionChoice::class,
        ],
        'factories' => [
            Db::class => DbFactory::class,
            DbCli::class => DbCliFactory::class,

            // TODO: Services to refactor and replace with autowiring.
            Service\QuestionService::class => Service\QuestionServiceFactory::class,
            Service\QuestionChoiceService::class => Service\QuestionChoiceServiceFactory::class,
            Service\GuideService::class => Service\GuideServiceFactory::class,
            Service\GuideItemService::class => Service\GuideItemServiceFactory::class,
            Service\HistoricalService::class => Service\HistoricalServiceFactory::class,
            Service\RolfRiskService::class => Service\RolfRiskServiceFactory::class,
            Service\RolfTagService::class => Service\RolfTagServiceFactory::class,
            Service\ReferentialService::class => Service\ReferentialServiceFactory::class,
            Service\MeasureService::class => Service\MeasureServiceFactory::class,
            Service\MeasureMeasureService::class => Service\MeasureMeasureServiceFactory::class,
            Service\SoaCategoryService::class => Service\SoaCategoryServiceFactory::class,
            Service\DeliveriesModelsService::class => Service\DeliveriesModelsServiceFactory::class,
            /* Services. */
            Service\AmvService::class => AutowireFactory::class,
            Service\AnrService::class => AutowireFactory::class,
            Service\UserRoleService::class => AutowireFactory::class,
            Service\UserProfileService::class => AutowireFactory::class,
            Service\AuthenticationService::class => AutowireFactory::class,
            Service\AssetService::class => AutowireFactory::class,
            Service\ConfigService::class => ReflectionBasedAbstractFactory::class,
            Service\InstanceService::class => AutowireFactory::class,
            Service\InstanceRiskService::class => AutowireFactory::class,
            Service\InstanceRiskOpService::class => AutowireFactory::class,
            Service\InstanceConsequenceService::class => AutowireFactory::class,
            Service\MailService::class => AutowireFactory::class,
            Service\ModelService::class => AutowireFactory::class,
            Service\ObjectService::class => AutowireFactory::class,
            Service\ObjectCategoryService::class => AutowireFactory::class,
            Service\ObjectObjectService::class => AutowireFactory::class,
            Service\PasswordService::class => AutowireFactory::class,
            Service\ScaleService::class => AutowireFactory::class,
            Service\ScaleCommentService::class => AutowireFactory::class,
            Service\ScaleImpactTypeService::class => AutowireFactory::class,
            Service\ThemeService::class => AutowireFactory::class,
            Service\ThreatService::class => AutowireFactory::class,
            Service\UserService::class => ReflectionBasedAbstractFactory::class,
            Service\VulnerabilityService::class => AutowireFactory::class,
            Service\AssetImportService::class => AutowireFactory::class,
            Service\ObjectImportService::class => AutowireFactory::class,
            Service\OperationalRiskScaleService::class => AutowireFactory::class,
            Service\OperationalRiskScaleCommentService::class => AutowireFactory::class,
            Service\AnrInstanceMetadataFieldService::class => AutowireFactory::class,
            Service\SoaScaleCommentService::class => AutowireFactory::class,
            /* Export services. */
            Service\Export\AssetExportService::class => AutowireFactory::class,
            Service\Export\ObjectExportService::class => AutowireFactory::class,
            Service\Export\AmvExportService::class => InvokableFactory::class,

            // TODO: Entities are created in a generic way. Should be removed.
            ModelEntity\DeliveriesModels::class => ServiceModelEntity\DeliveriesModelsServiceModelEntity::class,
            ModelEntity\Referential::class => ServiceModelEntity\ReferentialServiceModelEntity::class,
            ModelEntity\Measure::class => ServiceModelEntity\MeasureServiceModelEntity::class,
            ModelEntity\MeasureMeasure::class => ServiceModelEntity\MeasureMeasureServiceModelEntity::class,
            ModelEntity\SoaCategory::class => ServiceModelEntity\SoaCategoryServiceModelEntity::class,
            ModelEntity\RolfRisk::class => ServiceModelEntity\RolfRiskServiceModelEntity::class,
            ModelEntity\RolfTag::class => ServiceModelEntity\RolfTagServiceModelEntity::class,
            ModelEntity\GuideItem::class => ServiceModelEntity\GuideItemServiceModelEntity::class,
            ModelEntity\Guide::class => ServiceModelEntity\GuideServiceModelEntity::class,
            ModelEntity\Historical::class => ServiceModelEntity\HistoricalServiceModelEntity::class,

            /* Table classes */
            DeprecatedTable\QuestionTable::class => AutowireFactory::class,
            DeprecatedTable\QuestionChoiceTable::class => AutowireFactory::class,
            DeprecatedTable\GuideTable::class => AutowireFactory::class,
            DeprecatedTable\GuideItemTable::class => AutowireFactory::class,
            DeprecatedTable\ReferentialTable::class => AutowireFactory::class,
            DeprecatedTable\MeasureTable::class => AutowireFactory::class,
            DeprecatedTable\MeasureMeasureTable::class => AutowireFactory::class,
            DeprecatedTable\SoaCategoryTable::class => AutowireFactory::class,
            DeprecatedTable\HistoricalTable::class => AutowireFactory::class,
            DeprecatedTable\RolfTagTable::class => AutowireFactory::class,
            DeprecatedTable\RolfRiskTable::class => AutowireFactory::class,
            DeprecatedTable\DeliveriesModelsTable::class => AutowireFactory::class,
            Table\InstanceRiskTable::class => Table\Factory\CoreEntityManagerFactory::class,
            Table\InstanceRiskOpTable::class => Table\Factory\CoreEntityManagerFactory::class,
            Table\ScaleTable::class => Table\Factory\CoreEntityManagerFactory::class,
            Table\ScaleCommentTable::class => Table\Factory\CoreEntityManagerFactory::class,
            Table\ScaleImpactTypeTable::class => Table\Factory\CoreEntityManagerFactory::class,
            Table\AnrTable::class => Table\Factory\CoreEntityManagerFactory::class,
            Table\InstanceConsequenceTable::class => Table\Factory\CoreEntityManagerFactory::class,
            Table\ModelTable::class => Table\Factory\CoreEntityManagerFactory::class,
            Table\MonarcObjectTable::class => Table\Factory\CoreEntityManagerFactory::class,
            Table\InstanceTable::class => Table\Factory\CoreEntityManagerFactory::class,
            Table\ObjectCategoryTable::class => Table\Factory\CoreEntityManagerFactory::class,
            Table\ObjectObjectTable::class => Table\Factory\CoreEntityManagerFactory::class,
            Table\ThemeTable::class => Table\Factory\CoreEntityManagerFactory::class,
            Table\AssetTable::class => Table\Factory\CoreEntityManagerFactory::class,
            Table\AmvTable::class => Table\Factory\CoreEntityManagerFactory::class,
            Table\ThreatTable::class => Table\Factory\CoreEntityManagerFactory::class,
            Table\VulnerabilityTable::class => Table\Factory\CoreEntityManagerFactory::class,
            Table\OperationalRiskScaleTable::class => Table\Factory\CoreEntityManagerFactory::class,
            Table\OperationalRiskScaleTypeTable::class => Table\Factory\CoreEntityManagerFactory::class,
            Table\OperationalRiskScaleCommentTable::class => Table\Factory\CoreEntityManagerFactory::class,
            Table\OperationalInstanceRiskScaleTable::class => Table\Factory\CoreEntityManagerFactory::class,
            Table\AnrInstanceMetadataFieldTable::class => Table\Factory\CoreEntityManagerFactory::class,
            Table\SoaScaleCommentTable::class => Table\Factory\CoreEntityManagerFactory::class,
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
            InputValidator\InputValidationTranslator::class => ReflectionBasedAbstractFactory::class,
            InputValidator\User\PostUserDataInputValidator::class => ReflectionBasedAbstractFactory::class,
            InputValidator\Model\PostModelDataInputValidator::class => ReflectionBasedAbstractFactory::class,
            InputValidator\Asset\PostAssetDataInputValidator::class => static function (
                Containerinterface $container,
                $serviceName
            ) {
                return new InputValidator\Asset\PostAssetDataInputValidator(
                    $container->get('config'),
                    $container->get(InputValidator\InputValidationTranslator::class),
                    $container->get(Table\AssetTable::class)
                );
            },
            InputValidator\Threat\PostThreatDataInputValidator::class => static function (
                Containerinterface $container,
                $serviceName
            ) {
                return new InputValidator\Threat\PostThreatDataInputValidator(
                    $container->get('config'),
                    $container->get(InputValidator\InputValidationTranslator::class),
                    $container->get(Table\ThemeTable::class)
                );
            },
            InputValidator\Vulnerability\PostVulnerabilityDataInputValidator::class => static function (
                Containerinterface $container,
                $serviceName
            ) {
                return new InputValidator\Vulnerability\PostVulnerabilityDataInputValidator(
                    $container->get('config'),
                    $container->get(InputValidator\InputValidationTranslator::class),
                    $container->get(Table\VulnerabilityTable::class)
                );
            },
            InputValidator\Amv\PostAmvDataInputValidator::class => ReflectionBasedAbstractFactory::class,
            InputValidator\Theme\PostThemeDataInputValidator::class => ReflectionBasedAbstractFactory::class,
            InputValidator\Object\PostObjectDataInputValidator::class => ReflectionBasedAbstractFactory::class,
            InputValidator\ObjectCategory\PostObjectCategoryDataInputValidator::class =>
                ReflectionBasedAbstractFactory::class,
            LanguageValidator::class => ReflectionBasedAbstractFactory::class,
            InputValidator\ObjectComposition\CreateDataInputValidator::class => ReflectionBasedAbstractFactory::class,
            InputValidator\ObjectComposition\MovePositionDataInputValidator::class =>
                ReflectionBasedAbstractFactory::class,
            InputValidator\Instance\CreateInstanceDataInputValidator::class => ReflectionBasedAbstractFactory::class,
            InputValidator\Instance\UpdateInstanceDataInputValidator::class => ReflectionBasedAbstractFactory::class,
            InputValidator\Instance\PatchInstanceDataInputValidator::class => ReflectionBasedAbstractFactory::class,
            InputValidator\InstanceConsequence\PatchConsequenceDataInputValidator::class =>
                ReflectionBasedAbstractFactory::class,
            InputValidator\InstanceRisk\UpdateInstanceRiskDataInputValidator::class =>
                ReflectionBasedAbstractFactory::class,
            InputValidator\Anr\PatchThresholdsDataInputValidator::class => ReflectionBasedAbstractFactory::class,
            InputValidator\Profile\PatchProfileDataInputValidator::class => ReflectionBasedAbstractFactory::class,
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
                Service\InstanceService::class => Service\InstanceService::class,
                Service\AmvService::class => Service\AmvService::class,
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
            Service\InstanceService::class => [
                LazyServiceFactory::class,
            ],
            Service\AmvService::class => [
                LazyServiceFactory::class,
            ],
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
