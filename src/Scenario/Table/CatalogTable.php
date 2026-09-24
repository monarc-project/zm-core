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
        $template['release'] = $this->connection->fetchAssociative(
            'SELECT * FROM scenario_catalog_releases WHERE resource_type = ? AND resource_uuid = ? '
            . 'AND resource_version = ? ORDER BY id DESC LIMIT 1',
            ['template', $uuid, $version]
        ) ?: null;

        return $template;
    }


    public function insertDraft(string $kind, string $uuid, int $version, array $data, int $authorId): int
    {
        $fields = [
            'stable_uuid' => $uuid,
            'version' => $version,
            'semantic_version' => $data['semanticVersion'] ?? $version . '.0.0',
            'provenance' => $data['provenance'],
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
            'provenance' => $data['provenance'],
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
            'provenance' => $fields['provenance'],
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
            . ' SET provenance = :provenance, semantic_version = :semanticVersion, revision = :nextRevision'
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
                'description' => $translation['description'],
            ];
            foreach ([
                'riskSourceGuidance',
                'causeGuidance',
                'monitoringGuidance',
                'consequenceGuidance',
                'subjectGuidance',
                'controlGuidance',
            ] as $field) {
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
        $this->connection->delete('scenario_catalog_tag_links', ['resource_type' => $kind, 'resource_id' => $id]);
        $this->connection->delete('scenario_catalog_references', ['resource_type' => $kind, 'resource_id' => $id]);
        $this->connection->delete('scenario_catalog_suggestions', ['resource_type' => $kind, 'resource_id' => $id]);
        if ($kind === 'template') {
            $this->connection->delete('scenario_catalog_template_events', ['template_id' => $id]);
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
                    'SELECT id FROM scenario_catalog_events WHERE stable_uuid = ? AND version = ?',
                    [$eventReference['stableUuid'], $eventReference['version']]
                );
                if ($eventId === false) {
                    throw new InvalidArgumentException('Referenced event definition does not exist.');
                }
                $this->connection->insert('scenario_catalog_template_events', [
                    'template_id' => $id, 'event_id' => $eventId, 'position' => $position + 1,
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
