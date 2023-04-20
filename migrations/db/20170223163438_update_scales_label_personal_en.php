<?php

use Phinx\Migration\AbstractMigration;

class UpdateScalesLabelPersonalEn extends AbstractMigration
{
    public function change()
    {
        $this->execute('UPDATE scales_impact_types SET label2 = \'Personal\' WHERE is_sys = 1 AND label2 = \'Person\'');
    }
}
