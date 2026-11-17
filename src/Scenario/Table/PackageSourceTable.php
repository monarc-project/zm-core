<?php declare(strict_types=1);
/**
 * @link      https://github.com/monarc-project for the canonical source repository
 * @copyright Copyright (c) 2016-2026 Luxembourg House of Cybersecurity LHC.lu - Licensed under GNU Affero GPL v3
 * @license   MONARC is licensed under GNU Affero General Public License version 3
 */

namespace Monarc\Core\Scenario\Table;

use Doctrine\DBAL\Connection;
use Doctrine\ORM\EntityManager;
use InvalidArgumentException;
use Monarc\Core\Entity\ScenarioTemplatePackageLink;

/** Only common, reusable sources; this class is always bound to the core EM. */
final class PackageSourceTable
{
    public const TYPES = ScenarioTemplatePackageLink::SOURCE_TYPES;

    /**
     * Types a template author deliberately selects. Controls are captured as
     * children of a selected referential and are never standalone sources.
     */
    public const AUTHORABLE_TYPES = ScenarioTemplatePackageLink::AUTHORABLE_SOURCE_TYPES;

    private Connection $connection;

    public function __construct(EntityManager $entityManager)
    {
        $this->connection = $entityManager->getConnection();
    }

    /** @return array{string, string, string, string} */
    private function definition(string $type): array
    {
        return match ($type) {
            'asset' => ['objects', 'uuid', "COALESCE(NULLIF(s.name1, ''), s.label1)",
                'EXISTS (SELECT 1 FROM assets a WHERE a.uuid = s.asset_id AND a.status = 1)'],
            'asset-type' => ['assets', 'uuid', 's.label1', 's.status = 1'],
            'threat' => ['threats', 'uuid', 's.label1', 's.status = 1'],
            'vulnerability' => ['vulnerabilities', 'uuid', 's.label1', 's.status = 1'],
            'referential' => ['referentials', 'uuid', 's.label1', '1 = 1'],
            'control' => ['measures', 'uuid', 's.label1',
                's.status = 1 AND EXISTS (SELECT 1 FROM referentials r WHERE r.uuid = s.referential_uuid)'],
            'risk-source' => ['risk_sources', 'id', 's.label', 's.is_active = 1'],
            default => throw new InvalidArgumentException('Unsupported package source type.'),
        };
    }

    /** @return array<string, mixed>|null */
    public function snapshot(string $type, string $identity): ?array
    {
        [$table, $key, $label, $active] = $this->definition($type);
        $row = $this->connection->fetchAssociative(
            "SELECT s.*, $label AS source_label FROM $table s WHERE s.$key = ? AND $active",
            [$identity]
        );
        if ($row === false || strcasecmp((string) $row[$key], $identity) !== 0
            || trim((string) $row['source_label']) === '') {
            return null;
        }
        if ($type === 'risk-source') {
            // Risk-source labels use the legacy JSON map, unlike label1--4.
            // Retain its first non-empty author representation in this release;
            // analysis-language/locale selection belongs to SRA-28.
            $labels = json_decode((string) $row['source_label'], true);
            if (is_array($labels)) {
                $readable = array_values(array_filter(
                    $labels,
                    static fn (mixed $label): bool => is_string($label) && trim($label) !== ''
                ));
                if ($readable === []) {
                    return null;
                }
                $row['source_label'] = $readable[0];
            }
        }
        $parent = match ($type) {
            'asset' => $this->snapshot('asset-type', (string) $row['asset_id']),
            'control' => $this->snapshot('referential', (string) $row['referential_uuid']),
            default => null,
        };
        if (in_array($type, ['asset', 'control'], true) && $parent === null) {
            return null;
        }
        // A deliberately bounded canonical/default representation, not a raw
        // row export (which could grow to include private legacy metadata).
        $source = [
            'type' => $type,
            'identity' => $identity,
            'label' => (string) $row['source_label'],
            'description' => $type === 'asset'
                ? (string) ($row['label1'] ?? '')
                : (string) ($row['description1'] ?? $row['description'] ?? $row['comment'] ?? ''),
            'code' => (string) ($row['code'] ?? ''),
            'sourceScope' => 'common-knowledge-base',
            'provenance' => (string) ($row['provenance'] ?? 'monarc-common:' . $type . ':' . $identity),
            'sourceVersion' => (string) ($row['revision'] ?? $row['updated_at'] ?? 'unversioned'),
            'parent' => $parent,
        ];
        $source['checksum'] = hash('sha256', json_encode($source, JSON_THROW_ON_ERROR));

        return $source;
    }

    /** @return array<int, array<string, mixed>> */
    public function controlsForReferential(string $referentialIdentity): array
    {
        $identities = $this->connection->fetchFirstColumn(
            'SELECT uuid FROM measures WHERE referential_uuid = ? AND status = 1 ORDER BY label1, uuid',
            [$referentialIdentity]
        );

        $controls = [];
        foreach ($identities as $identity) {
            $control = $this->snapshot('control', (string) $identity);
            if ($control !== null) {
                $controls[] = $control;
            }
        }

        return $controls;
    }

    /** @return array<string, mixed> */
    public function list(string $type, string $search, int $page, int $pageSize): array
    {
        [$table, $key, $label, $active] = $this->definition($type);
        $parameters = ['%' . $search . '%'];
        $where = "$active AND $label IS NOT NULL AND TRIM($label) <> '' AND $label LIKE ?";
        $rows = $this->connection->fetchAllAssociative(
            "SELECT s.$key AS identity FROM $table s WHERE $where ORDER BY $label, s.$key LIMIT ? OFFSET ?",
            [...$parameters, $pageSize, ($page - 1) * $pageSize],
            [\PDO::PARAM_STR, \PDO::PARAM_INT, \PDO::PARAM_INT]
        );
        $items = [];
        foreach ($rows as $row) {
            $source = $this->snapshot($type, (string) $row['identity']);
            if ($source !== null) {
                $items[] = $source;
            }
        }

        return [
            'items' => $items, 'page' => $page, 'pageSize' => $pageSize,
            'total' => (int) $this->connection->fetchOne("SELECT COUNT(*) FROM $table s WHERE $where", $parameters),
        ];
    }
}
