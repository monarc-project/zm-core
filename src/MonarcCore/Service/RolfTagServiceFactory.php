<?php
namespace MonarcCore\Service;

class RolfTagServiceFactory extends AbstractServiceFactory
{
    protected $ressources = array(
        'table' => 'MonarcCore\Model\Table\RolfTagTable',
        'entity' => 'MonarcCore\Model\Entity\RolfTag',
    );
}
