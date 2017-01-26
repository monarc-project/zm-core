<?php
namespace MonarcCore\Service;

/**
 * Question Service Factory
 * Class QuestionServiceFactory
 * @package MonarcCore\Service
 */
class QuestionServiceFactory extends AbstractServiceFactory
{
    protected $ressources = [
        'table' => 'MonarcCore\Model\Table\QuestionTable',
        'entity' => 'MonarcCore\Model\Entity\Question',
        'choiceTable' => 'MonarcCore\Model\Table\QuestionChoiceTable',
        'anrTable' => 'MonarcCore\Model\Table\AnrTable',
    ];
}