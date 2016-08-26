<?php
namespace MonarcCore\Service;

class InstanceConsequenceServiceFactory extends AbstractServiceFactory
{
    protected $ressources = array(
        'table' => 'MonarcCore\Model\Table\InstanceConsequenceTable',
        'entity' => 'MonarcCore\Model\Entity\InstanceConsequence',
        'anrTable' => 'MonarcCore\Model\Table\AnrTable',
        'instanceTable' => 'MonarcCore\Model\Table\InstanceTable',
        'objectTable' => 'MonarcCore\Model\Table\ObjectTable',
        'scaleTable' => 'MonarcCore\Model\Table\ScaleTable',
        'scaleImpactTypeTable' => 'MonarcCore\Model\Table\ScaleTypeTable',
    );
}
