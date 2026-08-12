<?php
declare(strict_types=1);

use Phinx\Migration\AbstractMigration;

final class AddCyfunIso27002Mapping extends AbstractMigration
{
    public function up(): void
    {
        $pdo = $this->getAdapter()->getConnection();

        $jsonFiles = [
            'mapping_cyfun_basic_iso27002.json',
            'mapping_cyfun_important_iso27002.json',
            'mapping_cyfun_essential_iso27002.json',
        ];

        foreach ($jsonFiles as $jsonFile) {
            $jsonPath = __DIR__ . '/../data/' . $jsonFile;

            if (!file_exists($jsonPath)) {
                throw new RuntimeException("Missing JSON file: $jsonPath");
            }

            $data = json_decode(file_get_contents($jsonPath), true);
            // Validate JSON structure
            if (!$data || !isset($data['values']) || !is_array($data['values'])) {
                throw new RuntimeException("Invalid JSON structure in $jsonPath");
            }

            foreach ($data['values'] as $mapping) {
                if (!isset($mapping['control'], $mapping['match'])) {
                    throw new RuntimeException("Invalid mapping entry in $jsonPath: " . json_encode($mapping));
                }

                $masterMeasureId = $mapping['control'];
                $linkedMeasureId = $mapping['match'];

                // Ensure both measures exist before linking them
                $quotedMaster = $pdo->quote($masterMeasureId);
                $quotedLinked = $pdo->quote($linkedMeasureId);

                $masterExists = $this->fetchRow("SELECT 1 FROM measures WHERE uuid = $quotedMaster");
                $linkedExists = $this->fetchRow("SELECT 1 FROM measures WHERE uuid = $quotedLinked");

                if (!$masterExists || !$linkedExists) {
                    throw new RuntimeException(
                        "Cannot link measures_measures: master=$masterMeasureId (exists: " .
                        ($masterExists ? 'yes' : 'no') . "), linked=$linkedMeasureId (exists: " .
                        ($linkedExists ? 'yes' : 'no') . ") in $jsonFile"
                    );
                }

                // The relationship is stored symmetrically: both (master, linked) and
                // (linked, master) rows exist, so the link can be looked up from either
                // measure without a UNION query
                $pairs = [
                    [$masterMeasureId, $linkedMeasureId],
                    [$linkedMeasureId, $masterMeasureId],
                ];

                foreach ($pairs as [$master, $linked]) {
                    $this->execute(
                        'INSERT INTO measures_measures
                            (master_measure_id, linked_measure_id)
                         VALUES
                            (:master_measure_id, :linked_measure_id)
                         ON DUPLICATE KEY UPDATE
                            master_measure_id = VALUES(master_measure_id)',
                        [
                            'master_measure_id' => $master,
                            'linked_measure_id' => $linked,
                        ]
                    );
                }
            }
        }
    }
}
