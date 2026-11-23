<?php declare(strict_types=1);
/**
 * @link      https://github.com/monarc-project for the canonical source repository
 * @copyright Copyright (c) 2016-2026 Luxembourg House of Cybersecurity LHC.lu - Licensed under GNU Affero GPL v3
 * @license   MONARC is licensed under GNU Affero General Public License version 3
 */

namespace Monarc\Core\Tests\Runtime;

use Doctrine\DBAL\Types\StringType;
use Doctrine\ORM\Tools\SchemaValidator;
use Monarc\Core\Entity;
use Monarc\Core\InputFormatter\Historical\GetHistoricalsInputFormatter;
use Monarc\Core\Service;
use Monarc\Core\Table;
use Psr\Container\ContainerInterface;
use RuntimeException;

/** Local development only. Connection-local tables isolate every fixture write. */
class HistoricalRuntimeChecks
{
    public static function run(ContainerInterface $container, string &$phase): array
    {
        $phase = 'historical common schema and metadata';
        $manager = $container->get('doctrine.entitymanager.orm_default');
        $connection = $manager->getConnection();
        $metadata = $manager->getClassMetadata(Entity\Historical::class);
        self::check((new SchemaValidator($manager))->validateClass($metadata) === [], 'historical mapping');
        self::check($metadata->fieldMappings['sourceId']['type'] === 'string', 'sourceId metadata');
        $column = $connection->createSchemaManager()->listTableColumns('historicals')['source_id'];
        self::check(
            $column->getType() instanceof StringType && $column->getLength() === 64 && !$column->getNotnull(),
            'installed nullable string(64) source_id'
        );
        self::check((int)$connection->fetchOne(
            'SELECT COUNT(*) FROM phinxlog WHERE version = ?',
            ['20230901112005']
        ) === 1, 'existing source_id migration installed');
        $table = $container->get(Table\HistoricalTable::class);
        $amvTable = $container->get(Table\AmvTable::class);
        $managerProperty = new \ReflectionProperty(Table\AbstractTable::class, 'entityManager');
        self::check($managerProperty->getValue($table) === $manager, 'history uses common manager');
        self::check($managerProperty->getValue($amvTable) === $manager, 'writer uses same manager');
        $service = $container->get(Service\HistoricalService::class);
        $amvService = $container->get(Service\AmvService::class);
        $formatter = new GetHistoricalsInputFormatter();
        $fingerprints = [];
        foreach (['historicals' => 'id', 'amvs' => 'uuid'] as $name => $key) {
            $fingerprints[$name] = self::fingerprint($connection, $name, $key);
        }
        $phase = 'installed historical reads';
        $service->getList($formatter->format(['order' => '-createdAt']));
        $manager->clear();
        $temporaryTables = [];
        try {
            foreach (['historicals', 'assets', 'threats', 'vulnerabilities', 'amvs', 'measures_amvs'] as $name) {
                $phase = 'historical temporary ' . $name;
                $definition = $connection->createSchemaManager()->introspectTable($name);
                foreach ($definition->getForeignKeys() as $key) {
                    $definition->removeForeignKey($key->getName());
                }
                foreach ($connection->getDatabasePlatform()->getCreateTableSQL($definition) as $sql) {
                    $connection->executeStatement(str_replace('CREATE TABLE', 'CREATE TEMPORARY TABLE', $sql));
                }
                $temporaryTables[] = $name;
            }
            $phase = 'historical isolated AMV writer';
            $asset = (new Entity\Asset())->setType(Entity\Asset::TYPE_SECONDARY)->setCode('PHP84-07-A');
            $threat = (new Entity\Threat())->setCode('PHP84-07-T');
            $otherThreat = (new Entity\Threat())->setCode('PHP84-07-T2');
            $vulnerability = (new Entity\Vulnerability())->setCode('PHP84-07-V');
            foreach ([$asset, $threat, $otherThreat, $vulnerability] as $reference) {
                $manager->persist($reference);
            }
            $manager->flush();
            $data = [
                'asset' => $asset->getUuid(),
                'threat' => $threat->getUuid(),
                'vulnerability' => $vulnerability->getUuid(),
                'measures' => [],
            ];
            $amv = $amvService->create($data);
            $amvService->update($amv->getUuid(), array_replace($data, ['threat' => $otherThreat->getUuid()]));
            $amvService->delete($amv->getUuid());
            $rows = $service->getList($formatter->format(['order' => 'id']));
            self::check(count($rows) === 3, 'three AMV events');
            self::check(array_column($rows, 'action') === ['create', 'create', 'delete'], 'unchanged actions');
            self::check(count(array_unique(array_column($rows, 'id'))) === 3, 'fresh generated identifiers');
            foreach ($rows as $row) {
                self::check($row['sourceId'] === $amv->getUuid(), 'AMV UUID retained');
                self::check($row['creator'] === $container->get(Service\ConnectedUserService::class)
                    ->getConnectedUser()->getEmail(), 'new author email');
            }
            $queued = $service->create(['sourceId' => 42, 'details' => ''], false);
            self::check($table->countAll() === 3, 'deferred history not flushed');
            self::check(
                !(new \ReflectionProperty(Entity\Historical::class, 'id'))->isInitialized($queued),
                'deferred identifier unassigned'
            );
            $standalone = $service->create(['sourceId' => $amv->getUuid(), 'details' => null]);
            self::check($standalone->getId() !== $queued->getId(), 'standalone boundary flushes queued entries');
            $connection->insert('historicals', ['source_id' => '7', 'creator' => 'Legacy author']);
            $manager->clear();
            $legacy = $service->getList($formatter->format(['filter' => 'Legacy author']))[0];
            self::check($legacy['details'] === [''] && $legacy['createdAt'] === '', 'legacy null rendering');
            self::check($legacy['sourceId'] === '7' && $legacy['label1'] === null, 'legacy values unchanged');
            $params = $formatter->format(['filter' => 'PHP84-07', 'order' => '-createdAt', 'page' => 2, 'limit' => 2]);
            self::check(
                $service->getCount($params) === 3 && count($service->getList($params)) === 1,
                'search/list/count/pagination agreement'
            );
        } finally {
            $manager->clear();
            foreach (array_reverse($temporaryTables) as $name) {
                $connection->executeStatement('DROP TEMPORARY TABLE ' . $name);
            }
            foreach (['historicals' => 'id', 'amvs' => 'uuid'] as $name => $key) {
                self::check(
                    self::fingerprint($connection, $name, $key) === $fingerprints[$name],
                    'installed ' . $name . ' unchanged'
                );
            }
        }
        $phase = 'separate modern action history read';
        $container->get(Service\ActionHistoryService::class)->getActionsHistoryByAction('PHP84-07-read-only', 1);

        return [
            'schema' => 'nullable string(64); migration 20230901112005 installed',
            'amvWriter' => 'create/update/delete passed',
            'historicalReads' => 'legacy/null/UUID/numeric/search/pagination passed',
            'commonManager' => 'history and AMV share orm_default',
            'installedFingerprints' => $fingerprints,
            'installedDataUnchanged' => true,
            'modernActionHistoryRead' => 'passed',
        ];
    }

    private static function fingerprint(object $connection, string $table, string $key): string
    {
        return hash('sha256', json_encode(
            $connection->fetchAllAssociative('SELECT * FROM ' . $table . ' ORDER BY ' . $key),
            JSON_THROW_ON_ERROR
        ));
    }

    private static function check(bool $passed, string $name): void
    {
        if (!$passed) {
            throw new RuntimeException('Historical runtime check failed: ' . $name);
        }
    }
}
