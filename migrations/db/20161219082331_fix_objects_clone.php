<?php

use Phinx\Migration\AbstractMigration;

class FixObjectsClone extends AbstractMigration
{
    public function up()
    {
        $this->execute('UPDATE objects set name1 = "" WHERE name1 = " (copy)"');
        $this->execute('UPDATE objects set name2 = "" WHERE name2 = " (copy)"');
        $this->execute('UPDATE objects set name3 = "" WHERE name3 = " (copy)"');
        $this->execute('UPDATE objects set name4 = "" WHERE name4 = " (copy)"');
    }
}
