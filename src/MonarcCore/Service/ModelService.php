<?php
namespace MonarcCore\Service;

use MonarcCore\Model\Entity\Model;
use MonarcCore\Validator\AlnumMultiLanguage;

class ModelService extends AbstractService
{
    protected $modelTable;

    public function create($data) {

        $modelEntity = new Model();
        $modelEntity->exchangeArray($data);

        $modelTable = $this->get('modelTable');
        $modelTable->save($modelEntity);
    }

}