<?php
/**
 * @link      https://github.com/monarc-project for the canonical source repository
 * @copyright Copyright (c) 2018 SMILE GIE Securitymadein.lu - Licensed under GNU Affero GPL v3
 * @license   MONARC is licensed under GNU Affero General Public License version 3
 */

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