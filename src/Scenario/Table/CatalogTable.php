<?php declare(strict_types=1);

namespace Monarc\Core\Scenario\Table;

use Doctrine\DBAL\Connection;
use Doctrine\ORM\EntityManager;
use InvalidArgumentException;

/**
 * Owns shared Scenario Library persistence in monarc_common.
 *
 * BackOffice users live in a separate database, so actor IDs are stored as
 * auditable external references rather than cross-database foreign keys.
 */
final class CatalogTable
{
    private Connection $connection;

    public function __construct(EntityManager $entityManager)
    {
        $this->connection = $entityManager->getConnection();
    }

    /** Execute a catalog write atomically, including its relations and audit event. */
    public function transactional(callable $callback)
    {
        return $this->connection->transactional($callback);
    }

    /** @return array{items: array<int, array<string, mixed>>, page: int, pageSize: int, total: int} */
    public function list(
        string $kind,
        string $where,
        array $parameters,
        int $page,
        int $pageSize
    ): array {
        $table = $this->table($kind);
        $count = (int) $this->connection->fetchOne(
            "SELECT COUNT(*) FROM $table c WHERE $where",
            $parameters
        );
        $parameters['limit'] = $pageSize;
        $parameters['offset'] = ($page - 1) * $pageSize;
        $items = $this->connection->fetchAllAssociative(
            "SELECT c.* FROM $table c WHERE $where ORDER BY c.updated_at DESC LIMIT :limit OFFSET :offset",
            $parameters,
            ['limit' => \PDO::PARAM_INT, 'offset' => \PDO::PARAM_INT]
        );

        return ['items' => $items, 'page' => $page, 'pageSize' => $pageSize, 'total' => $count];
    }

    /** @return array{items: array<int, array<string, mixed>>, page: int, pageSize: int, total: int} */
    public function listPublishedTemplates(string $locale, string $search, int $page, int $pageSize): array
    {
        $where = 'c.lifecycle = :lifecycle';
        $parameters = ['lifecycle' => 'published'];
        if ($search !== '') {
            $where .= ' AND EXISTS (SELECT 1 FROM scenario_catalog_template_translations t '
                . 'WHERE t.template_id = c.id AND (t.title LIKE :search OR t.description LIKE :search))';
            $parameters['search'] = '%' . $search . '%';
        }

        $result = $this->list('template', $where, $parameters, $page, $pageSize);
        foreach ($result['items'] as &$template) {
            $translations = $this->findTranslations('template', (int) $template['id']);
            $template['translation'] = $this->translationForLocale($translations, $locale);
            $template['release'] = $this->connection->fetchAssociative(
                'SELECT uuid, released_at FROM scenario_catalog_releases '
                . 'WHERE resource_type = ? AND resource_uuid = ? AND resource_version = ? '
                . 'ORDER BY id DESC LIMIT 1',
                ['template', $template['stable_uuid'], (int) $template['version']]
            ) ?: null;
        }
        unset($template);

        return $result;
    }

    /** @return array<string, mixed>|null */
    public function find(string $kind, string $uuid, ?int $version = null): ?array
    {
        $sql = "SELECT * FROM {$this->table($kind)} WHERE stable_uuid = ?";
        $parameters = [$uuid];
        if ($version !== null) {
            $sql .= ' AND version = ?';
            $parameters[] = $version;
        }
        $row = $this->connection->fetchAssociative($sql . ' ORDER BY version DESC LIMIT 1', $parameters);

        return $row === false ? null : $row;
    }

    /** @return array<int, array<string, mixed>> */
    public function findVersions(string $kind, string $uuid): array
    {
        return $this->connection->fetchAllAssociative(
            "SELECT * FROM {$this->table($kind)} WHERE stable_uuid = ? ORDER BY version DESC",
            [$uuid]
        );
    }

    /** @return array<int, array<string, mixed>> */
    public function findTranslations(string $kind, int $id): array
    {
        return $this->connection->fetchAllAssociative(
            "SELECT * FROM {$this->translationTable($kind)} "
            . "WHERE {$this->foreignKey($kind)} = ?",
            [$id]
        );
    }

    /** @return array<int, string> */
    public function findTags(string $kind, int $id): array
    {
        return $this->connection->fetchFirstColumn(
            'SELECT t.label FROM scenario_catalog_tags t INNER JOIN scenario_catalog_tag_links l ON l.tag_id = t.id '
            . 'WHERE l.resource_type = ? AND l.resource_id = ? ORDER BY t.label',
            [$kind, $id]
        );
    }

