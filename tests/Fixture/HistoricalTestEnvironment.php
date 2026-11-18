<?php declare(strict_types=1);
/**
 * @link      https://github.com/monarc-project for the canonical source repository
 * @copyright Copyright (c) 2016-2026 Luxembourg House of Cybersecurity LHC.lu - Licensed under GNU Affero GPL v3
 * @license   MONARC is licensed under GNU Affero General Public License version 3
 */

namespace Monarc\Core\Tests\Fixture;

use Doctrine\Common\Annotations\AnnotationReader;
use Doctrine\DBAL\Types\Type;
use Doctrine\ORM\Configuration;
use Doctrine\ORM\EntityManager;
use Doctrine\ORM\Mapping\Driver\AnnotationDriver;
use Doctrine\ORM\Tools\SchemaTool;
use Monarc\Core\Entity;
use Monarc\Core\Service;
use Monarc\Core\Table;
use Ramsey\Uuid\Doctrine\UuidType;

/** A private in-memory graph; no installed history or AMV rows are touched. */
class HistoricalTestEnvironment
{
    public EntityManager $manager;
    public Table\HistoricalTable $table;
    public Service\HistoricalService $service;

    public function __construct(public Service\ConnectedUserService $connectedUserService)
    {
        if (!Type::hasType(UuidType::NAME)) {
            Type::addType(UuidType::NAME, UuidType::class);
        }
        $config = new Configuration();
        $config->setMetadataDriverImpl(new AnnotationDriver(new AnnotationReader()));
        $config->setProxyDir(sys_get_temp_dir());
        $config->setProxyNamespace('HistoricalTestProxies');
        $this->manager = EntityManager::create(['driver' => 'pdo_sqlite', 'memory' => true], $config);
        $metadata = array_map($this->manager->getClassMetadata(...), [
            Entity\Historical::class,
            Entity\Asset::class,
            Entity\Threat::class,
            Entity\Vulnerability::class,
            Entity\Amv::class,
        ]);
        (new SchemaTool($this->manager))->createSchema($metadata);
        // AMV deletion unlinks this inverse collection; no measure entities are needed by the fixture.
        $this->manager->getConnection()->executeStatement(
            'CREATE TABLE measures_amvs (measure_id VARCHAR(36), amv_id VARCHAR(36))'
        );
        $this->table = new Table\HistoricalTable($this->manager);
        $this->service = new Service\HistoricalService($this->table, $connectedUserService);
    }

    public function close(): void
    {
        $this->manager->close();
    }
}
