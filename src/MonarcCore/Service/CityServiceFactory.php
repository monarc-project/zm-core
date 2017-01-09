<?php
namespace MonarcCore\Service;

class CityServiceFactory extends AbstractServiceFactory
{
    protected $ressources = array(
        'table'=> 'MonarcCore\Model\Table\CityTable',
        'entity'=> 'MonarcCore\Model\Entity\City',
    );
}
