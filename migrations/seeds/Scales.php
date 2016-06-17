<?php

use Phinx\Seed\AbstractSeed;

class Scales extends AbstractSeed
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
                'type' => 1,
                'min' => 0,
                'max' => 3,
                'creator' => 'System',
                'created_at' => date('Y-m-d H:i:s')
            ],
            [
                'type' => 2,
                'min' => 0,
                'max' => 4,
                'creator' => 'System',
                'created_at' => date('Y-m-d H:i:s')
            ],
            [
                'type' => 3,
                'min' => 0,
                'max' => 3,
                'creator' => 'System',
                'created_at' => date('Y-m-d H:i:s')
            ],
        ];

        $posts = $this->table('scales');
        foreach($data as $array) {
            $posts->insert($array)
                ->save();
        }
    }
}
