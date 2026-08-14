<?php

use Phinx\Migration\AbstractMigration;

class FixOpScalesTranslations extends AbstractMigration
{
    /**
     * Performs validation and adding of different languages missing translations of the op scales values.
     */
    public function change()
    {
        // Validate and fix scale types translations.
        $scalesTypesQuery = $this->query(
            'select st.label_translation_key, st.anr_id, count(t.lang) as langs_cnt
            from operational_risks_scales_types st
            inner join translations t
                on st.label_translation_key = t.translation_key
            group by st.label_translation_key, st.anr_id'
        );
        $translationsTable = $this->table('translations');
        $languages = $this->getLanguageCodes();
        foreach ($scalesTypesQuery->fetchAll() as $scaleTypeData) {
            if ((int)$scaleTypeData['langs_cnt'] < count($languages)) {
                foreach ($languages as $lang) {
                    $existsForTheLang = $this->fetchRow(
                        'select count(*) cnt from `translations`
                        where translation_key = "' . $scaleTypeData['label_translation_key'] . '"
                          and `lang` = "' . $lang . '"'
                    );

                    if ((int)$existsForTheLang['cnt'] === 0) {
                        $translationsTable->insert([
                            'anr_id' => $scaleTypeData['anr_id'],
                            'type' => 'operational-risk-scale-type',
                            'translation_key' => $scaleTypeData['label_translation_key'],
                            'lang' => $lang,
                            'value' => '',
                            'creator' => 'Migration script',
                        ])->saveData();
                    }
                }
            }
        }

        // Validate and fix scale comments translations.
        $scalesCommentsQuery = $this->query(
            'select sc.comment_translation_key, sc.anr_id, count(t.lang) as langs_cnt
            from operational_risks_scales_comments sc
            inner join translations t
                on sc.comment_translation_key = t.translation_key
            group by sc.comment_translation_key, sc.anr_id'
        );
        $translationsTable = $this->table('translations');
        foreach ($scalesCommentsQuery->fetchAll() as $scaleCommentData) {
            if ((int)$scaleCommentData['langs_cnt'] < count($languages)) {
                foreach ($languages as $lang) {
                    $existsForTheLang = $this->fetchRow(
                        'select count(*) cnt from `translations`
                        where translation_key = "' . $scaleCommentData['comment_translation_key'] . '"
                          and `lang` = "' . $lang . '"'
                    );

                    if ((int)$existsForTheLang['cnt'] === 0) {
                        $translationsTable->insert([
                            'anr_id' => $scaleCommentData['anr_id'],
                            'type' => 'operational-risk-scale-comment',
                            'translation_key' => $scaleCommentData['comment_translation_key'],
                            'lang' => $lang,
                            'value' => '',
                            'creator' => 'Migration script',
                        ])->saveData();
                    }
                }
            }
        }
    }

    private function getLanguageCodes(): array
    {
        $config = [];
        $base = getcwd() . '/config/autoload/';
        foreach (['global.php', 'local.php'] as $file) {
            $path = $base . $file;
            if (file_exists($path)) {
                $config = array_replace_recursive($config, require $path);
            }
        }

        if (!empty($config['languages']) && is_array($config['languages'])) {
            return array_keys($config['languages']);
        }

        return ['fr', 'en', 'de', 'nl'];
    }
}
