<?php
namespace MonarcCore\Model\Table;

class UserTable extends AbstractEntityTable {
    public function __construct(\MonarcCore\Model\Db $dbService) {
        parent::__construct($dbService, '\MonarcCore\Model\Entity\User');
    }

}