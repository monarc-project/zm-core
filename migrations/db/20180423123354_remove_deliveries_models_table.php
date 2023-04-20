<?php

use Phinx\Migration\AbstractMigration;

class RemoveDeliveriesModelsTable extends AbstractMigration
{
    public function change()
    {
        $this->table('deliveries_models')->drop()->save();
    }
}