    /** @return array<int, array{type: string, value: string}> */
    public function findReferences(string $kind, int $id): array
    {
        return array_map(
            static fn (array $reference): array => [
                'type' => $reference['reference_type'],
                'value' => $reference['reference_value'],
            ],
            $this->relations('scenario_catalog_references', $kind, $id)
        );
    }

    /** @return array<int, array{type: string, referenceType: string, referenceValue: string, guidance: ?string}> */
    public function findSuggestions(string $kind, int $id): array
    {
        return array_map(
            static fn (array $suggestion): array => [
                'type' => $suggestion['suggestion_type'],
                'referenceType' => $suggestion['reference_type'],
                'referenceValue' => $suggestion['reference_value'],
                'guidance' => $suggestion['guidance'],
            ],
            $this->relations('scenario_catalog_suggestions', $kind, $id)
        );
    }

    /** @return array<int, array{stableUuid: string, version: int}> */
    public function findTemplateEventReferences(int $templateId): array
    {
        return array_map(
            static fn (array $event): array => [
                'stableUuid' => $event['stable_uuid'],
                'version' => (int) $event['version'],
            ],
            $this->connection->fetchAllAssociative(
                'SELECT e.stable_uuid, e.version FROM scenario_catalog_template_events link '
                . 'INNER JOIN scenario_catalog_events e ON e.id = link.event_id '
                . 'WHERE link.template_id = ? ORDER BY link.position',
                [$templateId]
            )
        );
    }

    /**
     * Returns the complete, published template release required by a client
     * instantiation.  This intentionally returns database-shaped data: each
     * consumer snapshots it before applying its own presentation rules.
     *
     * @return array<string, mixed>|null
     */
    public function findPublishedTemplateSnapshot(string $uuid, int $version): ?array
    {
        $template = $this->connection->fetchAssociative(
            'SELECT * FROM scenario_catalog_templates WHERE stable_uuid = ? AND version = ? AND lifecycle = ?',
            [$uuid, $version, 'published']
        );
        if ($template === false) {
            return null;
        }

        $templateId = (int) $template['id'];
        $template['translations'] = $this->findTranslations('template', $templateId);
        $template['references'] = $this->relations('scenario_catalog_references', 'template', $templateId);
        $template['suggestions'] = $this->relations('scenario_catalog_suggestions', 'template', $templateId);
        $template['tags'] = $this->connection->fetchFirstColumn(
            'SELECT t.label FROM scenario_catalog_tags t INNER JOIN scenario_catalog_tag_links l ON l.tag_id = t.id '
            . 'WHERE l.resource_type = ? AND l.resource_id = ? ORDER BY t.label',
            ['template', $templateId]
        );
        $template['events'] = $this->connection->fetchAllAssociative(
            'SELECT e.*, te.position FROM scenario_catalog_template_events te '
            . 'INNER JOIN scenario_catalog_events e ON e.id = te.event_id '
            . 'WHERE te.template_id = ? ORDER BY te.position',
            [$templateId]
        );
        foreach ($template['events'] as &$event) {
            $event['translations'] = $this->findTranslations('event', (int) $event['id']);
            $event['references'] = $this->relations('scenario_catalog_references', 'event', (int) $event['id']);
            $event['suggestions'] = $this->relations('scenario_catalog_suggestions', 'event', (int) $event['id']);
        }
        unset($event);
        $template['risk_scenario_models'] = $this->findTemplateRiskModels($templateId);
        $template['release'] = $this->connection->fetchAssociative(
            'SELECT * FROM scenario_catalog_releases WHERE resource_type = ? AND resource_uuid = ? '
            . 'AND resource_version = ? ORDER BY id DESC LIMIT 1',
            ['template', $uuid, $version]
        ) ?: null;

        return $template;
    }

    /**
     * Version-local template models and their immutable event-release links.
     * The title snapshot is deliberately read from the link rather than the
     * current catalog translation so a later event release cannot rewrite it.
     *
     * @return array<int, array<string, mixed>>
     */
    public function findTemplateRiskModels(int $templateId): array
    {
        $models = $this->connection->fetchAllAssociative(
            'SELECT * FROM scenario_catalog_template_risk_models WHERE template_id = ? ORDER BY position, id',
            [$templateId]
        );
        foreach ($models as &$model) {
            $model['events'] = $this->connection->fetchAllAssociative(
                'SELECT event_stable_uuid AS stable_uuid, event_version, event_title_snapshot, guidance, position '
                . 'FROM scenario_catalog_template_model_events WHERE model_id = ? ORDER BY position, id',
                [(int) $model['id']]
            );
        }
        unset($model);

        return $models;
    }

