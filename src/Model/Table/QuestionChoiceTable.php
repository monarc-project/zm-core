<?php
/**
 * @link      https://github.com/monarc-project for the canonical source repository
 * @copyright Copyright (c) 2016-2020 SMILE GIE Securitymadein.lu - Licensed under GNU Affero GPL v3
 * @license   MONARC is licensed under GNU Affero General Public License version 3
 */

namespace Monarc\Core\Model\Table;

use Monarc\Core\Model\Db;
use Monarc\Core\Entity\QuestionChoice;
use Monarc\Core\Service\ConnectedUserService;

/**
 * Class QuestionChoiceTable
 * @package Monarc\Core\Model\Table
 */
class QuestionChoiceTable extends AbstractEntityTable
{
    public function __construct(Db $dbService, ConnectedUserService $connectedUserService)
    {
        parent::__construct($dbService, QuestionChoice::class, $connectedUserService);
    }
}
