<?php

use Phinx\Migration\AbstractMigration;

class UpgradeDeliveriesModels extends AbstractMigration
{
    public function change()
    {
        $table = $this->table('deliveries_models');
        $table->removeColumn('content1')
            ->removeColumn('content2')
            ->removeColumn('content3')
            ->removeColumn('content4')
            ->save();

        $this->query('DELETE FROM deliveries_models WHERE category = 1;');
        $this->query('DELETE FROM deliveries_models WHERE category = 2;');
        $this->query('DELETE FROM deliveries_models WHERE category = 3;');
        $this->query('DELETE FROM deliveries_models WHERE category = 4;');

        $this->query('INSERT INTO deliveries_models (category, description1, path1, creator, created_at, updater, updated_at, description2, description3, description4, path2, path3, path4) VALUES (1, "Validation de contexte", "./deliveries/cases/FR/1.docx", NULL , NULL, NULL, NULL, "Context validation", "Kontextüberprüfung", NULL, "./deliveries/cases/EN/1.docx", "./deliveries/cases/DE/1.docx", "./deliveries/cases/NE/1.docx"), (2, "Validation du modèle"  , "./deliveries/cases/FR/2.docx", NULL , NULL, NULL, NULL, "Modelling validation", "Modellierungsüberprüfung", NULL, "./deliveries/cases/EN/2.docx", "./deliveries/cases/DE/2.docx", "./deliveries/cases/NE/2.docx"), (3, "Rapport final", "./deliveries/cases/FR/3.docx", NULL, NULL, NULL, NULL, "Report risk assessment", "Risikobeurteilungsbericht", NULL, "./deliveries/cases/EN/3.docx", "./deliveries/cases/DE/3.docx", "./deliveries/cases/NE/3.docx"), (4, "Plan dimplémentation" , "./deliveries/cases/FR/4.docx", NULL, NULL, NULL, NULL, "Implementation plan", "Implementierungsplan", "Implementatieplan", "./deliveries/cases/EN/4.docx", "./deliveries/cases/DE/4.docx", "./deliveries/cases/NE/4.docx");');

    }
}
