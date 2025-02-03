<?php declare(strict_types=1);
/**
 * @link      https://github.com/monarc-project for the canonical source repository
 * @copyright Copyright (c) 2016-2025 Luxembourg House of Cybersecurity LHC.lu - Licensed under GNU Affero GPL v3
 * @license   MONARC is licensed under GNU Affero General Public License version 3
 */

namespace Monarc\Core\Service;

use Monarc\Core\Entity\ActionHistorySuperClass;
use Monarc\Core\Entity\UserSuperClass;
use Monarc\Core\Table\ActionHistoryTable;

class ActionHistoryService
{
    public function __construct(private ActionHistoryTable $actionHistoryTable)
    {
    }

    /**
     * @return ActionHistorySuperClass[]
     */
    public function getActionsHistoryByAction(string $action, int $limit = 0): array
    {
        return $this->actionHistoryTable->findByActionOrderByDate($action, $limit);
    }

    public function createActionHistory(
        string $action,
        array $data,
        int $status = ActionHistorySuperClass::STATUS_SUCCESS,
        ?UserSuperClass $user = null,
        bool $saveInDb = true
    ): ActionHistorySuperClass {
        $actionHistoryEntityName = $this->actionHistoryTable->getEntityName();
        /** @var ActionHistorySuperClass $actionHistory */
        $actionHistory = new $actionHistoryEntityName();
        $actionHistory
            ->setAction($action)
            ->setData(json_encode($data, JSON_THROW_ON_ERROR))
            ->setUser($user)
            ->setStatus($status)
            ->setCreator('System');
        $this->actionHistoryTable->save($actionHistory, $saveInDb);

        return $actionHistory;
    }
}
