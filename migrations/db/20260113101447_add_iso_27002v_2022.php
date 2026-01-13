<?php
declare(strict_types=1);

use Phinx\Migration\AbstractMigration;

final class AddIso27002v2022 extends AbstractMigration
{
    public function up(): void
    {
        $adapter = $this->getAdapter();
        $jsonPath = __DIR__ . '/../data/ISO_IEC_27002_2022.json';
        
        if (!file_exists($jsonPath)) {
            throw new RuntimeException("Missing JSON file: $jsonPath");
        }

        $data = json_decode(file_get_contents($jsonPath), true);
        // Validate JSON structure
        if (!$data || !isset($data['uuid'], $data['label'], $data['measures'])) {
            throw new RuntimeException("Invalid JSON structure in $jsonPath");
        }

        $referentialUuid = $data['uuid'];
        $referentialLabel = $data['label'];

        // Insert or update the referential
        $this->execute(
            'INSERT INTO referentials (uuid, label1, label2, label3, label4)
             VALUES (:uuid, :label1, :label2, :label3, :label4)
             ON DUPLICATE KEY UPDATE
                 label1 = VALUES(label1),
                 label2 = VALUES(label2),
                 label3 = VALUES(label3),
                 label4 = VALUES(label4)',
            [
                'uuid'   => $referentialUuid,
                'label1' => $referentialLabel,
                'label2' => $referentialLabel,
                'label3' => $referentialLabel,
                'label4' => $referentialLabel,
            ]
        );

        foreach ($data['measures'] as $measure) {
            $category = $measure['category'];
            $amvs = $measure['amvs'] ?? [];
            
            // Insert or update category
            $this->execute(
                'INSERT INTO soacategory (label1, label2, label3, label4, referential_uuid)
                 VALUES (:label1, :label2, :label3, :label4, :referential_uuid)
                 ON DUPLICATE KEY UPDATE
                     label1 = VALUES(label1),
                     label2 = VALUES(label2),
                     label3 = VALUES(label3),
                     label4 = VALUES(label4)',
                [
                    'label1'           => $category['label1'],
                    'label2'           => $category['label2'],
                    'label3'           => $category['label3'],
                    'label4'           => $category['label4'],
                    'referential_uuid' => $referentialUuid,
                ]
            );

            // Fetch the category ID using referential_uuid and label2[English]
            $categoryEnglishLabel = $category['label2'];
            $categoryRow = $this->fetchRow(
                "SELECT id FROM soacategory 
                WHERE referential_uuid = $adapter->quote($referentialUuid) 
                AND label2 = $adapter->quote($categoryEnglishLabel)"
            );
            
            if (!$categoryRow) {
                throw new RuntimeException("Failed to fetch category ID for " . $category['label2']);
            }

            $categoryId = $categoryRow['id'];
            
            // Insert or update measure
            $this->execute(
                'INSERT INTO measures (uuid, soacategory_id, referential_uuid, code, label1, label2, label3, label4)
                VALUES (:uuid, :soacategory_id, :referential_uuid, :code, :label1, :label2, :label3, :label4)
                ON DUPLICATE KEY UPDATE
                    code = VALUES(code),
                    soacategory_id = VALUES(soacategory_id),
                    referential_uuid = VALUES(referential_uuid),
                    label1 = VALUES(label1),
                    label2 = VALUES(label2),
                    label3 = VALUES(label3),
                    label4 = VALUES(label4)',
                [
                    'uuid'        => $measure['uuid'],
                    'soacategory_id' => $categoryId,
                    'referential_uuid' => $referentialUuid,
                    'code'        => $measure['code'],
                    'label1'      => $measure['label1'],
                    'label2'      => $measure['label2'],
                    'label3'      => $measure['label3'],
                    'label4'      => $measure['label4'],
                ]
            );

            // Remove existing measures_amvs for this measure
            $this->execute(
                'DELETE FROM measures_amvs WHERE measure_id = :measure_id',
                ['measure_id' => $measure['uuid']]
            );

            // Insert measures_amvs in batch
            if (!empty($amvs)) {
                $values = [];
                $params = [];
                $index = 0;

                foreach ($amvs as $amvUuid) {
                    // Check if the AMV exists
                    $exists = $this->fetchRow(
                        "SELECT 1 FROM amvs WHERE uuid = " . $adapter->quote($amvUuid)
                    );

                    if ($exists) {
                        $values[] = "(:measure_id, :amv_id_$index)";
                        $params["amv_id_$index"] = $amvUuid;
                        $index++;
                    }
                }

                if (!empty($values)) {
                    $params['measure_id'] = $measure['uuid'];
                    $sql = 'INSERT INTO measures_amvs (measure_id, amv_id) VALUES ' . implode(', ', $values);
                    $this->execute($sql, $params);
                }   
            }
        }
    }
}
