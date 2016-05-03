<?php
namespace MonarcCore\Model\Table;

class AssetTable extends AbstractEntityTable {
    public function __construct(\MonarcCore\Model\Db $dbService) {
        parent::__construct($dbService, '\MonarcCore\Model\Entity\Asset');
    }

}