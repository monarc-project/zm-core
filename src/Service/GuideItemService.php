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
            fn (GuideItem $item): array => $this->serialize($item),
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

    public function serialize(GuideItem $item): array
    {
        return array_merge(['id' => $item->getId()], $item->getDescriptions(), [
            'creator' => $item->getCreator(),
            'createdAt' => $item->getCreatedAt(),
            'updater' => $item->getUpdater(),
            'updatedAt' => $item->getUpdatedAt(),
            'guide' => $this->guideService->serialize($item->getGuide(), false),
            'position' => $item->getPosition(),
        ]);
    }

    public function create(array $data): int
    {
        $guide = $this->guideTable->findById($data['guide']);
        $this->validatePlacement($data, $guide);
        $item = (new GuideItem())
            ->setGuide($guide)
            ->setDescriptions($data)
            ->setCreator($this->connectedUser->getEmail());
        $this->placeItem($item, $guide, $data);

        $this->guideItemTable->save($item);

        return $item->getId();
    }

    public function update(int $id, array $data): void
    {
        $item = $this->guideItemTable->findById($id);
        $guide = isset($data['guide'])
            ? $this->guideTable->findById($data['guide'])
            : $item->getGuide();

        $this->validatePlacement($data, $guide, $item);
        $this->placeItem($item, $guide, $data);
        $item->setDescriptions($data)->setUpdater($this->connectedUser->getEmail());

        $this->guideItemTable->save($item);
    }

    public function delete(int $id): void
    {
        $this->deleteList([$id]);
    }

    public function deleteList(array $ids): void
    {
        $items = array_map(fn (int $id): GuideItem => $this->guideItemTable->findById($id), $ids);
        foreach ($items as $item) {
            $siblings = array_values(array_filter(
                $this->guideItemTable->findChildrenOfGuide($item->getGuide()),
                fn (GuideItem $sibling): bool => !in_array($sibling->getId(), $ids, true)
            ));
            $this->numberItems($siblings);
        }

        $this->guideItemTable->removeList($items);
    }

    private function validatePlacement(array $data, Guide $guide, ?GuideItem $item = null): void
    {
        if (($data['implicitPosition'] ?? null) === 3) {
            if (empty($data['previous'])) {
                throw new Exception('Previous item is required', 412);
            }
            $previous = $this->guideItemTable->findById($data['previous']);
            if ($previous === $item || $previous->getGuide()->getId() !== $guide->getId()) {
                throw new Exception('Previous item must belong to the same guide', 412);
            }
        }
    }

    private function placeItem(GuideItem $item, Guide $guide, array $data): void
    {
        $oldGuide = $item->getGuide();
        $siblings = array_values(array_filter(
            $this->guideItemTable->findChildrenOfGuide($guide),
            fn (GuideItem $sibling): bool => $sibling !== $item
        ));
        $mode = $data['implicitPosition'] ?? null;
        $position = $item->getPosition() ?? count($siblings) + 1;
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
        array_splice($siblings, $position - 1, 0, [$item]);
        $item->setGuide($guide);
        $this->numberItems($siblings);
        if ($oldGuide !== $guide) {
            $this->numberItems(array_values(array_filter(
                $this->guideItemTable->findChildrenOfGuide($oldGuide),
                fn (GuideItem $sibling): bool => $sibling !== $item
            )));
        }
    }

    private function numberItems(array $items): void
    {
        foreach ($items as $index => $item) {
            $item->setPosition($index + 1);
        }
    }
}
