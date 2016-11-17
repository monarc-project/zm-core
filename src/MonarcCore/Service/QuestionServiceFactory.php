<?php
namespace MonarcCore\Service;

class QuestionServiceFactory extends AbstractServiceFactory
{
    protected $ressources = array(
        'table' => 'MonarcCore\Model\Table\QuestionTable',
        'entity' => 'MonarcCore\Model\Entity\Question',
    );
}