    /** @return array<int, string> */
    public function templateModelEventWarnings(int $templateId): array
    {
        return array_values(array_filter($this->connection->fetchFirstColumn(
            'SELECT m.title FROM scenario_catalog_template_risk_models m '
            . 'LEFT JOIN scenario_catalog_template_model_events l ON l.model_id = m.id '
            . 'WHERE m.template_id = ? GROUP BY m.id, m.title HAVING COUNT(l.id) = 0 ORDER BY m.position',
            [$templateId]
        ), 'is_string'));
    }

    public function assertTemplateEventLinksPublishable(int $templateId): void
    {
        $unavailable = (int) $this->connection->fetchOne(
            'SELECT SUM(unavailable) FROM ('
            . 'SELECT COUNT(*) AS unavailable FROM scenario_catalog_template_events l '
            . 'INNER JOIN scenario_catalog_events e ON e.id = l.event_id '
            . 'WHERE l.template_id = ? AND e.lifecycle <> ? '
            . 'UNION ALL '
            . 'SELECT COUNT(*) AS unavailable FROM scenario_catalog_template_model_events l '
            . 'INNER JOIN scenario_catalog_template_risk_models m ON m.id = l.model_id '
            . 'INNER JOIN scenario_catalog_events e ON e.id = l.event_id '
            . 'WHERE m.template_id = ? AND e.lifecycle <> ?'
            . ') template_event_links',
            [$templateId, 'published', $templateId, 'published']
        );
        if ($unavailable > 0) {
            throw new InvalidArgumentException('Template event definitions must be published and non-deprecated.');
        }
    }


    public function insertDraft(string $kind, string $uuid, int $version, array $data, int $authorId): int
    {
        $fields = [
            'stable_uuid' => $uuid,
            'version' => $version,
            'semantic_version' => $data['semanticVersion'] ?? $version . '.0.0',
            // Provenance is technical lineage. Actor and timestamps are stored in
            // adjacent immutable columns; no workshop participant supplies it.
            'provenance' => $this->generatedProvenance($kind, $uuid, $version),
            'author_id' => $authorId,
        ];
        if ($kind === 'event') {
            $fields['category'] = $data['category'];
            $fields['guidance'] = $data['guidance'] ?? null;
        } else {
            $fields['assessment_guidance'] = $data['assessmentGuidance'] ?? null;
        }
        $this->connection->insert($this->table($kind), $fields);

        return (int) $this->connection->lastInsertId();
    }

    public function updateDraft(string $kind, int $id, array $data, int $revision): bool
    {
        $fields = [
            'semantic_version' => $data['semanticVersion'] ?? ($revision . '.0.0'),
            'revision' => $revision + 1,
        ];
        if ($kind === 'event') {
            $fields['category'] = $data['category'];
            $fields['guidance'] = $data['guidance'] ?? null;
        } else {
            $fields['assessment_guidance'] = $data['assessmentGuidance'] ?? null;
        }
        $parameters = [
            'semanticVersion' => $fields['semantic_version'],
            'nextRevision' => $fields['revision'],
            'id' => $id,
            'lifecycle' => 'draft',
            'revision' => $revision,
        ];
        if ($kind === 'event') {
            $set = ', category = :category, guidance = :guidance';
            $parameters['category'] = $fields['category'];
            $parameters['guidance'] = $fields['guidance'];
        } else {
            $set = ', assessment_guidance = :guidance';
            $parameters['guidance'] = $fields['assessment_guidance'];
        }

        return $this->connection->executeStatement(
            'UPDATE ' . $this->table($kind)
            . ' SET semantic_version = :semanticVersion, revision = :nextRevision'
            . $set . ' WHERE id = :id AND lifecycle = :lifecycle AND revision = :revision',
            $parameters
        ) === 1;
    }

    public function replaceTranslations(string $kind, int $id, array $translations): void
    {
        $translationTable = $this->translationTable($kind);
        $foreignKey = $this->foreignKey($kind);
        $this->connection->delete($translationTable, [$foreignKey => $id]);
        foreach ($translations as $locale => $translation) {
            $fields = [
                $foreignKey => $id,
                'locale' => $locale,
                'title' => $translation['title'],
                'description' => $translation['description'] ?? '',
            ];
            $guidanceFields = $kind === 'event'
                ? ['riskSourceGuidance', 'causeGuidance', 'monitoringGuidance']
                : ['consequenceGuidance', 'subjectGuidance', 'controlGuidance', 'monitoringGuidance'];
            foreach ($guidanceFields as $field) {
                $column = strtolower(preg_replace('/[A-Z]/', '_$0', $field));
                if (array_key_exists($field, $translation)) {
                    $fields[$column] = $translation[$field];
                }
            }
            $this->connection->insert($translationTable, $fields);
        }
    }

