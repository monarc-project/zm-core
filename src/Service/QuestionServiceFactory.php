<?php
/**
 * @link      https://github.com/monarc-project for the canonical source repository
 * @copyright Copyright (c) 2016-2020 SMILE GIE Securitymadein.lu - Licensed under GNU Affero GPL v3
 * @license   MONARC is licensed under GNU Affero General Public License version 3
 */

namespace Monarc\Core\Service;

use Monarc\Core\Table\AnrTable;

/**
 * Question Service Factory
 * Class QuestionServiceFactory
 * @package Monarc\Core\Service
 */
class QuestionServiceFactory extends AbstractServiceFactory
{
    protected $ressources = [
        'table' => 'Monarc\Core\Model\Table\QuestionTable',
        'entity' => 'Monarc\Core\Entity\Question',
        'choiceTable' => 'Monarc\Core\Model\Table\QuestionChoiceTable',
        'anrTable' => AnrTable::class,
    ];
}
