<?php

use Phinx\Migration\AbstractMigration;

class FixDateValue extends AbstractMigration
{
    /**
     * Change Method.
     *
     * Write your reversible migrations using this method.
     *
     * More information on writing migrations is available here:
     * http://docs.phinx.org/en/latest/migrations.html#the-abstractmigration-class
     *
     * The following commands can be used in this method and Phinx will
     * automatically reverse them when rolling back:
     *
     *    createTable
     *    renameTable
     *    addColumn
     *    renameColumn
     *    addIndex
     *    addForeignKey
     *
     * Remember to call "create()" or "update()" and NOT "save()" when working
     * with the Table class.
     */
    public function change()
    {
        $this->execute("UPDATE rolf_risks SET created_at = DATE('1970-01-01')  WHERE CAST(created_at AS CHAR(20)) = '0000-00-00 00:00:00' or created_at is null;");
        $this->execute("UPDATE rolf_risks SET updated_at = DATE('1970-01-01')  WHERE CAST(updated_at AS CHAR(20)) = '0000-00-00 00:00:00' or updated_at is null;");

        $this->execute("UPDATE rolf_tags SET created_at = DATE('1970-01-01')  WHERE CAST(created_at AS CHAR(20)) = '0000-00-00 00:00:00' or created_at is null;");
        $this->execute("UPDATE rolf_tags SET updated_at = DATE('1970-01-01')  WHERE CAST(updated_at AS CHAR(20)) = '0000-00-00 00:00:00' or updated_at is null;");

        $this->execute("UPDATE rolf_risks_tags  SET created_at = DATE('1970-01-01')  WHERE CAST(created_at AS CHAR(20)) = '0000-00-00 00:00:00' or created_at is null;");
        $this->execute("UPDATE rolf_risks_tags  SET updated_at = DATE('1970-01-01')  WHERE CAST(updated_at AS CHAR(20)) = '0000-00-00 00:00:00' or updated_at is null;");

        $this->execute("UPDATE objects SET created_at = DATE('1970-01-01')  WHERE CAST(created_at AS CHAR(20)) = '0000-00-00 00:00:00' or created_at is null;");
        $this->execute("UPDATE objects SET updated_at = DATE('1970-01-01')  WHERE CAST(updated_at AS CHAR(20)) = '0000-00-00 00:00:00' or updated_at is null;");

        $this->execute("UPDATE guides SET created_at = DATE('1970-01-01')  WHERE CAST(created_at AS CHAR(20)) = '0000-00-00 00:00:00' or created_at is null;");
        $this->execute("UPDATE guides SET updated_at = DATE('1970-01-01')  WHERE CAST(updated_at AS CHAR(20)) = '0000-00-00 00:00:00' or updated_at is null;");

        $this->execute("UPDATE objects_categories SET created_at = DATE('1970-01-01')  WHERE CAST(created_at AS CHAR(20)) = '0000-00-00 00:00:00' or created_at is null;");
        $this->execute("UPDATE objects_categories SET updated_at = DATE('1970-01-01')  WHERE CAST(updated_at AS CHAR(20)) = '0000-00-00 00:00:00' or updated_at is null;");
    }
}
