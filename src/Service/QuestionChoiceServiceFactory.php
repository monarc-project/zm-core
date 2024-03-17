<?php
/**
 * @link      https://github.com/monarc-project for the canonical source repository
 * @copyright Copyright (c) 2016-2020 SMILE GIE Securitymadein.lu - Licensed under GNU Affero GPL v3
 * @license   MONARC is licensed under GNU Affero General Public License version 3
 */

namespace Monarc\Core\Service;

use Monarc\Core\Table\AnrTable;
use Monarc\Core\Model\Table\QuestionChoiceTable;
use Monarc\Core\Entity\QuestionChoice;
use Monarc\Core\Model\Table\QuestionTable;

/**
 * Question Choice Service Factory
 *
 * Class QuestionChoiceServiceFactory
 * @package Monarc\Core\Service
 */
class QuestionChoiceServiceFactory extends AbstractServiceFactory
{
    protected $ressources = [
        'table' => QuestionChoiceTable::class,
        'entity' => QuestionChoice::class,
        'anrTable' => AnrTable::class,
        'questionTable' => QuestionTable::class,
    ];
}
