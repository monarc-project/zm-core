<?php
/**
 * @link      https://github.com/monarc-project for the canonical source repository
 * @copyright Copyright (c) 2016-2020 SMILE GIE Securitymadein.lu - Licensed under GNU Affero GPL v3
 * @license   MONARC is licensed under GNU Affero General Public License version 3
 */

namespace Monarc\Core\Service;

use Monarc\Core\Model\Entity\SoaCategory;

/**
 * SoaCategory Service
 *
 * Class SoaCategoryService
 * @package Monarc\Core\Service
 */
class SoaCategoryService extends AbstractService
{
    protected $filterColumns = ['label1', 'label2', 'label3', 'label4'];
    protected $dependencies = ['referential'];
    protected $referentialTable;

    public function create($data, $last = true)
    {
        /** @var SoaCategory $soaCategory */
        $soaCategory = $this->get('entity');

        $referentialTable = $this->get('referentialTable');
        $data['referential'] = $referentialTable->getEntity($data['referential']);

        $soaCategory->exchangeArray($data);

        $dependencies = property_exists($this, 'dependencies') ? $this->dependencies : [];
        $this->setDependencies($soaCategory, $dependencies);

        return $this->get('table')->save($soaCategory, $last);
    }

    public function delete($id)
    {
        $table = $this->get('table');
        $categ = $table->getEntity($id);

        foreach ($categ->measures as $measure) {
            $measure->setCategory(null);
        }

        return parent::delete($id);
    }
}
