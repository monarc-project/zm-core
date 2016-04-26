<?php
namespace MonarcCore\Service;

use MonarcCore\Model\Entity\Model;
use MonarcCore\Validator\AlnumMultiLanguage;

class ModelService extends AbstractService
{
    protected $modelTable;

    public function create($data) {

        $validatorLabel = new AlnumMultiLanguage(array('label' => 'label'));
        if ($validatorLabel->isValid($data)) {
            $modelEntity = new Model();
            $modelEntity->exchangeArray($data);

            $modelTable = $this->get('modelTable');
            $modelTable->save($modelEntity);

            return true;
        } else {
            return false;
        }
    }

}