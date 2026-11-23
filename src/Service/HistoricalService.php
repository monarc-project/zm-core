<?php declare(strict_types=1);
/**
 * @link      https://github.com/monarc-project for the canonical source repository
 * @copyright Copyright (c) 2016-2026 Luxembourg House of Cybersecurity LHC.lu - Licensed under GNU Affero GPL v3
 * @license   MONARC is licensed under GNU Affero General Public License version 3
 */

namespace Monarc\Core\Service;

use Monarc\Core\Entity\Historical;
use Monarc\Core\Entity\UserSuperClass;
use Monarc\Core\InputFormatter\FormattedInputParams;
use Monarc\Core\Table\HistoricalTable;

class HistoricalService
{
    private UserSuperClass $connectedUser;

    public function __construct(
        private HistoricalTable $historicalTable,
        ConnectedUserService $connectedUserService
    ) {
        $this->connectedUser = $connectedUserService->getConnectedUser();
    }

    public function create(array $data, bool $flush = true): Historical
    {
        $historical = (new Historical())
            ->setType($data['type'] ?? null)
            ->setSourceId(isset($data['sourceId']) ? (string)$data['sourceId'] : null)
            ->setAction($data['action'] ?? null)
            ->setDetails($data['details'] ?? null)
            ->setLabels($data)
            ->setCreator($this->connectedUser->getEmail());

        $this->historicalTable->save($historical, $flush);

        return $historical;
    }

    public function getCount(FormattedInputParams $params): int
    {
        return $this->historicalTable->countByParams($params);
    }

    public function getList(FormattedInputParams $params): array
    {
        return array_map(static function (Historical $historical): array {
            return array_merge([
                'id' => $historical->getId(),
                'type' => $historical->getType(),
                'sourceId' => $historical->getSourceId(),
                'action' => $historical->getAction(),
                'details' => explode(' / ', $historical->getDetails() ?? ''),
                'creator' => $historical->getCreator(),
                'createdAt' => $historical->getCreatedAt()?->format('d/m/Y H:i:s') ?? '',
            ], $historical->getLabels());
        }, $this->historicalTable->findByParams($params));
    }
}
