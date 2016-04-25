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
        'invokables' => array(
            'MonarcCore\Service\Mime\Part' => 'Zend\Mime\Part',
            'MonarcCore\Service\Mime\Message' => 'Zend\Mime\Message',
            'MonarcCore\Service\Mail\Message' => 'Zend\Mail\Message',
            'MonarcCore\Service\Mail\Transport\Smtp' => 'Zend\Mail\Transport\Smtp',
            'MonarcCore\Service\Mail\Transport\SmtpOptions' => 'Zend\Mail\Transport\SmtpOptions'
        ),
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
        'home',
        'auth',
        'monarc_api_admin_roles',
<<<<<<< HEAD
        'monarc_api_admin_passwords',
    ),

    'cases' => [
        'name' => 'Cases',
        'mail' => 'info@cases.lu',
    ]
=======
        'home',
    )
>>>>>>> ad640f20274e670f9094992577f2a3112e27bbe1
);