<?php
/**
 * @link      https://github.com/monarc-project for the canonical source repository
 * @copyright Copyright (c) 2018 SMILE GIE Securitymadein.lu - Licensed under GNU Affero GPL v3
 * @license   MONARC is licensed under GNU Affero General Public License version 3
 */

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