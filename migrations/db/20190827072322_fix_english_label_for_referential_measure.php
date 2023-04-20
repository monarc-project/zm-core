<?php

use Phinx\Migration\AbstractMigration;

class FixEnglishLabelForReferentialMeasure extends AbstractMigration
{
    public function change()
    {
        $this->execute('UPDATE measures SET label2 = "Secure log-on procedures" WHERE uuid = "267fd954-f705-11e8-b555-0800279aaa2b"');
    }
}
