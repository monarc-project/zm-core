<?php declare(strict_types=1);
/**
 * @link      https://github.com/monarc-project for the canonical source repository
 * @copyright Copyright (c) 2016-2026 Luxembourg House of Cybersecurity LHC.lu - Licensed under GNU Affero GPL v3
 * @license   MONARC is licensed under GNU Affero General Public License version 3
 */

namespace Monarc\Core\Service;

use Monarc\Core\Entity\UserSuperClass;
use Monarc\Core\Entity\Guide;
use Monarc\Core\Entity\GuideItem;
use Monarc\Core\Exception\Exception;
use Monarc\Core\InputFormatter\FormattedInputParams;
use Monarc\Core\Table\GuideTable;
use Monarc\Core\Table\GuideItemTable;

class GuideItemService
{
    private UserSuperClass $connectedUser;

    public function __construct(
        private GuideItemTable $guideItemTable,
        private GuideTable $guideTable,
        private GuideService $guideService,
        ConnectedUserService $connectedUserService
    ) {
        $this->connectedUser = $connectedUserService->getConnectedUser();
    }

    public function getList(FormattedInputParams $params): array
    {
        return array_map(
            fn (GuideItem $guideItem): array => $this->serialize($guideItem),
            $this->guideItemTable->findByParams($params)
        );
    }

    public function getCount(FormattedInputParams $params): int
    {
        return $this->guideItemTable->countByParams($params);
    }

    public function getGuideItemData(int $id): array
    {
        return $this->serialize($this->guideItemTable->findById($id));
    }

    public function serialize(GuideItem $guideItem): array
    {
        return array_merge(['id' => $guideItem->getId()], $guideItem->getDescriptions(), [
            'creator' => $guideItem->getCreator(),
            'createdAt' => $guideItem->getCreatedAt(),
            'updater' => $guideItem->getUpdater(),
            'updatedAt' => $guideItem->getUpdatedAt(),
            'guide' => $this->guideService->serialize($guideItem->getGuide(), false),
            'position' => $guideItem->getPosition(),
        ]);
    }

    public function create(array $data): GuideItem
    {
        $guide = $this->guideTable->findById($data['guide']);
        $this->validatePlacement($data, $guide);
        $guideItem = (new GuideItem())
            ->setGuide($guide)
            ->setDescriptions($data)
            ->setCreator($this->connectedUser->getEmail());
        $this->placeItem($guideItem, $guide, $data);

        $this->guideItemTable->save($guideItem);

        return $guideItem;
    }

    public function update(int $id, array $data): void
    {
        $guideItem = $this->guideItemTable->findById($id);
        $guide = isset($data['guide'])
            ? $this->guideTable->findById($data['guide'])
            : $guideItem->getGuide();

        $this->validatePlacement($data, $guide, $guideItem);
        $this->placeItem($guideItem, $guide, $data);
        $guideItem->setDescriptions($data)
            ->setUpdater($this->connectedUser->getEmail());

        $this->guideItemTable->save($guideItem);
    }

    public function delete(int $id): void
    {
        $this->deleteList([$id]);
    }

    public function deleteList(array $ids): void
    {
        $guideItems = array_map(fn (int $id): GuideItem => $this->guideItemTable->findById($id), $ids);
        foreach ($guideItems as $guideItem) {
            $siblings = array_values(array_filter(
                $this->guideItemTable->findChildrenOfGuide($guideItem->getGuide()),
                fn (GuideItem $sibling): bool => !in_array($sibling->getId(), $ids, true)
            ));
            $this->numberItems($siblings);
        }

        $this->guideItemTable->removeList($guideItems);
    }

    private function validatePlacement(array $data, Guide $guide, ?GuideItem $guideItem = null): void
    {
        if (($data['implicitPosition'] ?? null) === 3) {
            if (empty($data['previous'])) {
                throw new Exception('Previous item is required', 412);
            }
            $previous = $this->guideItemTable->findById($data['previous']);
            if ($previous === $guideItem || $previous->getGuide()->getId() !== $guide->getId()) {
                throw new Exception('Previous item must belong to the same guide', 412);
            }
        }
    }

    private function placeItem(GuideItem $guideItem, Guide $guide, array $data): void
    {
        $oldGuide = $guideItem->getGuide();
        $siblings = array_values(array_filter(
            $this->guideItemTable->findChildrenOfGuide($guide),
            fn (GuideItem $sibling): bool => $sibling !== $guideItem
        ));
        $mode = $data['implicitPosition'] ?? null;
        $position = $guideItem->getPosition() ?? count($siblings) + 1;
        if ($oldGuide !== $guide) {
            $position = count($siblings) + 1;
        }
        if ($mode === 1) {
            $position = 1;
        } elseif ($mode === 2) {
            $position = count($siblings) + 1;
        } elseif ($mode === 3) {
            foreach ($siblings as $index => $sibling) {
                if ($sibling->getId() === $data['previous']) {
                    $position = $index + 2;
                    break;
                }
            }
        } elseif (isset($data['position'])) {
            $position = $data['position'];
        }
        $position = max(1, min($position, count($siblings) + 1));
        if (count($siblings) >= 65535) {
            throw new Exception('Too many guide items', 412);
        }
        array_splice($siblings, $position - 1, 0, [$guideItem]);
        $guideItem->setGuide($guide);
        $this->numberItems($siblings);
        if ($oldGuide !== $guide) {
            $this->numberItems(array_values(array_filter(
                $this->guideItemTable->findChildrenOfGuide($oldGuide),
                fn (GuideItem $sibling): bool => $sibling !== $guideItem
            )));
        }
    }

    private function numberItems(array $guideItems): void
    {
        foreach ($guideItems as $index => $guideItem) {
            $guideItem->setPosition($index + 1);
        }
    }
}
