<?php
namespace MonarcCore\Service;

class ObjectCategoryServiceFactory extends AbstractServiceFactory
{
    protected $ressources = array(
        'table'=> '\MonarcCore\Model\Table\ObjectCategoryTable',
        'entity'=> '\MonarcCore\Model\Entity\ObjectCategory',
        'anrObjectCategoryTable'=> '\MonarcCore\Model\Table\AnrObjectCategoryTable',
        'objectTable' => '\MonarcCore\Model\Table\ObjectTable',
        'rootTable' => 'MonarcCore\Model\Table\ObjectCategoryTable',
        'parentTable' => 'MonarcCore\Model\Table\ObjectCategoryTable',
        'anrTable' => 'MonarcCore\Model\Table\AnrTable',
    );

}
