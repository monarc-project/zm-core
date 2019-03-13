<?php

use Phinx\Migration\AbstractMigration;
use Phinx\Db\Adapter\MysqlAdapter;
use Phinx\Util\Literal;
use Ramsey\Uuid\Uuid;

class AddAssetsUuid extends AbstractMigration
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
      //uuid for asssets

      $data = array('BAT_LOC' => 'd2023c5d-44d1-11e9-a78c-0800277f0571',
              'CONT' => 'd2023c8f-44d1-11e9-a78c-0800277f0571',
              'INFO' => 'd2023ca5-44d1-11e9-a78c-0800277f0571',
              'LOG_APP' => 'd2023cb8-44d1-11e9-a78c-0800277f0571',
              'LOG_OS' => 'd2023ceb-44d1-11e9-a78c-0800277f0571',
              'LOG_SRV' => 'd2023cfb-44d1-11e9-a78c-0800277f0571',
              'LOG_STD' => 'd2023d0a-44d1-11e9-a78c-0800277f0571',
              'MAT_FIX' => 'd2023d19-44d1-11e9-a78c-0800277f0571',
              'MAT_MOB' => 'd2023d29-44d1-11e9-a78c-0800277f0571',
              'MAT_NELE' => 'd2023d38-44d1-11e9-a78c-0800277f0571',
              'MAT_PERI' => 'd2023d46-44d1-11e9-a78c-0800277f0571',
              'MAT_SUPP' => 'd2023d57-44d1-11e9-a78c-0800277f0571',
              'ORG_EXT' => 'd2023d64-44d1-11e9-a78c-0800277f0571',
              'ORG_GEN' => 'd2023d89-44d1-11e9-a78c-0800277f0571',
              'OV_BACKUP' => 'd2023d98-44d1-11e9-a78c-0800277f0571',
              'OV_BATI' => 'd2023da7-44d1-11e9-a78c-0800277f0571',
              'OV_DEVELOPPEMENT' => 'd2023db5-44d1-11e9-a78c-0800277f0571',
              'OV_INFOPHY' => 'd2023dc4-44d1-11e9-a78c-0800277f0571',
              'OV_LOGICIEL' => 'd2023dd3-44d1-11e9-a78c-0800277f0571',
              'OV_MAINTENANCE' => 'd2023de2-44d1-11e9-a78c-0800277f0571',
              'OV_MOBIL' => 'd2023df2-44d1-11e9-a78c-0800277f0571',
              'OV_MULTI_IMPRIMANTE' => 'd2023e01-44d1-11e9-a78c-0800277f0571',
              'OV_ORGANISATION' => 'd2023e2b-44d1-11e9-a78c-0800277f0571',
              'OV_POSTE_FIXE' => 'd2023e3c-44d1-11e9-a78c-0800277f0571',
              'OV_RESEAU' => 'd2023e4b-44d1-11e9-a78c-0800277f0571',
              'OV_SALLE_IT' => 'd2023e59-44d1-11e9-a78c-0800277f0571',
              'OV_SERVEUR' => 'd2023e65-44d1-11e9-a78c-0800277f0571',
              'OV_UTIL' => 'd2023e6f-44d1-11e9-a78c-0800277f0571',
              'PER' => 'd2023e7a-44d1-11e9-a78c-0800277f0571',
              'PER_DEC' => 'd2023e85-44d1-11e9-a78c-0800277f0571',
              'PER_DEV' => 'd2023e90-44d1-11e9-a78c-0800277f0571',
              'PER_EXP' => 'd2023ea9-44d1-11e9-a78c-0800277f0571',
              'PER_UTI' => 'd2023eb7-44d1-11e9-a78c-0800277f0571',
              'PROC' => 'd2023ec8-44d1-11e9-a78c-0800277f0571',
              'RESEAU' => 'd2023ed7-44d1-11e9-a78c-0800277f0571',
              'SERV' => 'd2023ee2-44d1-11e9-a78c-0800277f0571',
              'SERV_ESS' => 'd2023eef-44d1-11e9-a78c-0800277f0571',
              'SYS_ANU' => 'd2023efb-44d1-11e9-a78c-0800277f0571',
              'SYS_INT' => 'd2023f08-44d1-11e9-a78c-0800277f0571',
              'SYS_ITR' => 'd2023f15-44d1-11e9-a78c-0800277f0571',
              'SYS_MES' => 'd2023f34-44d1-11e9-a78c-0800277f0571',
              'SYS_WEB' => 'd2023f3f-44d1-11e9-a78c-0800277f0571');
      // Migration for table assets -- Modify the data
      $table = $this->table('assets');
      $table
          ->addColumn('uuid', 'uuid',array('after' => 'id'))
          ->addIndex(array('uuid'))
          ->update();
      foreach ($data as $key => $value) { //fill the uuid only for assets created by cases
        $this->execute('UPDATE assets SET uuid =' .'"'.$value.'"'.' WHERE code ='.'"'.$key .'"');
      }
      $unUUIDpdo = $this->query('select uuid,id from assets' .' WHERE uuid ='.'"'.'"');
      $unUUIDrows = $unUUIDpdo->fetchAll();

      foreach ($unUUIDrows as $key => $value) {
       $this->execute('UPDATE assets SET uuid =' .'"'.Uuid::uuid4().'"'.' WHERE id ='.$value['id']); //manage assets which are not in common
      }

      $table = $this->table('amvs'); //set the stufff for amvs
      $table->dropForeignKey('asset_id')
            ->addColumn('asset_uuid', 'uuid',array('after' => 'id'))
            ->update();
      $this->execute('UPDATE amvs A,assets B SET A.asset_uuid = B.uuid where B.id=A.asset_id');
      $table->removeColumn('asset_id')
            ->renameColumn('asset_uuid','asset_id')
            ->update();
      $table->addForeignKey('asset_id', 'assets', 'uuid', ['delete'=> 'CASCADE', 'update'=> 'RESTRICT'])
            ->update();

      $table = $this->table('assets_models'); //set the stufff for assets_models
      $table->dropForeignKey('asset_id')
            ->addColumn('asset_uuid', 'uuid')
            ->update();
      $this->execute('UPDATE assets_models A,assets B SET A.asset_uuid = B.uuid where B.id=A.asset_id');
      $table->removeColumn('asset_id')
            ->renameColumn('asset_uuid','asset_id')
            ->update();
      $table->addForeignKey('asset_id', 'assets', 'uuid', ['delete'=> 'CASCADE', 'update'=> 'RESTRICT'])
            ->update();

      $table = $this->table('instances'); //set the stufff for instances
      $table->dropForeignKey('asset_id')
            ->addColumn('asset_uuid', 'uuid',array('after' => 'id'))
            ->update();
      $this->execute('UPDATE instances A,assets B SET A.asset_uuid = B.uuid where B.id=A.asset_id');
      $table->removeColumn('asset_id')
            ->renameColumn('asset_uuid','asset_id')
            ->update();
      $table->addForeignKey('asset_id', 'assets', 'uuid', ['delete'=> 'CASCADE', 'update'=> 'RESTRICT'])
            ->update();

      $table = $this->table('instances_risks'); //set the stufff for instances_risks
      $table->dropForeignKey('asset_id')
            ->addColumn('asset_uuid', 'uuid',array('after' => 'id'))
            ->update();
      $this->execute('UPDATE instances_risks A,assets B SET A.asset_uuid = B.uuid where B.id=A.asset_id');
      $table->removeColumn('asset_id')
            ->renameColumn('asset_uuid','asset_id')
            ->update();
      $table->addForeignKey('asset_id', 'assets', 'uuid', ['delete'=> 'CASCADE', 'update'=> 'RESTRICT'])
            ->update();

      $table = $this->table('objects'); //set the stufff for objects
      $table->dropForeignKey('asset_id')
            ->addColumn('asset_uuid', 'uuid',array('after' => 'id'))
            ->update();
      $this->execute('UPDATE objects A,assets B SET A.asset_uuid = B.uuid where B.id=A.asset_id');
      $table->removeColumn('asset_id')
            ->renameColumn('asset_uuid','asset_id')
            ->update();
      $table->addForeignKey('asset_id', 'assets', 'uuid', ['delete'=> 'CASCADE', 'update'=> 'RESTRICT'])
            ->update();

      $table = $this->table('assets');
      $table->removeColumn('id')
            ->update();
      $this->execute("ALTER TABLE assets ADD PRIMARY KEY uuid (uuid)");
    }
}
