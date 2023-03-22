<?php

use Phinx\Migration\AbstractMigration;
use Ramsey\Uuid\Uuid;

class AddMeasuresUuid extends AbstractMigration
{
    public function change()
    {
        //uuid for measures
        $data = [
            '5.1.1' => '267fc596-f705-11e8-b555-0800279aaa2b',
            '5.1.2' => '267fc6a6-f705-11e8-b555-0800279aaa2b',
            '7.2.1' => '267fc6f7-f705-11e8-b555-0800279aaa2b',
            '6.1.1' => '267fc73c-f705-11e8-b555-0800279aaa2b',
            '13.2.4' => '267fc77e-f705-11e8-b555-0800279aaa2b',
            '6.1.3' => '267fc7c0-f705-11e8-b555-0800279aaa2b',
            '6.1.4' => '267fc80f-f705-11e8-b555-0800279aaa2b',
            '18.2.1' => '267fc84f-f705-11e8-b555-0800279aaa2b',
            '15.1.1' => '267fc88e-f705-11e8-b555-0800279aaa2b',
            '15.1.2' => '267fc8cc-f705-11e8-b555-0800279aaa2b',
            '8.1.1' => '267fc90c-f705-11e8-b555-0800279aaa2b',
            '8.1.2' => '267fc94c-f705-11e8-b555-0800279aaa2b',
            '8.1.3' => '267fc989-f705-11e8-b555-0800279aaa2b',
            '8.2.1' => '267fc9c9-f705-11e8-b555-0800279aaa2b',
            '8.2.2' => '267fca19-f705-11e8-b555-0800279aaa2b',
            '7.1.1' => '267fca6b-f705-11e8-b555-0800279aaa2b',
            '7.1.2' => '267fcaad-f705-11e8-b555-0800279aaa2b',
            '7.2.2' => '267fcaeb-f705-11e8-b555-0800279aaa2b',
            '7.2.3' => '267fcb29-f705-11e8-b555-0800279aaa2b',
            '7.3.1' => '267fcb79-f705-11e8-b555-0800279aaa2b',
            '8.1.4' => '267fcbce-f705-11e8-b555-0800279aaa2b',
            '9.2.6' => '267fcc3c-f705-11e8-b555-0800279aaa2b',
            '11.1.1' => '267fcca4-f705-11e8-b555-0800279aaa2b',
            '11.1.2' => '267fcce9-f705-11e8-b555-0800279aaa2b',
            '11.1.3' => '267fcd30-f705-11e8-b555-0800279aaa2b',
            '11.1.4' => '267fcd6f-f705-11e8-b555-0800279aaa2b',
            '11.1.5' => '267fcdac-f705-11e8-b555-0800279aaa2b',
            '11.1.6' => '267fcdec-f705-11e8-b555-0800279aaa2b',
            '11.2.1' => '267fce44-f705-11e8-b555-0800279aaa2b',
            '11.2.2' => '267fce8a-f705-11e8-b555-0800279aaa2b',
            '11.2.3' => '267fcecb-f705-11e8-b555-0800279aaa2b',
            '11.2.4' => '267fcf0a-f705-11e8-b555-0800279aaa2b',
            '11.2.6' => '267fcf4f-f705-11e8-b555-0800279aaa2b',
            '11.2.7' => '267fcf90-f705-11e8-b555-0800279aaa2b',
            '11.2.5' => '267fcfdf-f705-11e8-b555-0800279aaa2b',
            '12.1.1' => '267fd029-f705-11e8-b555-0800279aaa2b',
            '12.1.2' => '267fd073-f705-11e8-b555-0800279aaa2b',
            '6.1.2' => '267fd0b1-f705-11e8-b555-0800279aaa2b',
            '12.1.4' => '267fd0ef-f705-11e8-b555-0800279aaa2b',
            '15.2.1' => '267fd12f-f705-11e8-b555-0800279aaa2b',
            '15.2.2' => '267fd16b-f705-11e8-b555-0800279aaa2b',
            '12.1.3' => '267fd1a8-f705-11e8-b555-0800279aaa2b',
            '14.2.9' => '267fd1ea-f705-11e8-b555-0800279aaa2b',
            '12.2.1' => '267fd22e-f705-11e8-b555-0800279aaa2b',
            '12.3.1' => '267fd272-f705-11e8-b555-0800279aaa2b',
            '13.1.1' => '267fd2b1-f705-11e8-b555-0800279aaa2b',
            '13.1.2' => '267fd2ee-f705-11e8-b555-0800279aaa2b',
            '8.3.1' => '267fd32a-f705-11e8-b555-0800279aaa2b',
            '8.3.2' => '267fd369-f705-11e8-b555-0800279aaa2b',
            '13.2.1' => '267fd3a6-f705-11e8-b555-0800279aaa2b',
            '13.2.2' => '267fd3e3-f705-11e8-b555-0800279aaa2b',
            '8.3.3' => '267fd421-f705-11e8-b555-0800279aaa2b',
            '13.2.3' => '267fd462-f705-11e8-b555-0800279aaa2b',
            '14.1.2' => '267fd4ac-f705-11e8-b555-0800279aaa2b',
            '14.1.3' => '267fd4ed-f705-11e8-b555-0800279aaa2b',
            '12.4.1' => '267fd529-f705-11e8-b555-0800279aaa2b',
            '12.4.2' => '267fd567-f705-11e8-b555-0800279aaa2b',
            '12.4.3' => '267fd5ae-f705-11e8-b555-0800279aaa2b',
            '12.4.4' => '267fd610-f705-11e8-b555-0800279aaa2b',
            '9.1.1' => '267fd659-f705-11e8-b555-0800279aaa2b',
            '9.2.3' => '267fd69f-f705-11e8-b555-0800279aaa2b',
            '9.2.4' => '267fd6e4-f705-11e8-b555-0800279aaa2b',
            '9.2.5' => '267fd723-f705-11e8-b555-0800279aaa2b',
            '9.3.1' => '267fd761-f705-11e8-b555-0800279aaa2b',
            '11.2.8' => '267fd7a0-f705-11e8-b555-0800279aaa2b',
            '11.2.9' => '267fd7dd-f705-11e8-b555-0800279aaa2b',
            '9.1.2' => '267fd81b-f705-11e8-b555-0800279aaa2b',
            '13.1.3' => '267fd85b-f705-11e8-b555-0800279aaa2b',
            '9.2.1' => '267fd899-f705-11e8-b555-0800279aaa2b',
            '9.4.3' => '267fd8d8-f705-11e8-b555-0800279aaa2b',
            '9.4.4' => '267fd917-f705-11e8-b555-0800279aaa2b',
            '9.4.2' => '267fd954-f705-11e8-b555-0800279aaa2b',
            '9.4.1' => '267fd993-f705-11e8-b555-0800279aaa2b',
            '6.2.1' => '267fd9d0-f705-11e8-b555-0800279aaa2b',
            '6.2.2' => '267fda0e-f705-11e8-b555-0800279aaa2b',
            '14.1.1' => '267fda50-f705-11e8-b555-0800279aaa2b',
            '10.1.1' => '267fda8c-f705-11e8-b555-0800279aaa2b',
            '10.1.2' => '267fdacc-f705-11e8-b555-0800279aaa2b',
            '12.5.1' => '267fdb18-f705-11e8-b555-0800279aaa2b',
            '14.3.1' => '267fdb78-f705-11e8-b555-0800279aaa2b',
            '9.4.5' => '267fdbf1-f705-11e8-b555-0800279aaa2b',
            '14.2.2' => '267fdc38-f705-11e8-b555-0800279aaa2b',
            '14.2.3' => '267fdc8c-f705-11e8-b555-0800279aaa2b',
            '14.2.4' => '267fdcf3-f705-11e8-b555-0800279aaa2b',
            '14.2.7' => '267fdd55-f705-11e8-b555-0800279aaa2b',
            '12.6.1' => '267fdda3-f705-11e8-b555-0800279aaa2b',
            '16.1.2' => '267fddeb-f705-11e8-b555-0800279aaa2b',
            '16.1.3' => '267fde31-f705-11e8-b555-0800279aaa2b',
            '16.1.1' => '267fde78-f705-11e8-b555-0800279aaa2b',
            '16.1.6' => '267fdeb8-f705-11e8-b555-0800279aaa2b',
            '16.1.7' => '267fdef6-f705-11e8-b555-0800279aaa2b',
            '14.2.5' => '267fdf36-f705-11e8-b555-0800279aaa2b',
            '17.1.1' => '267fdf76-f705-11e8-b555-0800279aaa2b',
            '17.1.2' => '267fdfbe-f705-11e8-b555-0800279aaa2b',
            '17.1.3' => '267fe022-f705-11e8-b555-0800279aaa2b',
            '18.1.1' => '267fe08b-f705-11e8-b555-0800279aaa2b',
            '18.1.2' => '267fe307-f705-11e8-b555-0800279aaa2b',
            '18.1.3' => '267fe37d-f705-11e8-b555-0800279aaa2b',
            '18.1.4' => '267fe3de-f705-11e8-b555-0800279aaa2b',
            '18.1.5' => '267fe510-f705-11e8-b555-0800279aaa2b',
            '18.2.2' => '267fe58f-f705-11e8-b555-0800279aaa2b',
            '18.2.3' => '267fe600-f705-11e8-b555-0800279aaa2b',
            '12.7.1' => '267fe660-f705-11e8-b555-0800279aaa2b',
            '6.1.5' => '267fe6b9-f705-11e8-b555-0800279aaa2b',
            '8.2.3' => '267fe71a-f705-11e8-b555-0800279aaa2b',
            '9.2.2' => '267fe782-f705-11e8-b555-0800279aaa2b',
            '14.2.8' => '267fe7e9-f705-11e8-b555-0800279aaa2b',
            '14.2.6' => '267fe847-f705-11e8-b555-0800279aaa2b',
            '14.2.1' => '267fe8a1-f705-11e8-b555-0800279aaa2b',
            '12.6.2' => '267fe8fe-f705-11e8-b555-0800279aaa2b',
            '15.1.3' => '267fe959-f705-11e8-b555-0800279aaa2b',
            '16.1.4' => '267fe9b4-f705-11e8-b555-0800279aaa2b',
            '16.1.5' => '267fea11-f705-11e8-b555-0800279aaa2b',
            '17.2.1' => '267fea72-f705-11e8-b555-0800279aaa2b',
        ];
        // Migration for table measures -- Modify the data
        $table = $this->table('measures');
        $table
            ->addColumn('uuid', 'uuid', ['after' => 'id'])
            ->addIndex(['uuid'])
            ->update();
        foreach ($data as $key => $value) { //fill the uuid only for 27002
            $this->execute('UPDATE measures SET uuid =' . '"' . $value . '"' . ' WHERE code =' . '"' . $key . '"');
        }
        $unUUIDpdo = $this->query('select uuid,id from measures' . ' WHERE uuid =' . '"' . '"');
        $unUUIDrows = $unUUIDpdo->fetchAll();

        foreach ($unUUIDrows as $key => $value) {
            $this->execute(
                'UPDATE measures SET uuid =' . '"' . Uuid::uuid4() . '"' . ' WHERE id =' . $value['id']
            ); //manage measure which are not 27002
        }

        $table = $this->table('measures_amvs');
        $table->dropForeignKey('measure_id')->save();
        $table->addColumn('measure_uuid', 'uuid', ['after' => 'id'])->save();
        $this->execute('UPDATE measures_amvs MA,measures M SET MA.measure_uuid = M.uuid where M.id=MA.measure_id');

        $table->removeColumn('measure_id')->save();
        $table->renameColumn('measure_uuid', 'measure_id')->save();
        $table->addForeignKey('measure_id', 'measures', 'uuid', ['delete' => 'CASCADE', 'update' => 'RESTRICT'])->save(
        );

        $table = $this->table('measures_measures');
        $table->dropForeignKey('father_id')
            ->dropForeignKey('child_id')
            ->addColumn('father_uuid', 'uuid', ['after' => 'id'])
            ->addColumn('child_uuid', 'uuid', ['after' => 'id'])
            ->update();
        $this->execute('UPDATE measures_measures MM,measures M SET MM.father_uuid = M.uuid where M.id=MM.father_id');
        $this->execute('UPDATE measures_measures MM,measures M SET MM.child_uuid = M.uuid where M.id=MM.child_id');
        $table->removeColumn('father_id')->save();
        $table->renameColumn('father_uuid', 'father_id')->save();
        $table->removeColumn('child_id')->save();
        $table->renameColumn('child_uuid', 'child_id')->save();
        $table->addForeignKey('father_id', 'measures', 'uuid', ['delete' => 'CASCADE', 'update' => 'RESTRICT'])
            ->addForeignKey('child_id', 'measures', 'uuid', ['delete' => 'CASCADE', 'update' => 'RESTRICT'])
            ->update();

        $this->table('soa')->drop()->save();

        // MODIFY STRUCTURE of concerning table
        $table = $this->table('measures_measures');
        $table->removeColumn('id')->update();
        $this->execute("ALTER TABLE measures_measures ADD PRIMARY KEY child_id_father_id_anr_id (child_id, father_id)");

        $table = $this->table('measures');
        $table->dropForeignKey('anr_id')->save();
        $table->removeColumn('anr_id')->save();
        $table->removeColumn('id')->update();
        $this->execute("ALTER TABLE measures ADD PRIMARY KEY uuid_anr_id (uuid)");

        $table = $this->table('rolf_risks_tags');
        $table->dropForeignKey('rolf_risk_id')
            ->dropForeignKey('rolf_tag_id')
            ->update();

        $table = $this->table('rolf_risks_tags');
        $table->addForeignKey('rolf_risk_id', 'rolf_risks', 'id', ['delete' => 'CASCADE', 'update' => 'RESTRICT'])
            ->addForeignKey('rolf_tag_id', 'rolf_tags', 'id', ['delete' => 'CASCADE', 'update' => 'RESTRICT'])
            ->update();
    }
}
