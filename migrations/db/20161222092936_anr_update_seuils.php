<?php

use Phinx\Migration\AbstractMigration;

class AnrUpdateSeuils extends AbstractMigration
{
    public function up()
    {
        $this->execute('UPDATE anrs SET seuil1 = 4 WHERE seuil1 IS NULL');
        $this->execute('UPDATE anrs SET seuil2 = 8 WHERE seuil2 IS NULL');
        $this->execute('UPDATE anrs SET seuil_rolf1 = 4 WHERE seuil_rolf1 IS NULL');
        $this->execute('UPDATE anrs SET seuil_rolf2 = 8 WHERE seuil_rolf2 IS NULL');
    }
    public function down()
    {
    }
}
