<?php declare(strict_types=1);
/**
 * @link      https://github.com/monarc-project for the canonical source repository
 * @copyright Copyright (c) 2016-2026 Luxembourg House of Cybersecurity LHC.lu - Licensed under GNU Affero GPL v3
 * @license   MONARC is licensed under GNU Affero General Public License version 3
 */

namespace Monarc\Core\Tests\Fixture;

use Doctrine\ORM\EntityManager;
use Doctrine\ORM\EntityManagerInterface;
use RuntimeException;

/** Metadata and per-class SQL comparison, with known legacy schema errors kept explicit. */
final class MappingSnapshot
{
    public static function capture(EntityManagerInterface $manager): array
    {
        $metadata = $manager->getMetadataFactory()->getAllMetadata();
        $errors = (new \Doctrine\ORM\Tools\SchemaValidator($manager))->validateMapping();
        if ($errors !== []) {
            throw new RuntimeException(json_encode($errors, JSON_THROW_ON_ERROR));
        }
        $classes = [];
        foreach ($metadata as $m) {
            $classes[$m->name] = array_intersect_key(get_object_vars($m), array_flip([
                'name', 'namespace', 'rootEntityName', 'isMappedSuperclass', 'isEmbeddedClass',
                'identifier', 'inheritanceType', 'generatorType', 'fieldMappings', 'associationMappings',
                'embeddedClasses', 'table', 'lifecycleCallbacks', 'entityListeners', 'changeTrackingPolicy',
                'isReadOnly', 'versionField', 'isVersioned', 'discriminatorColumn', 'discriminatorMap',
                'discriminatorValue', 'customRepositoryClassName', 'sequenceGeneratorDefinition',
                'customGeneratorDefinition', 'parentClasses', 'subClasses'
            ]));
        }
        ksort($classes);
        // Compare SQL under the same connection defaults as the pre-conversion fixture.
        // Live office connections use different charsets; their metadata remains unchanged.
        $comparisonEntityManager = EntityManager::create(
            ['driver' => 'pdo_sqlite', 'memory' => true],
            clone $manager->getConfiguration(),
            $manager->getEventManager()
        );
        $tool = new \Doctrine\ORM\Tools\SchemaTool($comparisonEntityManager);
        $platform = new \Doctrine\DBAL\Platforms\MySQL80Platform();
        $schemaErrors = [];
        $sql = [];
        // SchemaTool cannot assemble the legacy graph globally (duplicate explicit/join tables).
        // Compare generated SQL per concrete class; retain exact mappings separately.
        foreach ($metadata as $m) {
            if ($m->isMappedSuperclass) {
                continue;
            }
            $copy = clone $m;
            while (true) {
                try {
                    $classSql = $tool->getSchemaFromMetadata([$copy])->toSql($platform);
                    sort($classSql);
                    $sql[$m->name] = $classSql;
                    break;
                } catch (\Doctrine\DBAL\Schema\Exception\ColumnDoesNotExist $e) {
                    $schemaErrors[$m->name][] = $e->getMessage();
                    if (!preg_match('/name "([^"]+)" on table "([^"]+)"/', $e->getMessage(), $match)) {
                        throw $e;
                    }
                    $removed = false;
                    foreach (['indexes', 'uniqueConstraints'] as $kind) {
                        foreach ($copy->table[$kind] ?? [] as $index => $definition) {
                            if (in_array($match[1], $definition['columns'] ?? [], true)) {
                                unset($copy->table[$kind][$index]);
                                $removed = true;
                            }
                        }
                        if (($copy->table[$kind] ?? null) === []) {
                            unset($copy->table[$kind]);
                        }
                    }
                    if (!$removed) {
                        throw $e;
                    }
                }
            }
        }
        ksort($sql);
        $comparisonEntityManager->close();
        $normalized = self::normalize($classes);
        return [
            'metadataCount' => count($metadata),
            'classes' => $normalized,
            'metadataSha256' => hash('sha256', json_encode($normalized, JSON_THROW_ON_ERROR)),
            'physicalMappingSha256' => MappingTestEnvironment::physicalMappingFingerprint($metadata),
            'schemaGenerationErrors' => $schemaErrors,
            'comparisonSchemaSql' => $sql,
            'comparisonSchemaSha256' => hash('sha256', json_encode($sql, JSON_THROW_ON_ERROR)),
            'mappingErrors' => $errors,
        ];
    }

    public static function fingerprints(array $snapshot): array
    {
        $snapshot['classes'] = array_map(
            static fn (array $mapping): string => hash('sha256', json_encode($mapping, JSON_THROW_ON_ERROR)),
            $snapshot['classes']
        );
        $snapshot['schemaGenerationErrors'] = self::normalize($snapshot['schemaGenerationErrors']);
        unset($snapshot['comparisonSchemaSql']);

        return $snapshot;
    }

    private static function normalize(mixed $value): mixed
    {
        if (!is_array($value)) {
            return $value;
        }
        if (!array_is_list($value)) {
            ksort($value);
        }

        return array_map(self::normalize(...), $value);
    }
}
