<?php
namespace MonarcCore\Service;

class CountryServiceFactory extends AbstractServiceFactory
{
    protected $ressources = array(
        'table'=> 'MonarcCore\Model\Table\CountryTable',
        'entity'=> 'MonarcCore\Model\Entity\Country',
    );
}
