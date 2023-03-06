<?php declare(strict_types=1);
/**
 * @link      https://github.com/monarc-project for the canonical source repository
 * @copyright Copyright (c) 2016-2023 Luxembourg House of Cybersecurity LHC.lu - Licensed under GNU Affero GPL v3
 * @license   MONARC is licensed under GNU Affero General Public License version 3
 */

namespace Monarc\Core\Service\Traits;

use Monarc\Core\InputFormatter\FormattedInputParams;
use Monarc\Core\Model\Entity\Interfaces\TreeStructuredEntityInterface;
use Monarc\Core\Table\AbstractTable;

/* TODO: The trait is not used anywhere for the moment. */
trait TreeStructureTrait
{
    /**
     * Performs the setting of root relation for the entity's children.
     * The entity is not persisted with the new root, siblings' entities are persisted, but not flushed.
     */
    public function updateRootOfChildrenTree(TreeStructuredEntityInterface $entity, AbstractTable $table): void
    {
        $childrenOfRoot = $this->getChildrenOfRoot($entity, $table);

        /* Update root for the children entities. */
        $newChildrenRootEntity = $entity->getRoot() ?? $entity;
        foreach ($childrenOfRoot as $childOfRoot) {
            $childOfRoot->setRoot($newChildrenRootEntity);
            $table->save($table, false);
        }
    }

    /**
     * Returns list of children (direct and indirect) entities including the entity itself.
     *
     * @return TreeStructuredEntityInterface[]
     */
    public function getEntityWithLinkedChildren(TreeStructuredEntityInterface $entity, AbstractTable $table): array
    {
        $childrenOfRoot = $this->getChildrenOfRoot($entity, $table);

        $childrenEntities = [];
        foreach ($childrenOfRoot as $childOfRoot) {
            if ($this->isChildOfEntity($entity, $childOfRoot)) {
                $childrenEntities = array_merge($childrenEntities, $childOfRoot);
            }
        }

        return array_merge([$entity], $childrenEntities);
    }

    /**
     * Returns list of children (direct and indirect) entities IDs including the entity itself.
     *
     * @return int[]
     */
    public function getIdsOfEntityWithLinkedChildren(TreeStructuredEntityInterface $entity, AbstractTable $table): array
    {
        return array_map(static function ($item) {
            return $item->getId();
        }, $this->getEntityWithLinkedChildren($entity, $table));
    }

    /**
     * Returns list of children objects based on the root or the entity if root is null.
     *
     * @return TreeStructuredEntityInterface[]
     */
    private function getChildrenOfRoot(TreeStructuredEntityInterface $entity, AbstractTable $table): array
    {
        $rootEntityForChildren = $entity->getRoot() ?? $entity;
        $params = (new FormattedInputParams())
            ->addFilter('root', ['value' => $rootEntityForChildren]);

        return $table->findByParams($params);
    }

    private function isChildOfEntity(
        TreeStructuredEntityInterface $entity,
        TreeStructuredEntityInterface $childOfRoot
    ): bool {
        if ($childOfRoot->getParent() === null || $childOfRoot->getId() === $entity->getId()) {
            return false;
        }

        /* Directly or indirectly linked to the entity. */
        if ($childOfRoot->getParent()->getId() === $entity->getId()) {
            return true;
        }

        /* Validate the upper level, if the parent is linked. */
        return $this->isChildOfEntity($entity, $childOfRoot->getParent());
    }
}
