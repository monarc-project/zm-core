<?php declare(strict_types=1);
/**
 * @link      https://github.com/monarc-project for the canonical source repository
 * @copyright Copyright (c) 2016-2026 Luxembourg House of Cybersecurity LHC.lu - Licensed under GNU Affero GPL v3
 * @license   MONARC is licensed under GNU Affero General Public License version 3
 */

namespace Monarc\Core\Service;

use Monarc\Core\Entity\UserSuperClass;
use Monarc\Core\Entity\Guide;
use Monarc\Core\Exception\Exception;
use Monarc\Core\InputFormatter\FormattedInputParams;
use Monarc\Core\Table\GuideTable;
use Monarc\Core\Table\GuideItemTable;

class GuideService
{
    private const TYPES = [
        1 => 'Risk analysis context',
        2 => 'Risk management context',
        3 => 'Summary assessment of trends and threats',
        4 => 'Summary of assets / impacts',
    ];

    private UserSuperClass $connectedUser;

    public function __construct(
        private GuideTable $guideTable,
        private GuideItemTable $guideItemTable,
        ConnectedUserService $connectedUserService
    ) {
        $this->connectedUser = $connectedUserService->getConnectedUser();
    }

    public function getTypes(): array
    {
        return self::TYPES;
    }

    public function getList(FormattedInputParams $params): array
    {
        return array_map(function (Guide $guide): array {
            return array_merge($this->serialize($guide), ['type_id' => $guide->getType()]);
        }, $this->guideTable->findByParams($params));
    }

    public function getCount(FormattedInputParams $params): int
    {
        return $this->guideTable->countByParams($params);
    }

    public function getGuideData(int $id): array
    {
        return $this->serialize($this->guideTable->findById($id));
    }

    public function serialize(Guide $guide, bool $displayType = true): array
    {
        return array_merge(['id' => $guide->getId()], $guide->getDescriptions(), [
            'creator' => $guide->getCreator(),
            'createdAt' => $guide->getCreatedAt(),
            'updater' => $guide->getUpdater(),
            'updatedAt' => $guide->getUpdatedAt(),
            'type' => $displayType ? self::TYPES[$guide->getType()] : $guide->getType(),
            'isWithItems' => $guide->getIsWithItems(),
        ]);
    }

    public function create(array $data): int
    {
        $this->assertCategoryAvailable($data['type']);

        $guide = (new Guide())
            ->setType($data['type'])
            ->setIsWithItems($data['isWithItems'])
            ->setDescriptions($data)
            ->setCreator($this->connectedUser->getEmail());

        $this->guideTable->save($guide);

        return $guide->getId();
    }

    public function update(int $id, array $data): void
    {
        $guide = $this->guideTable->findById($id);
        if (isset($data['type'])) {
            $this->assertCategoryAvailable($data['type'], $id);
            $guide->setType($data['type']);
        }
        if (isset($data['isWithItems'])) {
            $guide->setIsWithItems($data['isWithItems']);
        }
        $guide->setDescriptions($data)->setUpdater($this->connectedUser->getEmail());

        $this->guideTable->save($guide);
    }

    public function delete(int $id): void
    {
        $this->deleteList([$id]);
    }

    public function deleteList(array $ids): void
    {
        // Resolve every identifier before mutating a batch.
        $guides = array_map(fn (int $id): Guide => $this->guideTable->findById($id), $ids);
        foreach ($guides as $guide) {
            foreach ($this->guideItemTable->findChildrenOfGuide($guide) as $item) {
                $this->guideItemTable->remove($item, false);
            }
            $this->guideTable->remove($guide, false);
        }
        $this->guideTable->flush();
    }

    private function assertCategoryAvailable(int $type, ?int $exceptId = null): void
    {
        $existing = $this->guideTable->findForCategory($type);
        if ($existing !== null && $existing->getId() !== $exceptId) {
            throw new Exception('Only one guide by category', 412);
        }
    }
}
