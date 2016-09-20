<?php
namespace MonarcCore\Service;

class RolfCategoryServiceFactory extends AbstractServiceFactory
{
    protected $ressources = array(
        'table' => 'MonarcCore\Model\Table\RolfCategoryTable',
        'entity' => 'MonarcCore\Model\Entity\RolfCategory',
    );
}