<?php
/**
 * @link      https://github.com/monarc-project for the canonical source repository
 * @copyright Copyright (c) Cases is a registered trademark of SECURITYMADEIN.LU
 * @license   MyCases is licensed under the GNU Affero GPL v3 - See license.txt for more information
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