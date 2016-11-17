<?php
namespace MonarcCore\Service;

class GuideItemServiceFactory extends AbstractServiceFactory
{
    protected $ressources = array(
        'table' => 'MonarcCore\Model\Table\QuestionChoiceTable',
        'entity' => 'MonarcCore\Model\Entity\QuestionChoice',
        'guideTable' => 'MonarcCore\Model\Table\QuestionTable',
    );
}
