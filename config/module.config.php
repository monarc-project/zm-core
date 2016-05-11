<?php
return array(
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
            'MonarcCore\Model\Entity\Asset'         => 'MonarcCore\Model\Entity\Asset',
            'MonarcCore\Model\Entity\Model'         => 'MonarcCore\Model\Entity\Model',
            'MonarcCore\Model\Entity\Role'          => 'MonarcCore\Model\Entity\Role',
            'MonarcCore\Model\Entity\Theme'         => 'MonarcCore\Model\Entity\Theme',
            'MonarcCore\Model\Entity\UserRole'      => 'MonarcCore\Model\Entity\UserRole',
            'MonarcCore\Model\Entity\Vulnerability' => 'MonarcCore\Model\Entity\Vulnerability',
            'MonarcCore\Service\Mime\Part'          => 'Zend\Mime\Part',
            'MonarcCore\Service\Mime\Message'       => 'Zend\Mime\Message',
            'MonarcCore\Service\Mail\Message'       => 'Zend\Mail\Message',
            'MonarcCore\Service\Mail\Transport\Smtp'        => 'Zend\Mail\Transport\Smtp',
            'MonarcCore\Service\Mail\Transport\SmtpOptions' => 'Zend\Mail\Transport\SmtpOptions'
        ),
        'factories' => array(
            'MonarcCore\Service\AssetService'           => 'MonarcCore\Service\AssetServiceFactory',
            'MonarcCore\Service\AuthenticationService'  => 'MonarcCore\Service\AuthenticationServiceFactory',
            'MonarcCore\Service\IndexService'           => 'MonarcCore\Service\IndexServiceFactory',
            'MonarcCore\Service\MailService'            => 'MonarcCore\Service\MailServiceFactory',
            'MonarcCore\Service\ModelService'           => 'MonarcCore\Service\ModelServiceFactory',
            'MonarcCore\Service\PasswordService'        => 'MonarcCore\Service\PasswordServiceFactory',
            'MonarcCore\Service\RoleService'            => 'MonarcCore\Service\RoleServiceFactory',
            'MonarcCore\Service\ThemeService'           => 'MonarcCore\Service\ThemeServiceFactory',
            'MonarcCore\Service\ThreatService'          => 'MonarcCore\Service\ThreatServiceFactory',
            'MonarcCore\Service\UserRoleService'        => 'MonarcCore\Service\UserRoleServiceFactory',
            'MonarcCore\Service\UserService'            => 'MonarcCore\Service\UserServiceFactory',
            'MonarcCore\Service\VulnerabilityService'   => 'MonarcCore\Service\VulnerabilityServiceFactory',
        ),
        'initializers' =>
        [
            \MonarcCore\Service\Initializer\ObjectManagerInitializer::class
        ]
    ),

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
        ),
    ),

    'console' => array(
        'router' => array(
            'routes' => array(
            ),
        ),
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
        'monarc_api_themes',
    ),

    'cases' => [
        'name' => 'Cases',
        'mail' => 'info@cases.lu',
    ]
);