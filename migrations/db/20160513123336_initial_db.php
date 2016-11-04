<?php

use Phinx\Migration\AbstractMigration;
use Phinx\Db\Adapter\MysqlAdapter;

class InitialDb extends AbstractMigration
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
        // Migration for table amvs
        $table = $this->table('amvs');
        $table
            ->addColumn('anr_id', 'integer', array('null' => true, 'signed' => false))
            ->addColumn('asset_id', 'integer', array('null' => true, 'signed' => false))
            ->addColumn('threat_id', 'integer', array('null' => true, 'signed' => false))
            ->addColumn('vulnerability_id', 'integer', array('null' => true, 'signed' => false))
            ->addColumn('measure1_id', 'integer', array('null' => true, 'signed' => false))
            ->addColumn('measure2_id', 'integer', array('null' => true, 'signed' => false))
            ->addColumn('measure3_id', 'integer', array('null' => true, 'signed' => false))
            ->addColumn('position', 'integer', array('null' => true, 'default' => '1', 'limit' => 11))
            ->addColumn('status', 'integer', array('null' => true, 'default' => '1', 'limit' => 11))
            ->addColumn('creator', 'string', array('null' => true, 'limit' => 255))
            ->addColumn('created_at', 'datetime', array('null' => true))
            ->addColumn('updater', 'string', array('null' => true, 'limit' => 255))
            ->addColumn('updated_at', 'datetime', array('null' => true))
            ->addIndex(array('anr_id'))
            ->addIndex(array('asset_id'))
            ->addIndex(array('threat_id'))
            ->addIndex(array('vulnerability_id'))
            ->addIndex(array('measure1_id'))
            ->addIndex(array('measure2_id'))
            ->addIndex(array('measure3_id'))
            ->create();
        $table->changeColumn('id', 'integer',array('identity'=>true,'signed'=>false))->update();


        // Migration for table anrs
        $table = $this->table('anrs');
        $table
            ->addColumn('snapshot_id', 'integer', array('null' => true, 'signed' => false))
            ->addColumn('snapshot_ref_id', 'integer', array('null' => true, 'signed' => false))
            ->addColumn('label1', 'string', array('null' => true, 'limit' => 255))
            ->addColumn('label2', 'string', array('null' => true, 'limit' => 255))
            ->addColumn('label3', 'string', array('null' => true, 'limit' => 255))
            ->addColumn('label4', 'string', array('null' => true, 'limit' => 255))
            ->addColumn('description1', 'text', array('null' => true, 'limit' => MysqlAdapter::TEXT_LONG))
            ->addColumn('description2', 'text', array('null' => true, 'limit' => MysqlAdapter::TEXT_LONG))
            ->addColumn('description3', 'text', array('null' => true, 'limit' => MysqlAdapter::TEXT_LONG))
            ->addColumn('description4', 'text', array('null' => true, 'limit' => MysqlAdapter::TEXT_LONG))
            ->addColumn('seuil1', 'integer', array('null' => true, 'default' => '0', 'limit' => 11))
            ->addColumn('seuil2', 'integer', array('null' => true, 'default' => '0', 'limit' => 11))
            ->addColumn('seuil_rolf1', 'integer', array('null' => true, 'default' => '0', 'limit' => 11))
            ->addColumn('seuil_rolf2', 'integer', array('null' => true, 'default' => '0', 'limit' => 11))
            ->addColumn('seuil_traitement', 'integer', array('null' => true, 'default' => '0', 'limit' => 11))
            ->addColumn('init_anr_context', 'integer', array('null' => true, 'default' => '0', 'limit' => MysqlAdapter::INT_TINY))
            ->addColumn('init_eval_context', 'integer', array('null' => true, 'default' => '0', 'limit' => MysqlAdapter::INT_TINY))
            ->addColumn('init_risk_context', 'integer', array('null' => true, 'default' => '0', 'limit' => MysqlAdapter::INT_TINY))
            ->addColumn('init_def_context', 'integer', array('null' => true, 'default' => '0', 'limit' => MysqlAdapter::INT_TINY))
            ->addColumn('init_livrable_done', 'integer', array('null' => true, 'default' => '0', 'limit' => MysqlAdapter::INT_TINY))
            ->addColumn('model_impacts', 'integer', array('null' => true, 'default' => '0', 'limit' => MysqlAdapter::INT_TINY))
            ->addColumn('model_summary', 'integer', array('null' => true, 'default' => '0', 'limit' => MysqlAdapter::INT_TINY))
            ->addColumn('model_livrable_done', 'integer', array('null' => true, 'default' => '0', 'limit' => MysqlAdapter::INT_TINY))
            ->addColumn('eval_risks', 'integer', array('null' => true, 'default' => '0', 'limit' => MysqlAdapter::INT_TINY))
            ->addColumn('eval_plan_risks', 'integer', array('null' => true, 'default' => '0', 'limit' => MysqlAdapter::INT_TINY))
            ->addColumn('eval_livrable_done', 'integer', array('null' => true, 'default' => '0', 'limit' => MysqlAdapter::INT_TINY))
            ->addColumn('manage_risks', 'integer', array('null' => true, 'default' => '0', 'limit' => MysqlAdapter::INT_TINY))
            ->addColumn('context_ana_risk', 'text', array('null' => true, 'limit' => MysqlAdapter::TEXT_LONG))
            ->addColumn('context_gest_risk', 'text', array('null' => true, 'limit' => MysqlAdapter::TEXT_LONG))
            ->addColumn('synth_threat', 'text', array('null' => true, 'limit' => MysqlAdapter::TEXT_LONG))
            ->addColumn('synth_act', 'text', array('null' => true, 'limit' => MysqlAdapter::TEXT_LONG))
            ->addColumn('cache_model_show_rolf_brut', 'integer', array('null' => true, 'default' => '0', 'limit' => MysqlAdapter::INT_TINY))
            ->addColumn('show_rolf_brut', 'integer', array('null' => true, 'default' => '0', 'limit' => MysqlAdapter::INT_TINY))
            ->addColumn('creator', 'string', array('null' => true, 'limit' => 255))
            ->addColumn('created_at', 'datetime', array('null' => true))
            ->addColumn('updater', 'string', array('null' => true, 'limit' => 255))
            ->addColumn('updated_at', 'datetime', array('null' => true))
            ->addIndex(array('snapshot_id'))
            ->addIndex(array('snapshot_ref_id'))
            ->create();
        $table->changeColumn('id', 'integer',array('identity'=>true,'signed'=>false))->update();


        // Migration for table anrs_objects_categories
        $table = $this->table('anrs_objects_categories');
        $table
            ->addColumn('anr_id', 'integer', array('null' => true, 'signed' => false))
            ->addColumn('object_category_id', 'integer', array('null' => true, 'signed' => false))
            ->addColumn('position', 'integer', array('null' => true, 'default' => '1', 'limit' => 11))
            ->addColumn('creator', 'string', array('null' => true, 'limit' => 255))
            ->addColumn('created_at', 'datetime', array('null' => true))
            ->addColumn('updater', 'string', array('null' => true, 'limit' => 255))
            ->addColumn('updated_at', 'datetime', array('null' => true))
            ->addIndex(array('anr_id'))
            ->addIndex(array('object_category_id'))
            ->create();
        $table->changeColumn('id', 'integer',array('identity'=>true,'signed'=>false))->update();


        // Migration for table assets
        $table = $this->table('assets');
        $table
            ->addColumn('anr_id', 'integer', array('null' => true, 'default' => '0', 'signed' => false))
            ->addColumn('mode', 'integer', array('null' => true, 'default' => '1', 'limit' => MysqlAdapter::INT_TINY))
            ->addColumn('type', 'integer', array('null' => true, 'default' => '3', 'limit' => MysqlAdapter::INT_TINY))
            ->addColumn('code', 'char', array('null' => true, 'limit' => 100))
            ->addColumn('label1', 'string', array('null' => true, 'limit' => 255))
            ->addColumn('label2', 'string', array('null' => true, 'limit' => 255))
            ->addColumn('label3', 'string', array('null' => true, 'limit' => 255))
            ->addColumn('label4', 'string', array('null' => true, 'limit' => 255))
            ->addColumn('description1', 'text', array('null' => true, 'limit' => MysqlAdapter::TEXT_LONG))
            ->addColumn('description2', 'text', array('null' => true, 'limit' => MysqlAdapter::TEXT_LONG))
            ->addColumn('description3', 'text', array('null' => true, 'limit' => MysqlAdapter::TEXT_LONG))
            ->addColumn('description4', 'text', array('null' => true, 'limit' => MysqlAdapter::TEXT_LONG))
            ->addColumn('status', 'integer', array('null' => true, 'default' => '1', 'limit' => MysqlAdapter::INT_TINY))
            ->addColumn('creator', 'string', array('null' => true, 'limit' => 255))
            ->addColumn('created_at', 'datetime', array('null' => true))
            ->addColumn('updater', 'string', array('null' => true, 'limit' => 255))
            ->addColumn('updated_at', 'datetime', array('null' => true))
            ->addIndex(array('anr_id','code'))
            ->addIndex(array('anr_id'))
            ->create();
        $table->changeColumn('id', 'integer',array('identity'=>true,'signed'=>false))->update();


        // Migration for table assets_models
        $table = $this->table('assets_models', array('id' => false, 'primary_key' => array('asset_id', 'model_id')));
        $table
            ->addColumn('asset_id', 'integer', array('signed' => false))
            ->addColumn('model_id', 'integer', array('signed' => false))
            ->addIndex(array('asset_id'))
            ->addIndex(array('model_id'))
            ->create();


        // Migration for table cities
        $table = $this->table('cities');
        $table
            ->addColumn('country_id', 'integer', array('null' => true, 'signed' => false))
            ->addColumn('label', 'string', array('limit' => 255))
            ->addColumn('creator', 'string', array('null' => true, 'limit' => 255))
            ->addColumn('created_at', 'datetime', array('null' => true))
            ->addColumn('updater', 'string', array('null' => true, 'limit' => 255))
            ->addColumn('updated_at', 'datetime', array('null' => true))
            ->addIndex(array('country_id'))
            ->create();
        $table->changeColumn('id', 'integer',array('identity'=>true,'signed'=>false))->update();


        // Migration for table countries
        $table = $this->table('countries');
        $table
            ->addColumn('iso', 'string', array('default' => '', 'limit' => 2))
            ->addColumn('iso3', 'string', array('null' => true, 'limit' => 3))
            ->addColumn('name', 'string', array('null' => true, 'default' => '', 'limit' => 80))
            ->create();
        $table->changeColumn('id', 'integer',array('identity'=>true,'signed'=>false))->update();


        // Migration for table deliveries
        $table = $this->table('deliveries');
        $table
            ->addColumn('anr_id', 'integer', array('null' => true, 'signed' => false))
            ->addColumn('typedoc', 'integer', array('default' => '0', 'limit' => 11))
            ->addColumn('name', 'text', array())
            ->addColumn('version', 'float', array('default' => '0.00','precision'=>11,'scale'=>2))
            ->addColumn('status', 'integer', array('default' => '0', 'limit' => 11))
            ->addColumn('classification', 'string', array('null' => true, 'limit' => 255))
            ->addColumn('resp_customer', 'string', array('default' => '0', 'limit' => 255))
            ->addColumn('resp_smile', 'string', array('default' => '0', 'limit' => 255))
            ->addColumn('summary_eval_risk', 'text', array('limit' => MysqlAdapter::TEXT_LONG))
            ->addColumn('creator', 'string', array('null' => true, 'limit' => 255))
            ->addColumn('created_at', 'datetime', array('null' => true))
            ->addColumn('updater', 'string', array('null' => true, 'limit' => 255))
            ->addColumn('updated_at', 'datetime', array('null' => true))
            ->addIndex(array('anr_id'))
            ->create();
        $table->changeColumn('id', 'integer',array('identity'=>true,'signed'=>false))->update();


        // Migration for table deliveries_models
        $table = $this->table('deliveries_models');
        $table
            ->addColumn('anr_id', 'integer', array('null' => true, 'signed' => false))
            ->addColumn('typedoc', 'integer', array('default' => '0', 'limit' => MysqlAdapter::INT_TINY))
            ->addColumn('name1', 'string', array('limit' => 255))
            ->addColumn('name2', 'string', array('limit' => 255))
            ->addColumn('name3', 'string', array('limit' => 255))
            ->addColumn('name4', 'string', array('limit' => 255))
            ->addColumn('content', 'blob', array('default' => '', 'limit'=>MysqlAdapter::BLOB_LONG))
            ->addColumn('description1', 'string', array('default' => '', 'limit' => 255))
            ->addColumn('description2', 'string', array('default' => '', 'limit' => 255))
            ->addColumn('description3', 'string', array('default' => '', 'limit' => 255))
            ->addColumn('description4', 'string', array('default' => '', 'limit' => 255))
            ->addColumn('creator', 'string', array('null' => true, 'limit' => 255))
            ->addColumn('created_at', 'datetime', array('null' => true))
            ->addColumn('updater', 'string', array('null' => true, 'limit' => 255))
            ->addColumn('updated_at', 'datetime', array('null' => true))
            ->addIndex(array('anr_id'))
            ->addIndex(array('typedoc'))
            ->create();
        $table->changeColumn('id', 'integer',array('identity'=>true,'signed'=>false))->update();


        // Migration for table rss_feeds
        $table = $this->table('rss_feeds');
        $table
            ->addColumn('guid', 'char', array('null' => true, 'limit' => 255))
            ->addColumn('type', 'integer', array('null' => true, 'default' => '1', 'limit' => MysqlAdapter::INT_TINY))
            ->addColumn('title', 'string', array('null' => true, 'limit' => 255))
            ->addColumn('link', 'string', array('null' => true, 'limit' => 255))
            ->addColumn('description', 'text', array('null' => true))
            ->addColumn('pubdate', 'datetime', array('null' => true))
            ->addColumn('picto', 'string', array('null' => true, 'limit' => 255))
            ->addIndex(array('guid'))
            ->create();
        $table->changeColumn('id', 'integer',array('identity'=>true,'signed'=>false))->update();


        // Migration for table guides
        $table = $this->table('guides');
        $table
            ->addColumn('anr_id', 'integer', array('null' => true, 'signed' => false))
            ->addColumn('type', 'integer', array('limit' => MysqlAdapter::INT_TINY))
            ->addColumn('description1', 'text', array())
            ->addColumn('description2', 'text', array())
            ->addColumn('description3', 'text', array())
            ->addColumn('description4', 'text', array())
            ->addColumn('is_with_items', 'integer', array('limit' => MysqlAdapter::INT_TINY))
            ->addColumn('creator', 'string', array('null' => true, 'limit' => 255))
            ->addColumn('created_at', 'datetime', array('null' => true))
            ->addColumn('updater', 'string', array('null' => true, 'limit' => 255))
            ->addColumn('updated_at', 'datetime', array('null' => true))
            ->addIndex(array('anr_id'))
            ->create();
        $table->changeColumn('id', 'integer',array('identity'=>true,'signed'=>false))->update();


        // Migration for table guides_items
        $table = $this->table('guides_items');
        $table
            ->addColumn('anr_id', 'integer', array('null' => true, 'signed' => false))
            ->addColumn('guide_id', 'integer', array('null' => true, 'signed' => false))
            ->addColumn('description1', 'text', array())
            ->addColumn('description2', 'text', array())
            ->addColumn('description3', 'text', array())
            ->addColumn('description4', 'text', array())
            ->addColumn('position', 'integer', array('default' => '0', 'limit' => 11))
            ->addColumn('creator', 'string', array('null' => true, 'limit' => 255))
            ->addColumn('created_at', 'datetime', array('null' => true))
            ->addColumn('updater', 'string', array('null' => true, 'limit' => 255))
            ->addColumn('updated_at', 'datetime', array('null' => true))
            ->addIndex(array('anr_id'))
            ->addIndex(array('guide_id'))
            ->addIndex(array('position'))
            ->create();
        $table->changeColumn('id', 'integer',array('identity'=>true,'signed'=>false))->update();


        // Migration for table instances
        $table = $this->table('instances');
        $table
            ->addColumn('anr_id', 'integer', array('null' => true, 'signed' => false))
            ->addColumn('asset_id', 'integer', array('null' => true, 'signed' => false))
            ->addColumn('object_id', 'integer', array('null' => true, 'signed' => false))
            ->addColumn('root_id', 'integer', array('null' => true, 'signed' => false))
            ->addColumn('name1', 'string', array('null' => true, 'limit' => 255))
            ->addColumn('name2', 'string', array('null' => true, 'limit' => 255))
            ->addColumn('name3', 'string', array('null' => true, 'limit' => 255))
            ->addColumn('name4', 'string', array('null' => true, 'limit' => 255))
            ->addColumn('label1', 'string', array('null' => true, 'limit' => 255))
            ->addColumn('label2', 'string', array('null' => true, 'limit' => 255))
            ->addColumn('label3', 'string', array('null' => true, 'limit' => 255))
            ->addColumn('label4', 'string', array('null' => true, 'limit' => 255))
            ->addColumn('description1', 'text', array('null' => true, 'limit' => MysqlAdapter::TEXT_LONG))
            ->addColumn('description2', 'text', array('null' => true, 'limit' => MysqlAdapter::TEXT_LONG))
            ->addColumn('description3', 'text', array('null' => true, 'limit' => MysqlAdapter::TEXT_LONG))
            ->addColumn('description4', 'text', array('null' => true, 'limit' => MysqlAdapter::TEXT_LONG))
            ->addColumn('disponibility', 'decimal', array('null' => true, 'default' => '0.00000','precision'=>11,'scale'=>2))
            ->addColumn('level', 'integer', array('null' => true, 'default' => '1', 'limit' => MysqlAdapter::INT_TINY))
            ->addColumn('asset_type', 'integer', array('null' => true, 'default' => '3', 'limit' => MysqlAdapter::INT_TINY))
            ->addColumn('exportable', 'integer', array('null' => true, 'default' => '1', 'limit' => MysqlAdapter::INT_TINY))
            ->addColumn('creator', 'string', array('null' => true, 'limit' => 255))
            ->addColumn('created_at', 'datetime', array('null' => true))
            ->addColumn('updater', 'string', array('null' => true, 'limit' => 255))
            ->addColumn('updated_at', 'datetime', array('null' => true))
            ->addIndex(array('anr_id'))
            ->addIndex(array('asset_id'))
            ->addIndex(array('object_id'))
            ->addIndex(array('root_id'))
            ->create();
        $table->changeColumn('id', 'integer',array('identity'=>true,'signed'=>false))->update();


        // Migration for table instances_instances
        $table = $this->table('instances_instances');
        $table
            ->addColumn('anr_id', 'integer', array('null' => true, 'signed' => false))
            ->addColumn('father_id', 'integer', array('null' => true, 'signed' => false))
            ->addColumn('child_id', 'integer', array('null' => true, 'signed' => false))
            ->addColumn('position', 'integer', array('null' => true, 'default' => '0', 'limit' => 11))
            ->addColumn('c', 'integer', array('null' => true, 'default' => '-1', 'limit' => 11))
            ->addColumn('i', 'integer', array('null' => true, 'default' => '-1', 'limit' => 11))
            ->addColumn('d', 'integer', array('null' => true, 'default' => '-1', 'limit' => 11))
            ->addColumn('ch', 'integer', array('null' => true, 'default' => '0', 'limit' => MysqlAdapter::INT_TINY))
            ->addColumn('ih', 'integer', array('null' => true, 'default' => '0', 'limit' => MysqlAdapter::INT_TINY))
            ->addColumn('dh', 'integer', array('null' => true, 'default' => '0', 'limit' => MysqlAdapter::INT_TINY))
            ->addColumn('creator', 'string', array('null' => true, 'limit' => 255))
            ->addColumn('created_at', 'datetime', array('null' => true))
            ->addColumn('updater', 'string', array('null' => true, 'limit' => 255))
            ->addColumn('updated_at', 'datetime', array('null' => true))
            ->addIndex(array('anr_id'))
            ->addIndex(array('father_id'))
            ->addIndex(array('child_id'))
            ->addIndex(array('position'))
            ->create();
        $table->changeColumn('id', 'integer',array('identity'=>true,'signed'=>false))->update();


        // Migration for table instances_instances_consequences
        $table = $this->table('instances_instances_consequences');
        $table
            ->addColumn('anr_id', 'integer', array('null' => true, 'default' => '0', 'signed' => false))
            ->addColumn('instance_instance_id', 'integer', array('null' => true, 'default' => '0', 'signed' => false))
            ->addColumn('instance_id', 'integer', array('null' => true, 'default' => '0', 'signed' => false))
            ->addColumn('object_id', 'integer', array('null' => true, 'default' => '0', 'signed' => false))
            ->addColumn('scale_impact_type_id', 'integer', array('null' => true, 'default' => '0', 'signed' => false))
            ->addColumn('is_hidden', 'integer', array('null' => true, 'default' => '0', 'limit' => MysqlAdapter::INT_TINY))
            ->addColumn('locally_touched', 'integer', array('null' => true, 'default' => '0', 'limit' => MysqlAdapter::INT_TINY))
            ->addColumn('c', 'integer', array('null' => true, 'default' => '-1', 'limit' => 11))
            ->addColumn('ch', 'integer', array('null' => true, 'default' => '1', 'limit' => MysqlAdapter::INT_TINY))
            ->addColumn('i', 'integer', array('null' => true, 'default' => '-1', 'limit' => 11))
            ->addColumn('ih', 'integer', array('null' => true, 'default' => '1', 'limit' => MysqlAdapter::INT_TINY))
            ->addColumn('d', 'integer', array('null' => true, 'default' => '-1', 'limit' => 11))
            ->addColumn('dh', 'integer', array('null' => true, 'default' => '1', 'limit' => MysqlAdapter::INT_TINY))
            ->addColumn('creator', 'string', array('null' => true, 'limit' => 255))
            ->addColumn('created_at', 'datetime', array('null' => true))
            ->addColumn('updater', 'string', array('null' => true, 'limit' => 255))
            ->addColumn('updated_at', 'datetime', array('null' => true))
            ->addIndex(array('anr_id'))
            ->addIndex(array('instance_instance_id'))
            ->addIndex(array('instance_id'))
            ->addIndex(array('object_id'))
            ->addIndex(array('scale_impact_type_id'))
            ->create();
        $table->changeColumn('id', 'integer',array('identity'=>true,'signed'=>false))->update();


        // Migration for table instances_risks
        $table = $this->table('instances_risks');
        $table
            ->addColumn('anr_id', 'integer', array('null' => true, 'signed' => false))
            ->addColumn('amv_id', 'integer', array('null' => true, 'signed' => false))
            ->addColumn('instance_instance_id', 'integer', array('null' => true, 'signed' => false))
            ->addColumn('specific', 'integer', array('null' => true, 'default' => '0', 'limit' => MysqlAdapter::INT_TINY))
            ->addColumn('asset_id', 'integer', array('null' => true, 'signed' => false))
            ->addColumn('threat_id', 'integer', array('null' => true, 'signed' => false))
            ->addColumn('vulnerability_id', 'integer', array('null' => true, 'signed' => false))
            ->addColumn('mh', 'integer', array('default' => '1', 'limit' => MysqlAdapter::INT_TINY))
            ->addColumn('threat_rate', 'integer', array('default' => '-1', 'limit' => 11))
            ->addColumn('vulnerability_rate', 'integer', array('default' => '-1', 'limit' => 11))
            ->addColumn('kind_of_measure', 'integer', array('null' => true, 'default' => '5', 'limit' => MysqlAdapter::INT_TINY))
            ->addColumn('reduction_amount', 'integer', array('null' => true, 'default' => '0', 'limit' => 3))
            ->addColumn('comment', 'text', array('null' => true, 'limit' => MysqlAdapter::TEXT_LONG))
            ->addColumn('comment_after', 'text', array('null' => true, 'limit' => MysqlAdapter::TEXT_LONG))
            ->addColumn('risk_c', 'integer', array('null' => true, 'default' => '-1', 'limit' => 11))
            ->addColumn('risk_i', 'integer', array('null' => true, 'default' => '-1', 'limit' => 11))
            ->addColumn('risk_d', 'integer', array('null' => true, 'default' => '-1', 'limit' => 11))
            ->addColumn('cache_max_risk', 'integer', array('null' => true, 'default' => '-1', 'limit' => 11))
            ->addColumn('cache_targeted_risk', 'integer', array('null' => true, 'default' => '-1', 'limit' => 11))
            ->addColumn('creator', 'string', array('null' => true, 'limit' => 255))
            ->addColumn('created_at', 'datetime', array('null' => true))
            ->addColumn('updater', 'string', array('null' => true, 'limit' => 255))
            ->addColumn('updated_at', 'datetime', array('null' => true))
            ->addIndex(array('anr_id'))
            ->addIndex(array('amv_id'))
            ->addIndex(array('instance_instance_id'))
            ->addIndex(array('asset_id'))
            ->addIndex(array('threat_id'))
            ->addIndex(array('vulnerability_id'))
            ->create();
        $table->changeColumn('id', 'integer',array('identity'=>true,'signed'=>false))->update();


        // Migration for table instances_risks_op
        $table = $this->table('instances_risks_op');
        $table
            ->addColumn('anr_id', 'integer', array('null' => true, 'signed' => false))
            ->addColumn('instance_id', 'integer', array('null' => true, 'signed' => false))
            ->addColumn('object_id', 'integer', array('null' => true, 'signed' => false))
            ->addColumn('rolf_risk_id', 'integer', array('null' => true, 'signed' => false))
            ->addColumn('risk_cache_code', 'char', array('null' => true, 'limit' => 100))
            ->addColumn('risk_cache_label1', 'string', array('null' => true, 'limit' => 255))
            ->addColumn('risk_cache_label2', 'string', array('null' => true, 'limit' => 255))
            ->addColumn('risk_cache_label3', 'string', array('null' => true, 'limit' => 255))
            ->addColumn('risk_cache_label4', 'string', array('null' => true, 'limit' => 255))
            ->addColumn('risk_cache_description1', 'text', array('null' => true, 'limit' => MysqlAdapter::TEXT_LONG))
            ->addColumn('risk_cache_description2', 'text', array('null' => true, 'limit' => MysqlAdapter::TEXT_LONG))
            ->addColumn('risk_cache_description3', 'text', array('null' => true, 'limit' => MysqlAdapter::TEXT_LONG))
            ->addColumn('risk_cache_description4', 'text', array('null' => true, 'limit' => MysqlAdapter::TEXT_LONG))
            ->addColumn('brut_prob', 'integer', array('null' => true, 'default' => '-1', 'limit' => 11))
            ->addColumn('brut_r', 'integer', array('null' => true, 'default' => '-1', 'limit' => 11))
            ->addColumn('brut_o', 'integer', array('null' => true, 'default' => '-1', 'limit' => 11))
            ->addColumn('brut_l', 'integer', array('null' => true, 'default' => '-1', 'limit' => 11))
            ->addColumn('brut_f', 'integer', array('null' => true, 'default' => '-1', 'limit' => 11))
            ->addColumn('cache_brut_risk', 'integer', array('null' => true, 'default' => '-1', 'limit' => 11))
            ->addColumn('net_prob', 'integer', array('null' => true, 'default' => '-1', 'limit' => 11))
            ->addColumn('net_r', 'integer', array('null' => true, 'default' => '-1', 'limit' => 11))
            ->addColumn('net_o', 'integer', array('null' => true, 'default' => '-1', 'limit' => 11))
            ->addColumn('net_l', 'integer', array('null' => true, 'default' => '-1', 'limit' => 11))
            ->addColumn('net_f', 'integer', array('null' => true, 'default' => '-1', 'limit' => 11))
            ->addColumn('cache_net_risk', 'integer', array('null' => true, 'default' => '-1', 'limit' => 11))
            ->addColumn('targeted_prob', 'integer', array('null' => true, 'default' => '-1', 'limit' => 11))
            ->addColumn('targeted_r', 'integer', array('null' => true, 'default' => '-1', 'limit' => 11))
            ->addColumn('targeted_o', 'integer', array('null' => true, 'default' => '-1', 'limit' => 11))
            ->addColumn('targeted_l', 'integer', array('null' => true, 'default' => '-1', 'limit' => 11))
            ->addColumn('targeted_f', 'integer', array('null' => true, 'default' => '-1', 'limit' => 11))
            ->addColumn('cache_targeted_risk', 'integer', array('null' => true, 'default' => '-1', 'limit' => 11))
            ->addColumn('kind_of_measure', 'integer', array('null' => true, 'default' => '5', 'limit' => MysqlAdapter::INT_TINY))
            ->addColumn('comment', 'text', array('null' => true, 'limit' => MysqlAdapter::TEXT_LONG))
            ->addColumn('mitigation', 'text', array('null' => true, 'limit' => MysqlAdapter::TEXT_LONG))
            ->addColumn('specific', 'integer', array('null' => true, 'default' => '0', 'limit' => MysqlAdapter::INT_TINY))
            ->addColumn('creator', 'string', array('null' => true, 'limit' => 255))
            ->addColumn('created_at', 'datetime', array('null' => true))
            ->addColumn('updater', 'string', array('null' => true, 'limit' => 255))
            ->addColumn('updated_at', 'datetime', array('null' => true))
            ->addIndex(array('anr_id'))
            ->addIndex(array('instance_id'))
            ->addIndex(array('object_id'))
            ->addIndex(array('rolf_risk_id'))
            ->create();
        $table->changeColumn('id', 'integer',array('identity'=>true,'signed'=>false))->update();


        // Migration for table interviews
        $table = $this->table('interviews');
        $table
            ->addColumn('anr_id', 'integer', array('null' => true, 'signed' => false))
            ->addColumn('date', 'datetime', array('null' => true))
            ->addColumn('service', 'text', array('null' => true))
            ->addColumn('content', 'text', array('null' => true, 'limit' => MysqlAdapter::TEXT_LONG))
            ->addColumn('creator', 'string', array('null' => true, 'limit' => 255))
            ->addColumn('created_at', 'datetime', array('null' => true))
            ->addColumn('updater', 'string', array('null' => true, 'limit' => 255))
            ->addColumn('updated_at', 'datetime', array('null' => true))
            ->addIndex(array('anr_id'))
            ->create();
        $table->changeColumn('id', 'integer',array('identity'=>true,'signed'=>false))->update();


        // Migration for table measures
        $table = $this->table('measures');
        $table
            ->addColumn('anr_id', 'integer', array('null' => true, 'signed' => false))
            ->addColumn('code', 'string', array('null' => true, 'limit' => 255))
            ->addColumn('description1', 'text', array('null' => true, 'limit' => MysqlAdapter::TEXT_LONG))
            ->addColumn('description2', 'text', array('null' => true, 'limit' => MysqlAdapter::TEXT_LONG))
            ->addColumn('description3', 'text', array('null' => true, 'limit' => MysqlAdapter::TEXT_LONG))
            ->addColumn('description4', 'text', array('null' => true, 'limit' => MysqlAdapter::TEXT_LONG))
            ->addColumn('status', 'integer', array('null' => true, 'default' => '1', 'limit' => MysqlAdapter::INT_TINY))
            ->addColumn('creator', 'string', array('null' => true, 'limit' => 255))
            ->addColumn('created_at', 'datetime', array('null' => true))
            ->addColumn('updater', 'string', array('null' => true, 'limit' => 255))
            ->addColumn('updated_at', 'datetime', array('null' => true))
            ->addIndex(array('anr_id'))
            ->create();
        $table->changeColumn('id', 'integer',array('identity'=>true,'signed'=>false))->update();


        // Migration for table models
        $table = $this->table('models');
        $table
            ->addColumn('anr_id', 'integer', array('null' => true, 'signed' => false))
            ->addColumn('label1', 'string', array('null' => true, 'limit' => 255))
            ->addColumn('label2', 'string', array('null' => true, 'limit' => 255))
            ->addColumn('label3', 'string', array('null' => true, 'limit' => 255))
            ->addColumn('label4', 'string', array('null' => true, 'limit' => 255))
            ->addColumn('description1', 'text', array('null' => true, 'limit' => MysqlAdapter::TEXT_LONG))
            ->addColumn('description2', 'text', array('null' => true, 'limit' => MysqlAdapter::TEXT_LONG))
            ->addColumn('description3', 'text', array('null' => true, 'limit' => MysqlAdapter::TEXT_LONG))
            ->addColumn('description4', 'text', array('null' => true, 'limit' => MysqlAdapter::TEXT_LONG))
            ->addColumn('status', 'integer', array('null' => true, 'default' => '1', 'limit' => MysqlAdapter::INT_TINY))
            ->addColumn('is_scales_updatable', 'integer', array('null' => true, 'default' => '1', 'limit' => MysqlAdapter::INT_TINY))
            ->addColumn('is_default', 'integer', array('null' => true, 'default' => '0', 'limit' => MysqlAdapter::INT_TINY))
            ->addColumn('is_deleted', 'integer', array('null' => true, 'default' => '0', 'limit' => MysqlAdapter::INT_TINY))
            ->addColumn('is_generic', 'integer', array('null' => true, 'default' => '0', 'limit' => MysqlAdapter::INT_TINY))
            ->addColumn('is_regulator', 'integer', array('null' => true, 'default' => '0', 'limit' => MysqlAdapter::INT_TINY))
            ->addColumn('show_rolf_brut', 'integer', array('null' => true, 'default' => '0', 'limit' => MysqlAdapter::INT_TINY))
            ->addColumn('creator', 'string', array('null' => true, 'limit' => 255))
            ->addColumn('created_at', 'datetime', array('null' => true))
            ->addColumn('updater', 'string', array('null' => true, 'limit' => 255))
            ->addColumn('updated_at', 'datetime', array('null' => true))
            ->addIndex(array('anr_id'))
            ->create();
        $table->changeColumn('id', 'integer',array('identity'=>true,'signed'=>false))->update();


        // Migration for table objects
        $table = $this->table('objects');
        $table
            ->addColumn('anr_id', 'integer', array('null' => true, 'signed' => false))
            ->addColumn('object_category_id', 'integer', array('null' => true, 'signed' => false))
            ->addColumn('asset_id', 'integer', array('null' => true, 'signed' => false))
            ->addColumn('source_bdc_object_id', 'integer', array('null' => true, 'signed' => false))
            ->addColumn('rolf_tag_id', 'integer', array('null' => true, 'signed' => false))
            ->addColumn('type', 'char', array('null' => true, 'default' => 'anr', 'limit' => 3))
            ->addColumn('mode', 'integer', array('null' => true, 'default' => '1', 'limit' => MysqlAdapter::INT_TINY))
            ->addColumn('scope', 'integer', array('null' => true, 'default' => '1', 'limit' => MysqlAdapter::INT_TINY))
            ->addColumn('name1', 'string', array('null' => true, 'limit' => 255))
            ->addColumn('name2', 'string', array('null' => true, 'limit' => 255))
            ->addColumn('name3', 'string', array('null' => true, 'limit' => 255))
            ->addColumn('name4', 'string', array('null' => true, 'limit' => 255))
            ->addColumn('label1', 'string', array('null' => true, 'limit' => 255))
            ->addColumn('label2', 'string', array('null' => true, 'limit' => 255))
            ->addColumn('label3', 'string', array('null' => true, 'limit' => 255))
            ->addColumn('label4', 'string', array('null' => true, 'limit' => 255))
            ->addColumn('description1', 'text', array('null' => true, 'limit' => MysqlAdapter::TEXT_LONG))
            ->addColumn('description2', 'text', array('null' => true, 'limit' => MysqlAdapter::TEXT_LONG))
            ->addColumn('description3', 'text', array('null' => true, 'limit' => MysqlAdapter::TEXT_LONG))
            ->addColumn('description4', 'text', array('null' => true, 'limit' => MysqlAdapter::TEXT_LONG))
            ->addColumn('disponibility', 'decimal', array('null' => true, 'default' => '0.00000','precision'=>11,'scale'=>5))
            ->addColumn('c', 'integer', array('null' => true, 'default' => '-1', 'limit' => 11))
            ->addColumn('i', 'integer', array('null' => true, 'default' => '-1', 'limit' => 11))
            ->addColumn('d', 'integer', array('null' => true, 'default' => '-1', 'limit' => 11))
            ->addColumn('position', 'integer', array('null' => true, 'default' => '1', 'limit' => 11))
            ->addColumn('token_import', 'char', array('null' => true, 'limit' => 13))
            ->addColumn('original_name', 'string', array('null' => true, 'limit' => 255))
            ->addColumn('creator', 'string', array('null' => true, 'limit' => 255))
            ->addColumn('created_at', 'datetime', array('null' => true))
            ->addColumn('updater', 'string', array('null' => true, 'limit' => 255))
            ->addColumn('updated_at', 'datetime', array('null' => true))
            ->addIndex(array('anr_id'))
            ->addIndex(array('object_category_id'))
            ->addIndex(array('asset_id'))
            ->addIndex(array('source_bdc_object_id'))
            ->addIndex(array('rolf_tag_id'))
            ->create();
        $table->changeColumn('id', 'integer',array('identity'=>true,'signed'=>false))->update();


        // Migration for table objects_categories
        $table = $this->table('objects_categories');
        $table
            ->addColumn('anr_id', 'integer', array('null' => true, 'signed' => false))
            ->addColumn('root_id', 'integer', array('null' => true, 'signed' => false))
            ->addColumn('parent_id', 'integer', array('null' => true, 'signed' => false))
            ->addColumn('label1', 'text', array('null' => true, 'limit' => MysqlAdapter::TEXT_LONG))
            ->addColumn('label2', 'text', array('null' => true, 'limit' => MysqlAdapter::TEXT_LONG))
            ->addColumn('label3', 'text', array('null' => true, 'limit' => MysqlAdapter::TEXT_LONG))
            ->addColumn('label4', 'text', array('null' => true, 'limit' => MysqlAdapter::TEXT_LONG))
            ->addColumn('position', 'integer', array('null' => true, 'default' => '1', 'limit' => 11))
            ->addColumn('creator', 'string', array('null' => true, 'limit' => 255))
            ->addColumn('created_at', 'datetime', array('null' => true))
            ->addColumn('updater', 'string', array('null' => true, 'limit' => 255))
            ->addColumn('updated_at', 'datetime', array('null' => true))
            ->addIndex(array('anr_id'))
            ->addIndex(array('root_id'))
            ->addIndex(array('parent_id'))
            ->addIndex(array('position'))
            ->create();
        $table->changeColumn('id', 'integer',array('identity'=>true,'signed'=>false))->update();


        // Migration for table objects_objects
        $table = $this->table('objects_objects');
        $table
            ->addColumn('anr_id', 'integer', array('null' => true, 'signed' => false))
            ->addColumn('father_id', 'integer', array('null' => true, 'signed' => false))
            ->addColumn('child_id', 'integer', array('null' => true, 'signed' => false))
            ->addColumn('creator', 'string', array('null' => true, 'limit' => 255))
            ->addColumn('created_at', 'datetime', array('null' => true))
            ->addColumn('updater', 'string', array('null' => true, 'limit' => 255))
            ->addColumn('updated_at', 'datetime', array('null' => true))
            ->addIndex(array('anr_id'))
            ->addIndex(array('father_id'))
            ->addIndex(array('child_id'))
            ->create();
        $table->changeColumn('id', 'integer',array('identity'=>true,'signed'=>false))->update();


        // Migration for table objects_risks
        $table = $this->table('objects_risks');
        $table
            ->addColumn('anr_id', 'integer', array('null' => true, 'signed' => false))
            ->addColumn('object_id', 'integer', array('null' => true, 'signed' => false))
            ->addColumn('specific', 'integer', array('default' => '0', 'limit' => MysqlAdapter::INT_TINY))
            ->addColumn('amv_id', 'integer', array('null' => true, 'signed' => false))
            ->addColumn('asset_id', 'integer', array('null' => true, 'signed' => false))
            ->addColumn('threat_id', 'integer', array('null' => true, 'signed' => false))
            ->addColumn('vulnerability_id', 'integer', array('null' => true, 'signed' => false))
            ->addColumn('mh', 'integer', array('default' => '1', 'limit' => MysqlAdapter::INT_TINY))
            ->addColumn('threat_rate', 'integer', array('default' => '-1', 'limit' => 11))
            ->addColumn('vulnerability_rate', 'integer', array('default' => '-1', 'limit' => 11))
            ->addColumn('kind_of_measure', 'integer', array('null' => true, 'default' => '5', 'limit' => MysqlAdapter::INT_TINY))
            ->addColumn('reduction_amount', 'integer', array('null' => true, 'default' => '0', 'limit' => 11))
            ->addColumn('comment', 'text', array('null' => true, 'limit' => MysqlAdapter::TEXT_LONG))
            ->addColumn('risk_c', 'integer', array('null' => true, 'default' => '-1', 'limit' => 11))
            ->addColumn('risk_i', 'integer', array('null' => true, 'default' => '-1', 'limit' => 11))
            ->addColumn('risk_d', 'integer', array('null' => true, 'default' => '-1', 'limit' => 11))
            ->addColumn('creator', 'string', array('null' => true, 'limit' => 255))
            ->addColumn('created_at', 'datetime', array('null' => true))
            ->addColumn('updater', 'string', array('null' => true, 'limit' => 255))
            ->addColumn('updated_at', 'datetime', array('null' => true))
            ->addIndex(array('anr_id'))
            ->addIndex(array('object_id'))
            ->addIndex(array('amv_id'))
            ->addIndex(array('asset_id'))
            ->addIndex(array('threat_id'))
            ->addIndex(array('vulnerability_id'))
            ->create();
        $table->changeColumn('id', 'integer',array('identity'=>true,'signed'=>false))->update();


        // Migration for table rolf_categories
        $table = $this->table('rolf_categories');
        $table
            ->addColumn('anr_id', 'integer', array('null' => true, 'signed' => false))
            ->addColumn('code', 'char', array('null' => true, 'limit' => 100))
            ->addColumn('label1', 'string', array('null' => true, 'limit' => 255))
            ->addColumn('label2', 'string', array('null' => true, 'limit' => 255))
            ->addColumn('label3', 'string', array('null' => true, 'limit' => 255))
            ->addColumn('label4', 'string', array('null' => true, 'limit' => 255))
            ->addColumn('creator', 'string', array('null' => true, 'limit' => 255))
            ->addColumn('created_at', 'datetime', array('null' => true))
            ->addColumn('updater', 'string', array('null' => true, 'limit' => 255))
            ->addColumn('updated_at', 'datetime', array('null' => true))
            ->addIndex(array('anr_id','code'),array('unique'=>true))
            ->addIndex(array('anr_id'))
            ->create();
        $table->changeColumn('id', 'integer',array('identity'=>true,'signed'=>false))->update();


        // Migration for table rolf_risks
        $table = $this->table('rolf_risks');
        $table
            ->addColumn('anr_id', 'integer', array('null' => true, 'signed' => false))
            ->addColumn('code', 'char', array('null' => true, 'limit' => 100))
            ->addColumn('label1', 'string', array('null' => true, 'limit' => 255))
            ->addColumn('label2', 'string', array('null' => true, 'limit' => 255))
            ->addColumn('label3', 'string', array('null' => true, 'limit' => 255))
            ->addColumn('label4', 'string', array('null' => true, 'limit' => 255))
            ->addColumn('description1', 'text', array('null' => true, 'limit' => MysqlAdapter::TEXT_LONG))
            ->addColumn('description2', 'text', array('null' => true, 'limit' => MysqlAdapter::TEXT_LONG))
            ->addColumn('description3', 'text', array('null' => true, 'limit' => MysqlAdapter::TEXT_LONG))
            ->addColumn('description4', 'text', array('null' => true, 'limit' => MysqlAdapter::TEXT_LONG))
            ->addColumn('creator', 'string', array('null' => true, 'limit' => 255))
            ->addColumn('created_at', 'datetime', array('null' => true))
            ->addColumn('updater', 'string', array('null' => true, 'limit' => 255))
            ->addColumn('updated_at', 'datetime', array('null' => true))
            ->addIndex(array('anr_id','code'),array('unique'=>true))
            ->addIndex(array('anr_id'))
            ->create();
        $table->changeColumn('id', 'integer',array('identity'=>true,'signed'=>false))->update();


        // Migration for table rolf_risks_categories
        $table = $this->table('rolf_risks_categories');
        $table
            ->addColumn('anr_id', 'integer', array('null' => true, 'signed' => false))
            ->addColumn('rolf_risk_id', 'integer', array('null' => true, 'signed' => false))
            ->addColumn('rolf_category_id', 'integer', array('null' => true, 'signed' => false))
            ->addColumn('creator', 'string', array('null' => true, 'limit' => 255))
            ->addColumn('created_at', 'datetime', array('null' => true))
            ->addColumn('updater', 'string', array('null' => true, 'limit' => 255))
            ->addColumn('updated_at', 'datetime', array('null' => true))
            ->addIndex(array('anr_id'))
            ->addIndex(array('rolf_risk_id'))
            ->addIndex(array('rolf_category_id'))
            ->create();
        $table->changeColumn('id', 'integer',array('identity'=>true,'signed'=>false))->update();


        // Migration for table rolf_risks_tags
        $table = $this->table('rolf_risks_tags');
        $table
            ->addColumn('anr_id', 'integer', array('null' => true, 'signed' => false))
            ->addColumn('rolf_risk_id', 'integer', array('null' => true, 'signed' => false))
            ->addColumn('rolf_tag_id', 'integer', array('null' => true, 'signed' => false))
            ->addColumn('creator', 'string', array('null' => true, 'limit' => 255))
            ->addColumn('created_at', 'datetime', array('null' => true))
            ->addColumn('updater', 'string', array('null' => true, 'limit' => 255))
            ->addColumn('updated_at', 'datetime', array('null' => true))
            ->addIndex(array('anr_id'))
            ->addIndex(array('rolf_risk_id'))
            ->addIndex(array('rolf_tag_id'))
            ->create();
        $table->changeColumn('id', 'integer',array('identity'=>true,'signed'=>false))->update();


        // Migration for table rolf_tags
        $table = $this->table('rolf_tags');
        $table
            ->addColumn('anr_id', 'integer', array('null' => true, 'signed' => false))
            ->addColumn('code', 'char', array('null' => true, 'limit' => 100))
            ->addColumn('label1', 'string', array('null' => true, 'limit' => 255))
            ->addColumn('label2', 'string', array('null' => true, 'limit' => 255))
            ->addColumn('label3', 'string', array('null' => true, 'limit' => 255))
            ->addColumn('label4', 'string', array('null' => true, 'limit' => 255))
            ->addColumn('creator', 'string', array('null' => true, 'limit' => 255))
            ->addColumn('created_at', 'datetime', array('null' => true))
            ->addColumn('updater', 'string', array('null' => true, 'limit' => 255))
            ->addColumn('updated_at', 'datetime', array('null' => true))
            ->addIndex(array('anr_id','code'),array('unique'=>true))
            ->addIndex(array('anr_id'))
            ->create();
        $table->changeColumn('id', 'integer',array('identity'=>true,'signed'=>false))->update();


        // Migration for table scales
        $table = $this->table('scales');
        $table
            ->addColumn('anr_id', 'integer', array('null' => true, 'signed' => false))
            ->addColumn('type', 'integer', array('null' => true, 'default' => '0', 'limit' => MysqlAdapter::INT_TINY))
            ->addColumn('min', 'integer', array('null' => true, 'default' => '0', 'limit' => 11))
            ->addColumn('max', 'integer', array('null' => true, 'default' => '0', 'limit' => 11))
            ->addColumn('creator', 'string', array('null' => true, 'limit' => 255))
            ->addColumn('created_at', 'datetime', array('null' => true))
            ->addColumn('updater', 'string', array('null' => true, 'limit' => 255))
            ->addColumn('updated_at', 'datetime', array('null' => true))
            ->addIndex(array('anr_id'))
            ->create();
        $table->changeColumn('id', 'integer',array('identity'=>true,'signed'=>false))->update();


        // Migration for table scales_comments
        $table = $this->table('scales_comments');
        $table
            ->addColumn('anr_id', 'integer', array('null' => true, 'signed' => false))
            ->addColumn('scale_id', 'integer', array('null' => true, 'signed' => false))
            ->addColumn('scale_type_impact_id', 'integer', array('null' => true, 'signed' => false))
            ->addColumn('val', 'integer', array('null' => true, 'default' => '0', 'limit' => 11))
            ->addColumn('comment1', 'text', array('null' => true, 'limit' => MysqlAdapter::TEXT_LONG))
            ->addColumn('comment2', 'text', array('null' => true, 'limit' => MysqlAdapter::TEXT_LONG))
            ->addColumn('comment3', 'text', array('null' => true, 'limit' => MysqlAdapter::TEXT_LONG))
            ->addColumn('comment4', 'text', array('null' => true, 'limit' => MysqlAdapter::TEXT_LONG))
            ->addColumn('creator', 'string', array('null' => true, 'limit' => 255))
            ->addColumn('created_at', 'datetime', array('null' => true))
            ->addColumn('updater', 'string', array('null' => true, 'limit' => 255))
            ->addColumn('updated_at', 'datetime', array('null' => true))
            ->addIndex(array('anr_id'))
            ->addIndex(array('scale_id'))
            ->addIndex(array('scale_type_impact_id'))
            ->create();
        $table->changeColumn('id', 'integer',array('identity'=>true,'signed'=>false))->update();


        // Migration for table scales_impact_types
        $table = $this->table('scales_impact_types');
        $table
            ->addColumn('anr_id', 'integer', array('null' => true, 'signed' => false))
            ->addColumn('scale_id', 'integer', array('null' => true, 'signed' => false))
            ->addColumn('type', 'char', array('null' => true, 'limit' => 3))
            ->addColumn('label', 'string', array('null' => true, 'limit' => 255))
            ->addColumn('is_sys', 'integer', array('null' => true, 'default' => '0', 'limit' => MysqlAdapter::INT_TINY))
            ->addColumn('is_hidden', 'integer', array('null' => true, 'default' => '0', 'limit' => MysqlAdapter::INT_TINY))
            ->addColumn('position', 'integer', array('null' => true, 'default' => '0', 'limit' => 11))
            ->addColumn('creator', 'string', array('null' => true, 'limit' => 255))
            ->addColumn('created_at', 'datetime', array('null' => true))
            ->addColumn('updater', 'string', array('null' => true, 'limit' => 255))
            ->addColumn('updated_at', 'datetime', array('null' => true))
            ->addIndex(array('anr_id'))
            ->addIndex(array('scale_id'))
            ->addIndex(array('type'))
            ->create();
        $table->changeColumn('id', 'integer',array('identity'=>true,'signed'=>false))->update();


        // Migration for table snapshots
        $table = $this->table('snapshots');
        $table
            ->addColumn('anr_id', 'integer', array('null' => true, 'signed' => false))
            ->addColumn('anr_reference_id', 'integer', array('null' => true, 'signed' => false))
            ->addColumn('comment', 'text', array('null' => true, 'limit' => MysqlAdapter::TEXT_LONG))
            ->addColumn('creator', 'string', array('null' => true, 'limit' => 255))
            ->addColumn('created_at', 'datetime', array('null' => true))
            ->addColumn('updater', 'string', array('null' => true, 'limit' => 255))
            ->addColumn('updated_at', 'datetime', array('null' => true))
            ->addIndex(array('anr_id'))
            ->addIndex(array('anr_reference_id'))
            ->create();
        $table->changeColumn('id', 'integer',array('identity'=>true,'signed'=>false))->update();


        // Migration for table themes
        $table = $this->table('themes');
        $table
            ->addColumn('anr_id', 'integer', array('null' => true, 'signed' => false))
            ->addColumn('label1', 'string', array('null' => true, 'limit' => 255))
            ->addColumn('label2', 'string', array('null' => true, 'limit' => 255))
            ->addColumn('label3', 'string', array('null' => true, 'limit' => 255))
            ->addColumn('label4', 'string', array('null' => true, 'limit' => 255))
            ->addColumn('creator', 'string', array('null' => true, 'limit' => 255))
            ->addColumn('created_at', 'datetime', array('null' => true))
            ->addColumn('updater', 'string', array('null' => true, 'limit' => 255))
            ->addColumn('updated_at', 'datetime', array('null' => true))
            ->addIndex(array('anr_id'))
            ->create();
        $table->changeColumn('id', 'integer',array('identity'=>true,'signed'=>false))->update();


        // Migration for table threats
        $table = $this->table('threats');
        $table
            ->addColumn('anr_id', 'integer', array('null' => true, 'signed' => false))
            ->addColumn('threat_theme_id', 'integer', array('null' => true, 'signed' => false))
            ->addColumn('mode', 'integer', array('null' => true, 'default' => '1', 'limit' => MysqlAdapter::INT_TINY))
            ->addColumn('code', 'char', array('null' => true, 'limit' => 100))
            ->addColumn('label1', 'string', array('null' => true, 'limit' => 255))
            ->addColumn('label2', 'string', array('null' => true, 'limit' => 255))
            ->addColumn('label3', 'string', array('null' => true, 'limit' => 255))
            ->addColumn('label4', 'string', array('null' => true, 'limit' => 255))
            ->addColumn('description1', 'string', array('null' => true, 'limit' => 255))
            ->addColumn('description2', 'string', array('null' => true, 'limit' => 255))
            ->addColumn('description3', 'string', array('null' => true, 'limit' => 255))
            ->addColumn('description4', 'string', array('null' => true, 'limit' => 255))
            ->addColumn('c', 'integer', array('null' => true, 'default' => '0', 'limit' => MysqlAdapter::INT_TINY))
            ->addColumn('i', 'integer', array('null' => true, 'default' => '0', 'limit' => MysqlAdapter::INT_TINY))
            ->addColumn('d', 'integer', array('null' => true, 'default' => '0', 'limit' => MysqlAdapter::INT_TINY))
            ->addColumn('status', 'integer', array('null' => true, 'default' => '1', 'limit' => MysqlAdapter::INT_TINY))
            ->addColumn('is_accidental', 'integer', array('null' => true, 'default' => '0', 'limit' => MysqlAdapter::INT_TINY))
            ->addColumn('is_deliberate', 'integer', array('null' => true, 'default' => '0', 'limit' => MysqlAdapter::INT_TINY))
            ->addColumn('desc_accidental1', 'text', array('null' => true, 'limit' => MysqlAdapter::TEXT_LONG))
            ->addColumn('desc_accidental2', 'text', array('null' => true, 'limit' => MysqlAdapter::TEXT_LONG))
            ->addColumn('desc_accidental3', 'text', array('null' => true, 'limit' => MysqlAdapter::TEXT_LONG))
            ->addColumn('desc_accidental4', 'text', array('null' => true, 'limit' => MysqlAdapter::TEXT_LONG))
            ->addColumn('ex_accidental1', 'text', array('null' => true, 'limit' => MysqlAdapter::TEXT_LONG))
            ->addColumn('ex_accidental2', 'text', array('null' => true, 'limit' => MysqlAdapter::TEXT_LONG))
            ->addColumn('ex_accidental3', 'text', array('null' => true, 'limit' => MysqlAdapter::TEXT_LONG))
            ->addColumn('ex_accidental4', 'text', array('null' => true, 'limit' => MysqlAdapter::TEXT_LONG))
            ->addColumn('desc_deliberate1', 'text', array('null' => true, 'limit' => MysqlAdapter::TEXT_LONG))
            ->addColumn('desc_deliberate2', 'text', array('null' => true, 'limit' => MysqlAdapter::TEXT_LONG))
            ->addColumn('desc_deliberate3', 'text', array('null' => true, 'limit' => MysqlAdapter::TEXT_LONG))
            ->addColumn('desc_deliberate4', 'text', array('null' => true, 'limit' => MysqlAdapter::TEXT_LONG))
            ->addColumn('ex_deliberate1', 'text', array('null' => true, 'limit' => MysqlAdapter::TEXT_LONG))
            ->addColumn('ex_deliberate2', 'text', array('null' => true, 'limit' => MysqlAdapter::TEXT_LONG))
            ->addColumn('ex_deliberate3', 'text', array('null' => true, 'limit' => MysqlAdapter::TEXT_LONG))
            ->addColumn('ex_deliberate4', 'text', array('null' => true, 'limit' => MysqlAdapter::TEXT_LONG))
            ->addColumn('type_consequences1', 'text', array('null' => true, 'limit' => MysqlAdapter::TEXT_LONG))
            ->addColumn('type_consequences2', 'text', array('null' => true, 'limit' => MysqlAdapter::TEXT_LONG))
            ->addColumn('type_consequences3', 'text', array('null' => true, 'limit' => MysqlAdapter::TEXT_LONG))
            ->addColumn('type_consequences4', 'text', array('null' => true, 'limit' => MysqlAdapter::TEXT_LONG))
            ->addColumn('trend', 'integer', array('null' => true, 'default' => '0', 'limit' => 11))
            ->addColumn('comment', 'text', array('null' => true, 'limit' => MysqlAdapter::TEXT_LONG))
            ->addColumn('qualification', 'integer', array('null' => true, 'default' => '-1', 'limit' => 11))
            ->addColumn('creator', 'string', array('null' => true, 'limit' => 255))
            ->addColumn('created_at', 'datetime', array('null' => true))
            ->addColumn('updater', 'string', array('null' => true, 'limit' => 255))
            ->addColumn('updated_at', 'datetime', array('null' => true))
            ->addIndex(array('anr_id','code'),array('unique'=>true))
            ->addIndex(array('anr_id'))
            ->addIndex(array('threat_theme_id'))
            ->create();
        $table->changeColumn('id', 'integer',array('identity'=>true,'signed'=>false))->update();


        // Migration for table threats_models
        $table = $this->table('threats_models', array('id' => false, 'primary_key' => array('threat_id', 'model_id')));
        $table
            ->addColumn('threat_id', 'integer', array('signed' => false))
            ->addColumn('model_id', 'integer', array('signed' => false))
            ->addIndex(array('threat_id'))
            ->addIndex(array('model_id'))
            ->create();


        // Migration for table vulnerabilities
        $table = $this->table('vulnerabilities');
        $table
            ->addColumn('anr_id', 'integer', array('null' => true, 'signed' => false))
            ->addColumn('mode', 'integer', array('null' => true, 'default' => '1', 'limit' => MysqlAdapter::INT_TINY))
            ->addColumn('code', 'char', array('null' => true, 'limit' => 100))
            ->addColumn('label1', 'string', array('null' => true, 'default' => '', 'limit' => 255))
            ->addColumn('label2', 'string', array('null' => true, 'default' => '', 'limit' => 255))
            ->addColumn('label3', 'string', array('null' => true, 'default' => '', 'limit' => 255))
            ->addColumn('label4', 'string', array('null' => true, 'default' => '', 'limit' => 255))
            ->addColumn('description1', 'text', array('null' => true, 'limit' => MysqlAdapter::TEXT_LONG))
            ->addColumn('description2', 'text', array('null' => true, 'limit' => MysqlAdapter::TEXT_LONG))
            ->addColumn('description3', 'text', array('null' => true, 'limit' => MysqlAdapter::TEXT_LONG))
            ->addColumn('description4', 'text', array('null' => true, 'limit' => MysqlAdapter::TEXT_LONG))
            ->addColumn('status', 'integer', array('null' => true, 'default' => '1', 'limit' => MysqlAdapter::INT_TINY))
            ->addColumn('creator', 'string', array('null' => true, 'limit' => 255))
            ->addColumn('created_at', 'datetime', array('null' => true))
            ->addColumn('updater', 'string', array('null' => true, 'limit' => 255))
            ->addColumn('updated_at', 'datetime', array('null' => true))
            ->addIndex(array('anr_id','code'),array('unique'=>true))
            ->addIndex(array('anr_id'))
            ->create();
        $table->changeColumn('id', 'integer',array('identity'=>true,'signed'=>false))->update();


        // Migration for table vulnerabilities_models
        $table = $this->table('vulnerabilities_models', array('id' => false, 'primary_key' => array('vulnerability_id', 'model_id')));
        $table
            ->addColumn('vulnerability_id', 'integer', array('signed' => false))
            ->addColumn('model_id', 'integer', array('signed' => false))
            ->addIndex(array('vulnerability_id'))
            ->addIndex(array('model_id'))
            ->create();

        // Migrations ForeignKey
        $table = $this->table('amvs');
        $table
            ->addForeignKey('anr_id', 'anrs', 'id', array('delete' => 'CASCADE','update' => 'RESTRICT'))
            ->addForeignKey('asset_id', 'assets', 'id', array('delete' => 'CASCADE','update' => 'RESTRICT'))
            ->addForeignKey('threat_id', 'threats', 'id', array('delete' => 'CASCADE','update' => 'RESTRICT'))
            ->addForeignKey('vulnerability_id', 'vulnerabilities', 'id', array('delete' => 'CASCADE','update' => 'RESTRICT'))
            ->addForeignKey('measure1_id', 'measures', 'id', array('delete' => 'CASCADE','update' => 'RESTRICT'))
            ->addForeignKey('measure2_id', 'measures', 'id', array('delete' => 'CASCADE','update' => 'RESTRICT'))
            ->addForeignKey('measure3_id', 'measures', 'id', array('delete' => 'CASCADE','update' => 'RESTRICT'))
            ->update();
        $table = $this->table('anrs');
        $table
            ->addForeignKey('snapshot_id', 'anrs', 'id', array('delete' => 'CASCADE','update' => 'RESTRICT'))
            ->addForeignKey('snapshot_ref_id', 'snapshots', 'id', array('delete' => 'CASCADE','update' => 'RESTRICT'))
            ->update();
        $table = $this->table('anrs_objects_categories');
        $table
            ->addForeignKey('anr_id', 'anrs', 'id', array('delete' => 'CASCADE','update' => 'RESTRICT'))
            ->addForeignKey('object_category_id', 'objects_categories', 'id', array('delete' => 'CASCADE','update' => 'RESTRICT'))
            ->update();
        $table = $this->table('assets');
        $table
            ->addForeignKey('anr_id', 'anrs', 'id', array('delete' => 'CASCADE','update' => 'RESTRICT'))
            ->update();
        $table = $this->table('assets_models', array('id' => false, 'primary_key' => array('asset_id', 'model_id')));
        $table
            ->addForeignKey('asset_id', 'assets', 'id', array('delete' => 'RESTRICT','update' => 'RESTRICT'))
            ->addForeignKey('model_id', 'models', 'id', array('delete' => 'RESTRICT','update' => 'RESTRICT'))
            ->update();
        $table = $this->table('cities');
        $table
            ->addForeignKey('country_id', 'countries', 'id', array('delete' => 'CASCADE','update' => 'RESTRICT'))
            ->update();
        $table = $this->table('deliveries');
        $table
            ->addForeignKey('anr_id', 'anrs', 'id', array('delete' => 'CASCADE','update' => 'RESTRICT'))
            ->update();
        $table = $this->table('deliveries_models');
        $table
            ->addForeignKey('anr_id', 'anrs', 'id', array('delete' => 'CASCADE','update' => 'RESTRICT'))
            ->update();
        $table = $this->table('guides');
        $table
            ->addForeignKey('anr_id', 'anrs', 'id', array('delete' => 'CASCADE','update' => 'RESTRICT'))
            ->update();
        $table = $this->table('guides_items');
        $table
            ->addForeignKey('anr_id', 'anrs', 'id', array('delete' => 'CASCADE','update' => 'RESTRICT'))
            ->addForeignKey('guide_id', 'guides', 'id', array('delete' => 'CASCADE','update' => 'RESTRICT'))
            ->update();
        $table = $this->table('instances');
        $table
            ->addForeignKey('anr_id', 'anrs', 'id', array('delete' => 'CASCADE','update' => 'RESTRICT'))
            ->addForeignKey('asset_id', 'assets', 'id', array('delete' => 'CASCADE','update' => 'RESTRICT'))
            ->addForeignKey('object_id', 'objects', 'id', array('delete' => 'CASCADE','update' => 'RESTRICT'))
            ->addForeignKey('root_id', 'instances', 'id', array('delete' => 'CASCADE','update' => 'RESTRICT'))
            ->update();
        $table = $this->table('instances_instances');
        $table
            ->addForeignKey('anr_id', 'anrs', 'id', array('delete' => 'CASCADE','update' => 'RESTRICT'))
            ->addForeignKey('father_id', 'instances', 'id', array('delete' => 'CASCADE','update' => 'RESTRICT'))
            ->addForeignKey('child_id', 'instances', 'id', array('delete' => 'CASCADE','update' => 'RESTRICT'))
            ->update();
        $table = $this->table('instances_instances_consequences');
        $table
            ->addForeignKey('anr_id', 'anrs', 'id', array('delete' => 'CASCADE','update' => 'RESTRICT'))
            ->addForeignKey('instance_instance_id', 'instances_instances', 'id', array('delete' => 'CASCADE','update' => 'RESTRICT'))
            ->addForeignKey('instance_id', 'instances', 'id', array('delete' => 'CASCADE','update' => 'RESTRICT'))
            ->addForeignKey('object_id', 'objects', 'id', array('delete' => 'CASCADE','update' => 'RESTRICT'))
            ->addForeignKey('scale_impact_type_id', 'scales_impact_types', 'id', array('delete' => 'CASCADE','update' => 'RESTRICT'))
            ->update();
        $table = $this->table('instances_risks');
        $table
            ->addForeignKey('anr_id', 'anrs', 'id', array('delete' => 'CASCADE','update' => 'RESTRICT'))
            ->addForeignKey('amv_id', 'amvs', 'id', array('delete' => 'CASCADE','update' => 'RESTRICT'))
            ->addForeignKey('instance_instance_id', 'instances_instances', 'id', array('delete' => 'CASCADE','update' => 'RESTRICT'))
            ->addForeignKey('asset_id', 'assets', 'id', array('delete' => 'CASCADE','update' => 'RESTRICT'))
            ->addForeignKey('threat_id', 'threats', 'id', array('delete' => 'CASCADE','update' => 'RESTRICT'))
            ->addForeignKey('vulnerability_id', 'vulnerabilities', 'id', array('delete' => 'CASCADE','update' => 'RESTRICT'))
            ->update();
        $table = $this->table('instances_risks_op');
        $table
            ->addForeignKey('anr_id', 'anrs', 'id', array('delete' => 'CASCADE','update' => 'RESTRICT'))
            ->addForeignKey('instance_id', 'instances', 'id', array('delete' => 'CASCADE','update' => 'RESTRICT'))
            ->addForeignKey('rolf_risk_id', 'rolf_risks', 'id', array('delete' => 'RESTRICT','update' => 'RESTRICT'))
            ->addForeignKey('rolf_risk_id', 'rolf_risks', 'id', array('delete' => 'SET_NULL','update' => 'RESTRICT'))
            ->update();
        $table = $this->table('interviews');
        $table
            ->addForeignKey('anr_id', 'anrs', 'id', array('delete' => 'CASCADE','update' => 'RESTRICT'))
            ->update();
        $table = $this->table('measures');
        $table
            ->addForeignKey('anr_id', 'anrs', 'id', array('delete' => 'CASCADE','update' => 'RESTRICT'))
            ->update();
        $table = $this->table('models');
        $table
            ->addForeignKey('anr_id', 'anrs', 'id', array('delete' => 'CASCADE','update' => 'RESTRICT'))
            ->update();
        $table = $this->table('objects');
        $table
            ->addForeignKey('anr_id', 'anrs', 'id', array('delete' => 'CASCADE','update' => 'RESTRICT'))
            ->addForeignKey('object_category_id', 'objects_categories', 'id', array('delete' => 'SET_NULL','update' => 'RESTRICT'))
            ->addForeignKey('asset_id', 'assets', 'id', array('delete' => 'CASCADE','update' => 'RESTRICT'))
            ->addForeignKey('source_bdc_object_id', 'objects', 'id', array('delete' => 'CASCADE','update' => 'RESTRICT'))
            ->addForeignKey('rolf_tag_id', 'rolf_tags', 'id', array('delete' => 'SET_NULL','update' => 'RESTRICT'))
            ->update();
        $table = $this->table('objects_categories');
        $table
            ->addForeignKey('anr_id', 'anrs', 'id', array('delete' => 'CASCADE','update' => 'RESTRICT'))
            ->addForeignKey('root_id', 'objects_categories', 'id', array('delete' => 'SET_NULL','update' => 'RESTRICT'))
            ->addForeignKey('parent_id', 'objects_categories', 'id', array('delete' => 'SET_NULL','update' => 'RESTRICT'))
            ->update();
        $table = $this->table('objects_objects');
        $table
            ->addForeignKey('anr_id', 'anrs', 'id', array('delete' => 'CASCADE','update' => 'RESTRICT'))
            ->addForeignKey('father_id', 'objects', 'id', array('delete' => 'CASCADE','update' => 'RESTRICT'))
            ->addForeignKey('child_id', 'objects', 'id', array('delete' => 'CASCADE','update' => 'RESTRICT'))
            ->update();
        $table = $this->table('objects_risks');
        $table
            ->addForeignKey('anr_id', 'anrs', 'id', array('delete' => 'CASCADE','update' => 'RESTRICT'))
            ->addForeignKey('object_id', 'objects', 'id', array('delete' => 'CASCADE','update' => 'RESTRICT'))
            ->addForeignKey('asset_id', 'assets', 'id', array('delete' => 'CASCADE','update' => 'RESTRICT'))
            ->addForeignKey('amv_id', 'amvs', 'id', array('delete' => 'CASCADE','update' => 'RESTRICT'))
            ->addForeignKey('threat_id', 'threats', 'id', array('delete' => 'CASCADE','update' => 'RESTRICT'))
            ->addForeignKey('vulnerability_id', 'vulnerabilities', 'id', array('delete' => 'CASCADE','update' => 'RESTRICT'))
            ->update();
        $table = $this->table('rolf_categories');
        $table
            ->addForeignKey('anr_id', 'anrs', 'id', array('delete' => 'CASCADE','update' => 'RESTRICT'))
            ->update();
        $table = $this->table('rolf_risks');
        $table
            ->addForeignKey('anr_id', 'anrs', 'id', array('delete' => 'CASCADE','update' => 'RESTRICT'))
            ->update();
        $table = $this->table('rolf_risks_categories');
        $table
            ->addForeignKey('anr_id', 'anrs', 'id', array('delete' => 'CASCADE','update' => 'RESTRICT'))
            ->addForeignKey('rolf_category_id', 'rolf_categories', 'id', array('delete' => 'CASCADE','update' => 'RESTRICT'))
            ->addForeignKey('rolf_risk_id', 'rolf_risks', 'id', array('delete' => 'CASCADE','update' => 'RESTRICT'))
            ->update();
        $table = $this->table('rolf_risks_tags');
        $table
            ->addForeignKey('anr_id', 'anrs', 'id', array('delete' => 'CASCADE','update' => 'RESTRICT'))
            ->addForeignKey('rolf_risk_id', 'rolf_risks', 'id', array('delete' => 'CASCADE','update' => 'RESTRICT'))
            ->addForeignKey('rolf_tag_id', 'rolf_tags', 'id', array('delete' => 'CASCADE','update' => 'RESTRICT'))
            ->update();
        $table = $this->table('rolf_tags');
        $table
            ->addForeignKey('anr_id', 'anrs', 'id', array('delete' => 'CASCADE','update' => 'RESTRICT'))
            ->update();
        $table = $this->table('scales');
        $table
            ->addForeignKey('anr_id', 'anrs', 'id', array('delete' => 'CASCADE','update' => 'RESTRICT'))
            ->update();
        $table = $this->table('scales_comments');
        $table
            ->addForeignKey('anr_id', 'anrs', 'id', array('delete' => 'CASCADE','update' => 'RESTRICT'))
            ->addForeignKey('scale_id', 'scales', 'id', array('delete' => 'CASCADE','update' => 'RESTRICT'))
            ->addForeignKey('scale_type_impact_id', 'scales_impact_types', 'id', array('delete' => 'CASCADE','update' => 'RESTRICT'))
            ->update();
        $table = $this->table('scales_impact_types');
        $table
            ->addForeignKey('anr_id', 'anrs', 'id', array('delete' => 'CASCADE','update' => 'RESTRICT'))
            ->addForeignKey('scale_id', 'scales', 'id', array('delete' => 'CASCADE','update' => 'RESTRICT'))
            ->update();
        $table = $this->table('snapshots');
        $table
            ->addForeignKey('anr_id', 'anrs', 'id', array('delete' => 'CASCADE','update' => 'RESTRICT'))
            ->addForeignKey('anr_reference_id', 'anrs', 'id', array('delete' => 'CASCADE','update' => 'RESTRICT'))
            ->update();
        $table = $this->table('themes');
        $table
            ->addForeignKey('anr_id', 'anrs', 'id', array('delete' => 'CASCADE','update' => 'RESTRICT'))
            ->update();
        $table = $this->table('threats');
        $table
            ->addForeignKey('anr_id', 'anrs', 'id', array('delete' => 'CASCADE','update' => 'RESTRICT'))
            ->addForeignKey('threat_theme_id', 'themes', 'id', array('delete' => 'CASCADE','update' => 'RESTRICT'))
            ->update();
        $table = $this->table('threats_models');
        $table
            ->addForeignKey('threat_id', 'threats', 'id', array('delete' => 'RESTRICT','update' => 'RESTRICT'))
            ->addForeignKey('model_id', 'models', 'id', array('delete' => 'RESTRICT','update' => 'RESTRICT'))
            ->update();
        $table = $this->table('vulnerabilities');
        $table
            ->addForeignKey('anr_id', 'anrs', 'id', array('delete' => 'CASCADE','update' => 'RESTRICT'))
            ->update();
        $table = $this->table('vulnerabilities_models');
        $table
            ->addForeignKey('vulnerability_id', 'vulnerabilities', 'id', array('delete' => 'RESTRICT','update' => 'RESTRICT'))
            ->addForeignKey('model_id', 'models', 'id', array('delete' => 'RESTRICT','update' => 'RESTRICT'))
            ->update();
    }
}
