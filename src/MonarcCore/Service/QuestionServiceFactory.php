<?php
/**
 * @link      https://github.com/monarc-project for the canonical source repository
 * @copyright Copyright (c) Cases is a registered trademark of SECURITYMADEIN.LU
 * @license   MyCases is licensed under the GNU Affero GPL v3 - See license.txt for more information
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