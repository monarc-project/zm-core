<?php declare(strict_types=1);
/**
 * @link      https://github.com/monarc-project for the canonical source repository
 * @copyright Copyright (c) 2016-2026 Luxembourg House of Cybersecurity LHC.lu - Licensed under GNU Affero GPL v3
 * @license   MONARC is licensed under GNU Affero General Public License version 3
 */

namespace Monarc\Core\Tests\Fixture;

use Doctrine\DBAL\Types\Type;
use Doctrine\ORM\Configuration;
use Doctrine\ORM\EntityManager;
use Doctrine\ORM\Mapping\Driver\AttributeDriver;
use Doctrine\Persistence\Mapping\Driver\MappingDriverChain;
use Ramsey\Uuid\Doctrine\UuidType;

/** Fresh metadata and an isolated connection; configuration is supplied by the consumer. */
final class MappingTestEnvironment
{
    public static function create(array $drivers, string $name): EntityManager
    {
        $chain = new MappingDriverChain();
        foreach ($drivers[$name]['drivers'] as $namespace => $driver) {
            $chain->addDriver(new AttributeDriver($drivers[$driver]['paths']), $namespace);
        }
        if (!Type::hasType('uuid')) {
            Type::addType('uuid', UuidType::class);
        }
        $config = new Configuration();
        $config->setMetadataDriverImpl($chain);
        $config->setProxyDir(sys_get_temp_dir());
        $config->setProxyNamespace('Php84MappingTestProxies');
        $config->setAutoGenerateProxyClasses(true);

        return EntityManager::create(['driver' => 'pdo_sqlite', 'memory' => true], $config);
    }

    public static function physicalMappingFingerprint(array $metadata): string
    {
        $classes = [];
        foreach ($metadata as $mapping) {
            $associations = $mapping->associationMappings;
            foreach ($associations as &$association) {
                unset($association['inversedBy']);
            }
            unset($association);
            $classes[$mapping->name] = [
                'table' => $mapping->table,
                'fields' => $mapping->fieldMappings,
                'associations' => $associations,
            ];
        }
        ksort($classes);

        return hash('sha256', json_encode($classes, JSON_THROW_ON_ERROR));
    }
}
