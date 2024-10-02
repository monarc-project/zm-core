<?php declare(strict_types=1);
/**
 * @link      https://github.com/monarc-project for the canonical source repository
 * @copyright Copyright (c) 2016-2024 Luxembourg House of Cybersecurity LHC.lu - Licensed under GNU Affero GPL v3
 * @license   MONARC is licensed under GNU Affero General Public License version 3
 */

namespace Monarc\Core\Service\Export\Traits;

use Monarc\Core\Entity;

trait ObjectExportTrait
{
    use AssetExportTrait;
    use InformationRiskExportTrait;
    use OperationalRiskExportTrait;

    private function prepareObjectData(Entity\MonarcObject $object): array
    {
        /** @var Entity\ObjectCategory $objectCategory */
        $objectCategory = $object->getCategory();
        /** @var Entity\Asset $asset */
        $asset = $object->getAsset();
        $assetData = $this->prepareAssetData($asset);
        $assetData['informationRisks'] = [];
        foreach ($asset->getAmvs() as $amv) {
            $assetData['informationRisks'][] = $this->prepareInformationRiskData($amv);
        }
        $rolfTagData = null;
        if ($object->getRolfTag() !== null) {
            /** @var Entity\RolfTag $rolfTag */
            $rolfTag = $object->getRolfTag();
            $rolfTagData = array_merge([
                'id' => $rolfTag->getId(),
                'code' => $rolfTag->getCode(),
                'rolfRisks' => $this->prepareRolfRisksData($rolfTag),
            ], $rolfTag->getLabels());
        }

        return array_merge($object->getNames(), $object->getLabels(), [
            'uuid' => $object->getUuid(),
            'mode' => $object->getMode(),
            'scope' => $object->getScope(),
            'category' => $objectCategory !== null
                ? $this->prepareCategoryAndParentsData($objectCategory)
                : null,
            'asset' => $assetData,
            'rolfTag' => $rolfTagData,
            'children' => $object->hasChildren() ? $this->prepareChildrenObjectsData($object) : [],
        ]);
    }

    private function prepareCategoryAndParentsData(Entity\ObjectCategory $objectCategory): array
    {
        /** @var ?Entity\ObjectCategory $parentCategory */
        $parentCategory = $objectCategory->getParent();

        return array_merge($objectCategory->getLabels(), [
            'id' => $objectCategory->getId(),
            'position' => $objectCategory->getPosition(),
            'parent' => $parentCategory !== null ? $this->prepareCategoryAndParentsData($parentCategory) : null,
        ]);
    }

    private function prepareChildrenObjectsData(Entity\MonarcObject $object): array
    {
        $result = [];
        foreach ($object->getChildrenLinks() as $childLink) {
            /** @var Entity\MonarcObject $childObject */
            $childObject = $childLink->getChild();
            $result[] = $this->prepareObjectData($childObject);
        }

        return $result;
    }

    private function prepareRolfRisksData(Entity\RolfTag $rolfTag): array
    {
        $rolfRisksData = [];
        foreach ($rolfTag->getRisks() as $rolfRisk) {
            $rolfRisksData[] = $this->prepareOperationalRiskData($rolfRisk);
        }

        return $rolfRisksData;
    }
}
