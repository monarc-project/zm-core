<?php

use Phinx\Migration\AbstractMigration;

class DefaultKindOfMeasure extends AbstractMigration
{
    public function change()
    {
        $this->query('UPDATE instances_risks SET kind_of_measure = 5 WHERE kind_of_measure = 0;');
    }
}
