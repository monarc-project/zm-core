<?php

use Phinx\Migration\AbstractMigration;
use Ramsey\Uuid\Uuid;

class AddThreatsUuid extends AbstractMigration
{
    public function change()
    {
        //uuid for threats

        $data = [
            'MA11' => 'b402d4e0-4576-11e9-9173-0800277f0571',
            'MA15' => 'b402d513-4576-11e9-9173-0800277f0571',
            'MA16' => 'b402d523-4576-11e9-9173-0800277f0571',
            'MD14' => 'b402d530-4576-11e9-9173-0800277f0571',
            'MD15' => 'b402d557-4576-11e9-9173-0800277f0571',
            'MD19' => 'b402d563-4576-11e9-9173-0800277f0571',
            'MD21' => 'b402d56f-4576-11e9-9173-0800277f0571',
            'MD22' => 'b402d579-4576-11e9-9173-0800277f0571',
            'MD23' => 'b402d584-4576-11e9-9173-0800277f0571',
            'MD24' => 'b402d58f-4576-11e9-9173-0800277f0571',
            'MD26' => 'b402d599-4576-11e9-9173-0800277f0571',
            'MD27' => 'b402d5a5-4576-11e9-9173-0800277f0571',
            'MD36' => 'b402d5af-4576-11e9-9173-0800277f0571',
            'MDA12' => 'b402d5c9-4576-11e9-9173-0800277f0571',
            'MDA13' => 'b402d5d5-4576-11e9-9173-0800277f0571',
            'MDA16' => 'b402d5df-4576-11e9-9173-0800277f0571',
            'MDA17' => 'b402d5ea-4576-11e9-9173-0800277f0571',
            'MDA18' => 'b402d5f5-4576-11e9-9173-0800277f0571',
            'MDA20' => 'b402d600-4576-11e9-9173-0800277f0571',
            'MDA25' => 'b402d60a-4576-11e9-9173-0800277f0571',
            'MDA28' => 'b402d615-4576-11e9-9173-0800277f0571',
            'MDA29' => 'b402d620-4576-11e9-9173-0800277f0571',
            'ME11' => 'b402d63d-4576-11e9-9173-0800277f0571',
            'ME12' => 'b402d648-4576-11e9-9173-0800277f0571',
            'ME13' => 'b402d653-4576-11e9-9173-0800277f0571',
            'ME14' => 'b402d65d-4576-11e9-9173-0800277f0571',
            'ME15' => 'b402d668-4576-11e9-9173-0800277f0571',
            'ME16' => 'b402d673-4576-11e9-9173-0800277f0571',
            'ME17' => 'b402d67d-4576-11e9-9173-0800277f0571',
            'ME18' => 'b402d688-4576-11e9-9173-0800277f0571',
        ];
        // Migration for table threats -- Modify the data
        $table = $this->table('threats');
        $table
            ->addColumn('uuid', 'uuid', ['after' => 'id'])
            ->addIndex(['uuid'])
            ->update();
        foreach ($data as $key => $value) { //fill the uuid only for threats created by cases
            $this->execute('UPDATE threats SET uuid =' . '"' . $value . '"' . ' WHERE code =' . '"' . $key . '"');
        }
        $unUUIDpdo = $this->query('select uuid,id from threats' . ' WHERE uuid =' . '"' . '"');
        $unUUIDrows = $unUUIDpdo->fetchAll();

        foreach ($unUUIDrows as $key => $value) {
            $this->execute(
                'UPDATE threats SET uuid =' . '"' . Uuid::uuid4() . '"' . ' WHERE id =' . $value['id']
            ); //manage threats which are not in common
        }

        $table = $this->table('amvs');
        $table->dropForeignKey('threat_id')
            ->addColumn('threat_uuid', 'uuid', ['after' => 'id'])
            ->update();
        $this->execute('UPDATE amvs A,threats B SET A.threat_uuid = B.uuid where B.id=A.threat_id');
        $table->removeColumn('threat_id')->save();
        $table->renameColumn('threat_uuid', 'threat_id')->update();
        $table->addForeignKey('threat_id', 'threats', 'uuid', ['delete' => 'CASCADE', 'update' => 'RESTRICT'])->save();

        $table = $this->table('threats_models');
        $table->dropForeignKey('threat_id')->addColumn('threat_uuid', 'uuid')->update();
        $this->execute('ALTER TABLE `threats_models` DROP INDEX `PRIMARY`;');
        $this->execute('UPDATE threats_models A,threats B SET A.threat_uuid = B.uuid where B.id=A.threat_id');
        $table->removeColumn('threat_id')->save();
        $table->renameColumn('threat_uuid', 'threat_id')->update();
        $table->addForeignKey('threat_id', 'threats', 'uuid', ['delete' => 'CASCADE', 'update' => 'RESTRICT'])->save();
        $this->execute("ALTER TABLE `threats_models` ADD PRIMARY KEY `threat_id_model_id` (`threat_id`, `model_id`);");

        $table = $this->table('instances_risks');
        $table->dropForeignKey('threat_id')
            ->addColumn('threat_uuid', 'uuid', ['after' => 'id'])
            ->update();
        $this->execute('UPDATE instances_risks A,threats B SET A.threat_uuid = B.uuid where B.id=A.threat_id');
        $table->removeColumn('threat_id')->save();
        $table->renameColumn('threat_uuid', 'threat_id')->update();
        $table->addForeignKey('threat_id', 'threats', 'uuid', ['delete' => 'CASCADE', 'update' => 'RESTRICT'])->save();

        $this->table('threats')->removeColumn('id')->update();
        $this->execute("ALTER TABLE threats ADD PRIMARY KEY uuid (uuid)");
    }
}
