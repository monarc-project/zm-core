<?php
namespace MonarcCore\Model\Table;

class UserRoleTable extends AbstractEntityTable {
    public function __construct(\MonarcCore\Model\Db $dbService) {
        parent::__construct($dbService, '\MonarcCore\Model\Entity\UserRole');
    }

}