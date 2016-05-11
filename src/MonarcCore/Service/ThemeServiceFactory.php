<?php
namespace MonarcCore\Service;

class ThemeServiceFactory extends AbstractServiceFactory
{
    protected $ressources = array(
        'table'=> 'MonarcCore\Model\Table\ThemeTable',
        'entity'=> 'MonarcCore\Model\Entity\Theme',
    );
}