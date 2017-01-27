<?php
namespace MonarcCore\Service;

/**
 * Question Choice Service Factory
 *
 * Class QuestionChoiceServiceFactory
 * @package MonarcCore\Service
 */
class QuestionChoiceServiceFactory extends AbstractServiceFactory
{
    protected $ressources = [
        'table' => 'MonarcCore\Model\Table\QuestionChoiceTable',
        'entity' => 'MonarcCore\Model\Entity\QuestionChoice',
        'anrTable' => 'MonarcCore\Model\Table\AnrTable',
        'questionTable' => 'MonarcCore\Model\Table\QuestionTable',
    ];
}