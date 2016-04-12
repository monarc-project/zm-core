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
        ),
    ),

    'view_manager' => array(
        'strategies' => array(
            'ViewJsonStrategy',
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
);