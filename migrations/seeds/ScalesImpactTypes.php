<?php

use Phinx\Seed\AbstractSeed;

class ScalesImpactTypes extends AbstractSeed
{
    /**
     * Run Method.
     *
     * Write your database seeder using this method.
     *
     * More information on writing seeders is available here:
     * http://docs.phinx.org/en/latest/seeding.html
     */
    public function run()
    {
        $data = [
            [
                'scale_id' => 1,
                'type' => 1,
                'label1' => 'Confidentialité',
                'is_sys' => 1,
                'is_hidden' => 0,
                'position' => 1,
                'creator' => 'System',
                'created_at' => date('Y-m-d H:i:s')
            ],
            [
                'scale_id' => 1,
                'type' => 2,
                'label1' => 'Intégrité',
                'is_sys' => 1,
                'is_hidden' => 0,
                'position' => 2,
                'creator' => 'System',
                'created_at' => date('Y-m-d H:i:s')
            ],
            [
                'scale_id' => 1,
                'type' => 3,
                'label1' => 'Disponibilité',
                'is_sys' => 1,
                'is_hidden' => 0,
                'position' => 3,
                'creator' => 'System',
                'created_at' => date('Y-m-d H:i:s')
            ],
            [
                'scale_id' => 1,
                'type' => 4,
                'label1' => 'Réputation',
                'is_sys' => 1,
                'is_hidden' => 0,
                'position' => 4,
                'creator' => 'System',
                'created_at' => date('Y-m-d H:i:s')
            ],
            [
                'scale_id' => 1,
                'type' => 5,
                'label1' => 'Opérationnel',
                'is_sys' => 1,
                'is_hidden' => 0,
                'position' => 5,
                'creator' => 'System',
                'created_at' => date('Y-m-d H:i:s')
            ],
            [
                'scale_id' => 1,
                'type' => 6,
                'label1' => 'Légal',
                'is_sys' => 1,
                'is_hidden' => 0,
                'position' => 6,
                'creator' => 'System',
                'created_at' => date('Y-m-d H:i:s')
            ],
            [
                'scale_id' => 1,
                'type' => 7,
                'label1' => 'Financier',
                'is_sys' => 1,
                'is_hidden' => 0,
                'position' => 7,
                'creator' => 'System',
                'created_at' => date('Y-m-d H:i:s')
            ],
            [
                'scale_id' => 1,
                'type' => 8,
                'label1' => 'Personne',
                'is_sys' => 1,
                'is_hidden' => 0,
                'position' => 8,
                'creator' => 'System',
                'created_at' => date('Y-m-d H:i:s')
            ],
        ];

        $posts = $this->table('scales_impact_types');
        foreach($data as $array) {
            $posts->insert($array)
                ->save();
        }
    }
}
