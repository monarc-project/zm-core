<?php
/**
 * @link      https://github.com/monarc-project for the canonical source repository
 * @copyright Copyright (c) 2016-2020 SMILE GIE Securitymadein.lu - Licensed under GNU Affero GPL v3
 * @license   MONARC is licensed under GNU Affero General Public License version 3
 */

namespace Monarc\Core\Model\Table;

use Doctrine\ORM\EntityNotFoundException;
use Monarc\Core\Model\DbCli;
use Monarc\Core\Entity\DeliveriesModels;
use Monarc\Core\Service\ConnectedUserService;

class DeliveriesModelsTable extends AbstractEntityTable
{
    public function __construct(DbCli $dbService, ConnectedUserService $connectedUserService)
    {
        parent::__construct($dbService, DeliveriesModels::class, $connectedUserService);
    }

    public function findById(int $id): DeliveriesModels
    {
        /** @var ?DeliveriesModels $deliveryModel */
        $deliveryModel = $this->getRepository()->find($id);
        if ($deliveryModel === null) {
            throw EntityNotFoundException::fromClassNameAndIdentifier(DeliveriesModels::class, [$id]);
        }

        return $deliveryModel;
    }

    public function delete($id, $last = true): bool
    {
        $c = $this->getEntityClass();
        if (class_exists($c)) {
            $id = (int)$id;

            $entity = new $c();
            $entity->set('id', $id);
            $entity = $this->getDb()->fetch($entity);

            if (file_exists($entity->get('path'))) {
                unlink($entity->get('path'));
            }

            $this->getDb()->delete($entity, $last);
            return true;
        }

        return false;
    }
}
