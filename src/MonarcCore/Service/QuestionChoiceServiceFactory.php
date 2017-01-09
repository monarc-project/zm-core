<?php
namespace MonarcCore\Service;

class QuestionChoiceServiceFactory extends AbstractServiceFactory
{
    protected $ressources = array(
        'table' => 'MonarcCore\Model\Table\QuestionChoiceTable',
        'entity' => 'MonarcCore\Model\Entity\QuestionChoice',
        'anrTable' => 'MonarcCore\Model\Table\AnrTable',
        'questionTable' => 'MonarcCore\Model\Table\QuestionTable',
    );
}
