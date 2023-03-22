<?php

use Phinx\Migration\AbstractMigration;

class DefaultKindOfMeasureBis extends AbstractMigration
{
    public function change()
    {
        $this->query('UPDATE instances_risks SET kind_of_measure = 5 WHERE kind_of_measure = 0;');
    }

}
