<?php declare(strict_types=1);
/**
 * @link      https://github.com/monarc-project for the canonical source repository
 * @copyright Copyright (c) 2016-2024 Luxembourg House of Cybersecurity LHC.lu - Licensed under GNU Affero GPL v3
 * @license   MONARC is licensed under GNU Affero General Public License version 3
 */

namespace Monarc\Core\Service;

use Monarc\Core\InputFormatter\FormattedInputParams;
use Monarc\Core\Entity;
use Monarc\Core\Table\ReferentialTable;
use Monarc\Core\Table\SoaCategoryTable;
use Monarc\Core\Table\MeasureTable;

class MeasureService
{
    private Entity\UserSuperClass $connectedUser;

    public function __construct(
        private MeasureTable $measureTable,
        private ReferentialTable $referentialTable,
        private SoaCategoryTable $soaCategoryTable,
        ConnectedUserService $connectedUserService
    ) {
        $this->connectedUser = $connectedUserService->getConnectedUser();
    }

    public function getList(FormattedInputParams $params): array
    {
        $result = [];

        /** @var Entity\Measure[] $measures */
        $measures = $this->measureTable->findByParams($params);
        $includeLinks = $params->hasFilterFor('includeLinks') && $params->getFilterFor('includeLinks')['value'];
        foreach ($measures as $measure) {
            $result[] = $this->prepareMeasureDataResult($measure, $includeLinks);
        }

        return $result;
    }

    public function getCount(FormattedInputParams $params): int
    {
        return $this->measureTable->countByParams($params);
    }

    public function getMeasureData(string $uuid): array
    {
        /** @var Entity\Measure $measure */
        $measure = $this->measureTable->findByUuid($uuid);

        return $this->prepareMeasureDataResult($measure);
    }

    public function create(array $data, bool $saveInDb = true): Entity\Measure
    {
        /** @var Entity\Referential $referential */
        $referential = $this->referentialTable->findByUuid($data['referentialUuid']);
        /** @var Entity\SoaCategory $soaCategory */
        $soaCategory = $this->soaCategoryTable->findById($data['categoryId']);

        /** @var Entity\Measure $measure */
        $measure = (new Entity\Measure())
            ->setCode($data['code'])
            ->setLabels($data)
            ->setReferential($referential)
            ->setCategory($soaCategory)
            ->setCreator($this->connectedUser->getEmail());

        $this->measureTable->save($measure, $saveInDb);

        return $measure;
    }

    public function createList(array $data): array
    {
        $createdUuids = [];
        foreach ($data as $row) {
            $createdUuids[] = $this->create($row, false)->getUuid();
        }
        $this->measureTable->flush();

        return $createdUuids;
    }

    public function update(string $uuid, array $data): Entity\Measure
    {
        /** @var Entity\Measure $measure */
        $measure = $this->measureTable->findByUuid($uuid);
        $measure->setLabels($data)
            ->setCode($data['code'])
            ->setUpdater($this->connectedUser->getEmail());
        if ($measure->getCategory() === null || $measure->getCategory()->getId() !== $data['categoryId']) {
            $previousLinkedCategory = $measure->getCategory();
            /** @var Entity\SoaCategory $soaCategory */
            $soaCategory = $this->soaCategoryTable->findById($data['categoryId']);
            $measure->setCategory($soaCategory);
            if ($previousLinkedCategory !== null && $previousLinkedCategory->getMeasures()->isEmpty()) {
                $this->soaCategoryTable->remove($previousLinkedCategory, false);
            }
        }

        $this->measureTable->save($measure);

        return $measure;
    }

    public function delete(string $uuid): void
    {
        /** @var Entity\Measure $measure */
        $measure = $this->measureTable->findByUuid($uuid);

        $this->processMeasureRemoval($measure);
    }

    public function deleteList(array $data)
    {
        /** @var Entity\Measure[] $measures */
        $measures = $this->measureTable->findByUuids($data);

        foreach ($measures as $measure) {
            $this->processMeasureRemoval($measure, false);
        }
        $this->measureTable->flush();
    }

    private function processMeasureRemoval(Entity\Measure $measure, bool $saveInDb = true): void
    {
        $previousLinkedCategory = $measure->getCategory();
        $measure->setCategory(null);
        if ($previousLinkedCategory !== null && $previousLinkedCategory->getMeasures()->isEmpty()) {
            $this->soaCategoryTable->remove($previousLinkedCategory, false);
        }

        $this->measureTable->remove($measure, $saveInDb);
    }

    private function prepareMeasureDataResult(Entity\Measure $measure, bool $includeLinks): array
    {
        $linkedMeasures = [];
        if ($includeLinks) {
            foreach ($measure->getLinkedMeasures() as $linkedMeasure) {
                $linkedMeasures[] = array_merge([
                    'uuid' => $linkedMeasure->getUuid(),
                    'code' => $linkedMeasure->getCode(),
                ], $linkedMeasure->getLabels());
            }
        }

        return array_merge([
            'uuid' => $measure->getUuid(),
            'referential' => array_merge([
                'uuid' => $measure->getReferential()->getUuid(),
            ], $measure->getReferential()->getLabels()),
            'code' => $measure->getCode(),
            'category' => $measure->getCategory() === null
                ? []
                : array_merge(['id' => $measure->getCategory()->getId()], $measure->getCategory()->getLabels()),
            'status' => $measure->getStatus(),
            'linkedMeasures' => $linkedMeasures,
        ], $measure->getLabels());
    }
}
