<?php

use Phinx\Migration\AbstractMigration;

class AddReferentials extends AbstractMigration
{
    public function change()
    {
      // Migration for table referentials it appears that we can set a function as default value, so the uuid has to be managed via php or a trigger
      $table = $this->table('referentials');
      $table
          ->addColumn('uuid', 'uuid')
          ->addColumn('label1', 'string', array('null' => true, 'limit' => 255))
          ->addColumn('label2', 'string', array('null' => true, 'limit' => 255))
          ->addColumn('label3', 'string', array('null' => true, 'limit' => 255))
          ->addColumn('label4', 'string', array('null' => true, 'limit' => 255))
          ->addColumn('creator', 'string', array('null' => true, 'limit' => 255))
          ->addColumn('created_at', 'datetime', array('null' => true))
          ->addColumn('updater', 'string', array('null' => true, 'limit' => 255))
          ->addColumn('updated_at', 'datetime', array('null' => true))
          ->addIndex(array('uuid'))
          ->create();
      $table->removeColumn('id')->update();
      $row = ['uuid'=>'98ca84fb-db87-11e8-ac77-0800279aaa2b','label1'=>'ISO 27002','label2'=>'ISO 27002',
      'label3'=>'ISO 27002','label4'=>'ISO 27002','creator' => 'Migration script','created_at' => date('Y-m-d H:i:s')];
      $table->insert($row)->saveData();

      $this->execute("ALTER TABLE referentials ADD PRIMARY KEY uuid (uuid)");
      //add foreign key for measures
      $table = $this->table('measures');
      $table
          ->addColumn('referential_uuid', 'uuid', ['after' => 'soacategory_id'])
          ->addIndex(['referential_uuid', 'code'], ['unique' => true]) // we can't have 2 times the same code for same referential
          ->update();
      $this->execute('UPDATE measures m SET m.referential_uuid=(SELECT uuid FROM referentials LIMIT 1) ;');
      $table
          ->addForeignKey('referential_uuid', 'referentials', 'uuid', array('delete' => 'CASCADE','update' => 'RESTRICT'))
          ->addForeignKey('soacategory_id', 'soacategory', 'id', ['delete'=> 'SET_NULL', 'update'=> 'RESTRICT'])
          ->update();
      //add foreign key for the category
      $table = $this->table('soacategory');
      $table
          ->addColumn('referential_uuid', 'uuid')
          ->update();
      $this->execute('UPDATE soacategory s SET s.referential_uuid=(SELECT uuid FROM referentials LIMIT 1) ;');
      $table
          ->addForeignKey('referential_uuid', 'referentials', 'uuid', array('delete' => 'CASCADE','update' => 'RESTRICT'))
          ->update();

    }
}
