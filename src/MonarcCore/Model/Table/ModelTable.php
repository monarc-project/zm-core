<?php
namespace MonarcCore\Model\Table;

class ModelTable extends AbstractEntityTable {
    public function __construct(\MonarcCore\Model\Db $dbService) {
        parent::__construct($dbService, '\MonarcCore\Model\Entity\Model');
    }

}