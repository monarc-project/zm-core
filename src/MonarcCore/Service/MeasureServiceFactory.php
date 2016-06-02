<?php
namespace MonarcCore\Service;

class MeasureServiceFactory extends AbstractServiceFactory
{
    protected $ressources = array(
        'table'=> 'MonarcCore\Model\Table\MeasureTable',
        'entity'=> 'MonarcCore\Model\Entity\Measure',
    );
}