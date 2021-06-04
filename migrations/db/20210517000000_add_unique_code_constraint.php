<?php

use Phinx\Migration\AbstractMigration;

class AddUniqueCodeConstraint extends AbstractMigration
{
    public function change()
    {
        $this->execute(
            'ALTER TABLE `assets` ADD CONSTRAINT `assets_code_unq` UNIQUE (`code`);
            ALTER TABLE `threats` ADD CONSTRAINT `threats_code_unq` UNIQUE (`code`);
            ALTER TABLE `vulnerabilities` ADD CONSTRAINT `vulnerabilities_code_unq` UNIQUE (`code`);'
        );
    }
}
