<?php

use Phinx\Migration\AbstractMigration;

class RemoveCategoriesColumnFromOperationalRisks extends AbstractMigration
{
    public function change()
    {
        $this->table('rolf_risks_categories')->drop()->save();
        $this->table('rolf_categories')->drop()->save();
    }
}
