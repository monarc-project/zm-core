<?php
/**
 * @link      https://github.com/monarc-project for the canonical source repository
 * @copyright Copyright (c) 2018 SMILE GIE Securitymadein.lu - Licensed under GNU Affero GPL v3
 * @license   MONARC is licensed under GNU Affero General Public License version 3
 */

namespace MonarcCore\Service;

/**
 * SoaCategory Service
 *
 * Class SoaCategoryService
 * @package MonarcCore\Service
 */
class SoaCategoryService extends AbstractService
{
    protected $filterColumns = ['code','label1', 'label2', 'label3', 'label4'];
    protected $dependencies = ['referential'];
    protected $referentialTable;

    public function create($data, $last = true)
    {
        $class = $this->get('entity');

        $table = $this->get('table');
        $referentialTable = $this->get('referentialTable');
        $data['referential'] = $referentialTable->getEntity($data['referential']);

        $entity = new $class();

        $entity->setLanguage($this->getLanguage());
        $entity->setDbAdapter($table->getDb());
        $entity->exchangeArray($data);

        $dependencies = (property_exists($this, 'dependencies')) ? $this->dependencies : [];
        $this->setDependencies($entity, $dependencies);

        return $table->save($entity, $last);
    }
}
