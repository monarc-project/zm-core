<?php declare(strict_types=1);
/**
 * @link      https://github.com/monarc-project for the canonical source repository
 * @copyright Copyright (c) 2016-2026 Luxembourg House of Cybersecurity LHC.lu - Licensed under GNU Affero GPL v3
 * @license   MONARC is licensed under GNU Affero General Public License version 3
 */

namespace Monarc\Core\Tests\Unit\Runtime;

use DateTime;
use Doctrine\ORM\EntityManager;
use Doctrine\ORM\Tools\SchemaTool;
use Monarc\Core\Entity\Asset;
use Monarc\Core\Entity\Guide;
use Monarc\Core\Entity\GuideItem;
use Monarc\Core\Tests\Fixture\MappingTestEnvironment;
use Monarc\Core\Tests\Fixture\MappingSnapshot;
use PHPUnit\Framework\TestCase;

/** @coversNothing */
final class NativeMappingTest extends TestCase
{
    public function testSchemaComparisonPreservesMappingsAcrossConnectionCharsets(): void
    {
        require_once dirname(__DIR__, 2) . '/Fixture/MappingTestEnvironment.php';
        require_once dirname(__DIR__, 2) . '/Fixture/MappingSnapshot.php';
        $config = require dirname(__DIR__, 3) . '/config/module.config.php';
        $manager = MappingTestEnvironment::create($config['doctrine']['driver'], 'orm_default');
        $utf8mb4Manager = EntityManager::create(
            ['driver' => 'pdo_sqlite', 'memory' => true, 'charset' => 'utf8mb4'],
            clone $manager->getConfiguration()
        );
        try {
            self::assertSame(MappingSnapshot::capture($manager), MappingSnapshot::capture($utf8mb4Manager));
        } finally {
            $manager->close();
            $utf8mb4Manager->close();
        }
    }

    public function testTraitFieldsOverridesAndLifecycleCallbacksSurvivePersistence(): void
    {
        require_once dirname(__DIR__, 2) . '/Fixture/MappingTestEnvironment.php';
        $config = require dirname(__DIR__, 3) . '/config/module.config.php';
        $manager = MappingTestEnvironment::create($config['doctrine']['driver'], 'orm_default');
        try {
            $guideMapping = $manager->getClassMetadata(Guide::class);
            $itemMapping = $manager->getClassMetadata(GuideItem::class);
            self::assertTrue($guideMapping->fieldMappings['description1']['nullable']);
            self::assertFalse($itemMapping->fieldMappings['description1']['nullable']);
            self::assertSame('label1', $manager->getClassMetadata(Asset::class)->fieldMappings['label1']['columnName']);
            foreach ((new SchemaTool($manager))->getCreateSchemaSql([$guideMapping, $itemMapping]) as $sql) {
                $sql = preg_replace('/^(CREATE INDEX) (\w+) ON (\w+)/', '$1 $3_$2 ON $3', $sql);
                $manager->getConnection()->executeStatement($sql);
            }
            $before = new DateTime();
            $guide = (new Guide())
                ->setType(1)
                ->setIsWithItems(1)
                ->setDescriptions(['description1' => ' Native audit '])
                ->setCreator('fixture');
            $item = (new GuideItem())->setGuide($guide)->setPosition(1)->setCreator('fixture');
            $manager->persist($guide);
            $manager->persist($item);
            $manager->flush();
            $id = $guide->getId();
            self::assertGreaterThanOrEqual($before->getTimestamp(), $guide->getCreatedAt()->getTimestamp());
            self::assertNotNull($item->getCreatedAt());
            self::assertNull($guide->getUpdatedAt());
            $manager->clear();
            $guide = $manager->find(Guide::class, $id);
            self::assertSame(' Native audit ', $guide->getDescriptions()['description1']);
            self::assertSame('fixture', $guide->getCreator());
            $guide->setDescriptions(['description1' => 'Updated'])->setUpdater('editor');
            $manager->flush();
            self::assertNotNull($guide->getUpdatedAt());
            $updatedAt = $guide->getUpdatedAt()->format('Y-m-d H:i:s');
            $manager->clear();
            $guide = $manager->find(Guide::class, $id);
            self::assertSame($updatedAt, $guide->getUpdatedAt()->format('Y-m-d H:i:s'));
            self::assertSame('editor', $guide->getUpdater());
        } finally {
            $manager->close();
        }
    }
}
