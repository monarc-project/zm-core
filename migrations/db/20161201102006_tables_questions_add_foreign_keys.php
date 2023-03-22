<?php

use Phinx\Migration\AbstractMigration;

class TablesQuestionsAddForeignKeys extends AbstractMigration
{
    public function change()
    {
        $table = $this->table('questions_choices');

        $exists = $table->hasForeignKey('question_id');
        if ($exists) {
            $table->dropForeignKey('question_id')->update();
        }

        $table
            ->addForeignKey('question_id', 'questions', 'id', array('delete' => 'CASCADE','update' => 'RESTRICT'))
            ->update();
    }
}