    public function replaceRelations(string $kind, int $id, array $data): void
    {
        if (array_key_exists('tags', $data)) {
            $this->connection->delete('scenario_catalog_tag_links', ['resource_type' => $kind, 'resource_id' => $id]);
        }
        if (array_key_exists('references', $data)) {
            $this->connection->delete('scenario_catalog_references', ['resource_type' => $kind, 'resource_id' => $id]);
        }
        if (array_key_exists('suggestions', $data)) {
            $this->connection->delete('scenario_catalog_suggestions', ['resource_type' => $kind, 'resource_id' => $id]);
        }
        if ($kind === 'template' && array_key_exists('eventRefs', $data)) {
            $this->connection->delete('scenario_catalog_template_events', ['template_id' => $id]);
        }
        if ($kind === 'template' && array_key_exists('riskScenarioModels', $data)) {
            $this->connection->delete('scenario_catalog_template_risk_models', ['template_id' => $id]);
        }
        foreach ($data['tags'] ?? [] as $tag) {
            $tagId = $this->connection->fetchOne('SELECT id FROM scenario_catalog_tags WHERE label = ?', [$tag]);
            if ($tagId === false) {
                $this->connection->insert('scenario_catalog_tags', ['label' => $tag]);
                $tagId = $this->connection->lastInsertId();
            }
            $this->connection->insert('scenario_catalog_tag_links', [
                'tag_id' => (int) $tagId,
                'resource_type' => $kind,
                'resource_id' => $id,
            ]);
        }
        foreach ($data['references'] ?? [] as $reference) {
            $this->connection->insert('scenario_catalog_references', [
                'resource_type' => $kind, 'resource_id' => $id,
                'reference_type' => $reference['type'], 'reference_value' => $reference['value'],
            ]);
        }
        foreach ($data['suggestions'] ?? [] as $suggestion) {
            $this->connection->insert('scenario_catalog_suggestions', [
                'resource_type' => $kind, 'resource_id' => $id,
                'suggestion_type' => $suggestion['type'], 'reference_type' => $suggestion['referenceType'],
                'reference_value' => $suggestion['referenceValue'], 'guidance' => $suggestion['guidance'] ?? null,
            ]);
        }
        if ($kind === 'template') {
            foreach ($data['eventRefs'] ?? [] as $position => $eventReference) {
                $eventId = $this->connection->fetchOne(
                    'SELECT id FROM scenario_catalog_events WHERE stable_uuid = ? AND version = ? '
                    . 'AND lifecycle = ?',
                    [$eventReference['stableUuid'], $eventReference['version'], 'published']
                );
                if ($eventId === false) {
                    throw new InvalidArgumentException('Referenced event definition is unavailable.');
                }
                $this->connection->insert('scenario_catalog_template_events', [
                    'template_id' => $id, 'event_id' => $eventId, 'position' => $position + 1,
                ]);
            }
            if (array_key_exists('riskScenarioModels', $data)) {
                $this->replaceTemplateRiskModels($id, $data['riskScenarioModels']);
            }
        }
    }

    /** @param array<int, array<string, mixed>> $models */
    private function replaceTemplateRiskModels(int $templateId, array $models): void
    {
        foreach ($models as $modelPosition => $model) {
            $modelUuid = $model['stableUuid'] ?? \Ramsey\Uuid\Uuid::uuid4()->toString();
            $this->connection->insert('scenario_catalog_template_risk_models', [
                'template_id' => $templateId,
                'stable_uuid' => $modelUuid,
                'title' => $model['title'],
                'description' => $model['description'] ?? null,
                'position' => $modelPosition + 1,
            ]);
            $modelId = (int) $this->connection->lastInsertId();
            foreach ($model['eventLinks'] as $eventPosition => $eventLink) {
                $event = $this->connection->fetchAssociative(
                    'SELECT e.id, e.stable_uuid, e.version, '
                    . 'COALESCE(en.title, fallback.title) AS title '
                    . 'FROM scenario_catalog_events e '
                    . 'LEFT JOIN scenario_catalog_event_translations en '
                    . 'ON en.event_id = e.id AND en.locale = ? '
                    . 'LEFT JOIN scenario_catalog_event_translations fallback '
                    . 'ON fallback.event_id = e.id '
                    . 'WHERE e.stable_uuid = ? AND e.version = ? AND e.lifecycle = ? '
                    . 'ORDER BY fallback.locale LIMIT 1',
                    ['en', $eventLink['stableUuid'], $eventLink['version'], 'published']
                );
                if ($event === false || !is_string($event['title']) || $event['title'] === '') {
                    throw new InvalidArgumentException('Referenced event definition is unavailable.');
                }
                $this->connection->insert('scenario_catalog_template_model_events', [
                    'model_id' => $modelId,
                    'event_id' => (int) $event['id'],
                    'event_stable_uuid' => $event['stable_uuid'],
                    'event_version' => (int) $event['version'],
                    'event_title_snapshot' => $event['title'],
                    'guidance' => $eventLink['guidance'] ?? null,
                    'position' => $eventPosition + 1,
                ]);
            }
        }
    }

