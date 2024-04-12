<?php declare(strict_types=1);
/**
 * @link      https://github.com/monarc-project for the canonical source repository
 * @copyright Copyright (c) 2016-2024 Luxembourg House of Cybersecurity LHC.lu - Licensed under GNU Affero GPL v3
 * @license   MONARC is licensed under GNU Affero General Public License version 3
 */

namespace Monarc\Core\Service;

use Monarc\Core\Entity\Referential;
use Monarc\Core\Entity\SoaCategory;
use Monarc\Core\InputFormatter\FormattedInputParams;
use Monarc\Core\Table\ReferentialTable;
use Monarc\Core\Table\SoaCategoryTable;

class SoaCategoryService
{
    public function __construct(private SoaCategoryTable $soaCategoryTable, private ReferentialTable $referentialTable)
    {
    }

    public function getList(FormattedInputParams $params): array
    {
        $result = [];
        /** @var SoaCategory $soaCategory */
        foreach ($this->soaCategoryTable->findByParams($params) as $soaCategory) {
            $result[] = $this->prepareSoaCategoryDataResult($soaCategory);
        }

        return $result;
    }

    public function getSoaCategoryData(int $id): array
    {
        /** @var SoaCategory $soaCategory */
        $soaCategory = $this->soaCategoryTable->findById($id);

        return $this->prepareSoaCategoryDataResult($soaCategory);
    }

    public function create(array $data, bool $saveInDb = true): SoaCategory
    {
        /** @var Referential $referential */
        $referential = $this->referentialTable->findByUuid($data['referential']);

        /** @var SoaCategory $soaCategory */
        $soaCategory = (new SoaCategory())->setLabels($data)->setReferential($referential);

        $this->soaCategoryTable->save($soaCategory, $saveInDb);

        return $soaCategory;
    }

    public function update(int $id, array $data): SoaCategory
    {
        /** @var SoaCategory $soaCategory */
        $soaCategory = $this->soaCategoryTable->findById($id);

        $soaCategory->setLabels($data);

        $this->soaCategoryTable->save($soaCategory);

        return $soaCategory;
    }

    public function delete(int $id): void
    {
        /** @var SoaCategory $soaCategory */
        $soaCategory = $this->soaCategoryTable->findById($id);

        $this->soaCategoryTable->remove($soaCategory);
    }

    private function prepareSoaCategoryDataResult(SoaCategory $soaCategory): array
    {
        return array_merge(['id' => $soaCategory->getId()], $soaCategory->getLabels());
    }
}