    public function transition(
        string $kind,
        int $id,
        string $lifecycle,
        int $reviewerId,
        int $revision
    ): bool {
        $fields = ['lifecycle' => $lifecycle, 'reviewer_id' => $reviewerId, 'revision' => $revision + 1];
        if ($lifecycle === 'published') {
            $fields['published_at'] = gmdate('Y-m-d H:i:s');
        }
        if ($lifecycle === 'deprecated') {
            $fields['deprecated_at'] = gmdate('Y-m-d H:i:s');
        }
        $previousLifecycle = $lifecycle === 'review' ? 'draft' : ($lifecycle === 'published' ? 'review' : 'published');
        $updated = $this->connection->update(
            $this->table($kind),
            $fields,
            ['id' => $id, 'lifecycle' => $previousLifecycle, 'revision' => $revision]
        );
        if ($updated !== 1) {
            return false;
        }
        if ($lifecycle === 'published') {
            $resource = $this->connection->fetchAssociative(
                "SELECT stable_uuid, version FROM {$this->table($kind)} WHERE id = ?",
                [$id]
            );
            $this->connection->insert('scenario_catalog_releases', [
                'uuid' => \Ramsey\Uuid\Uuid::uuid4()->toString(),
                'resource_type' => $kind,
                'resource_uuid' => $resource['stable_uuid'],
                'resource_version' => $resource['version'],
                'released_by' => $reviewerId,
            ]);
        }

        return true;
    }

    public function audit(
        string $kind,
        string $uuid,
        int $version,
        string $action,
        int $actorId,
        string $correlationId,
        array $payload = []
    ): void {
        $this->connection->insert('scenario_catalog_audit_events', [
            'resource_type' => $kind,
            'resource_uuid' => $uuid,
            'resource_version' => $version,
            'action' => $action,
            'actor_id' => $actorId,
            'correlation_id' => $correlationId === '' ? null : $correlationId,
            'payload_json' => $payload === [] ? null : json_encode($payload, JSON_THROW_ON_ERROR),
        ]);
    }

    private function table(string $kind): string
    {
        return match ($kind) {
            'event' => 'scenario_catalog_events',
            'template' => 'scenario_catalog_templates',
            default => throw new InvalidArgumentException('Invalid catalog resource type.'),
        };
    }

    private function translationTable(string $kind): string
    {
        return match ($kind) {
            'event' => 'scenario_catalog_event_translations',
            'template' => 'scenario_catalog_template_translations',
            default => throw new InvalidArgumentException('Invalid catalog resource type.'),
        };
    }

    private function foreignKey(string $kind): string
    {
        return match ($kind) {
            'event' => 'event_id',
            'template' => 'template_id',
            default => throw new InvalidArgumentException('Invalid catalog resource type.'),
        };
    }

    private function generatedProvenance(string $kind, string $uuid, int $version): string
    {
        return sprintf('catalog:%s:%s:v%d', $kind, $uuid, $version);
    }


    /** @return array<int, array<string, mixed>> */
    private function relations(string $table, string $kind, int $id): array
    {
        return $this->connection->fetchAllAssociative(
            'SELECT * FROM ' . $table . ' WHERE resource_type = ? AND resource_id = ? ORDER BY id',
            [$kind, $id]
        );
    }

    /** @param array<int, array<string, mixed>> $translations @return array<string, mixed>|null */
    private function translationForLocale(array $translations, string $locale): ?array
    {
        foreach ($translations as $translation) {
            if ($translation['locale'] === $locale) {
                return $translation;
            }
        }
        foreach ($translations as $translation) {
            if ($translation['locale'] === 'en') {
                return $translation;
            }
        }

        return $translations[0] ?? null;
    }
}
