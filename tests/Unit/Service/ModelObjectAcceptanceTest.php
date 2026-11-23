<?php declare(strict_types=1);
/**
 * @link      https://github.com/monarc-project for the canonical source repository
 * @copyright Copyright (c) 2016-2026 Luxembourg House of Cybersecurity LHC.lu - Licensed under GNU Affero GPL v3
 * @license   MONARC is licensed under GNU Affero General Public License version 3
 */

namespace Monarc\Core\Tests\Unit\Service;

use Monarc\Core\Entity;
use Monarc\Core\Exception\Exception;
use Monarc\Core\Service;
use Monarc\Core\Table;
use PHPUnit\Framework\TestCase;

/** @coversNothing */
class ModelObjectAcceptanceTest extends TestCase
{
    /** @dataProvider combinations */
    public function testModelObjectAndAssetCombinations(
        bool $generic,
        int $objectMode,
        int $assetMode,
        string $link
    ): void {
        $model = $this->model(11)->setIsGeneric($generic);
        $asset = (new Entity\Asset())->setMode($assetMode);
        if ($link !== 'none') {
            // Different instances with the same persisted ID must remain accepted.
            $asset->addModel($this->model($link === 'same' ? 11 : 12));
        }
        $object = (new Entity\MonarcObject())->setMode($objectMode)->setAsset($asset);
        if ($generic && $objectMode === Entity\ObjectSuperClass::MODE_SPECIFIC) {
            $this->expectException(Exception::class);
            $this->expectExceptionCode(412);
            $this->expectExceptionMessage('You cannot add a specific object to a generic model');
        } elseif (!$generic && $assetMode === Entity\AssetSuperClass::MODE_SPECIFIC && $link !== 'same') {
            $this->expectException(Exception::class);
            $this->expectExceptionCode(412);
            $this->expectExceptionMessage('You cannot add an object with specific asset unrelated to a specific model');
        } else {
            $this->addToAssertionCount(1);
        }
        $this->service()->validateObjectAcceptance($model, $object);
    }

    public static function combinations(): iterable
    {
        foreach ([true, false] as $generic) {
            foreach ([Entity\ObjectSuperClass::MODE_GENERIC, Entity\ObjectSuperClass::MODE_SPECIFIC] as $objectMode) {
                foreach ([Entity\AssetSuperClass::MODE_GENERIC, Entity\AssetSuperClass::MODE_SPECIFIC] as $assetMode) {
                    foreach (['none', 'same', 'different'] as $link) {
                        yield [$generic, $objectMode, $assetMode, $link];
                    }
                }
            }
        }
    }

    public function testForcedAssetDeterminesAcceptanceWithoutChangingTheObject(): void
    {
        $model = $this->model(11)->setIsGeneric(false);
        $unrelated = (new Entity\Asset())->setMode(Entity\AssetSuperClass::MODE_SPECIFIC);
        $linked = (new Entity\Asset())->setMode(Entity\AssetSuperClass::MODE_SPECIFIC)->addModel($model);
        $object = (new Entity\MonarcObject())->setAsset($unrelated);
        $this->service()->validateObjectAcceptance($model, $object, $linked);
        self::assertSame($unrelated, $object->getAsset());

        $this->expectException(Exception::class);
        $this->expectExceptionCode(412);
        $this->service()->validateObjectAcceptance($model, $object, $unrelated);
    }

    private function model(int $id): Entity\Model
    {
        $model = new Entity\Model();
        (new \ReflectionProperty($model, 'id'))->setValue($model, $id);

        return $model;
    }

    private function service(): Service\ModelService
    {
        $connectedUserService = $this->createMock(Service\ConnectedUserService::class);
        $connectedUserService->method('getConnectedUser')->willReturn($this->createMock(Entity\UserSuperClass::class));

        return new Service\ModelService(
            $this->createMock(Table\ModelTable::class),
            $this->createMock(Table\AnrTable::class),
            $this->createMock(Service\AnrService::class),
            $this->createMock(Service\AnrInstanceMetadataFieldService::class),
            $connectedUserService
        );
    }
    public function testModelAnalysisAssociationPersistsFromTheOwningSide(): void
    {
        require_once __DIR__ . '/../../Fixture/MappingTestEnvironment.php';
        $config = require dirname(__DIR__, 3) . '/config/module.config.php';
        $manager = \Monarc\Core\Tests\Fixture\MappingTestEnvironment::create(
            $config['doctrine']['driver'],
            'orm_default'
        );
        try {
            $metadata = array_map($manager->getClassMetadata(...), [Entity\Anr::class, Entity\Model::class]);
            foreach ((new \Doctrine\ORM\Tools\SchemaTool($manager))->getCreateSchemaSql($metadata) as $sql) {
                $sql = preg_replace('/^(CREATE INDEX) (\w+) ON (\w+)/', '$1 $3_$2 ON $3', $sql);
                $manager->getConnection()->executeStatement($sql);
            }
            $anr = (new Entity\Anr())->setLabels([])->setDescriptions([]);
            $model = (new Entity\Model())->setAnr($anr)->setLabels([])->setDescriptions([]);
            $manager->persist($model);
            $manager->flush();
            $anrId = $anr->getId();
            $modelId = $model->getId();
            $manager->clear();
            $reloaded = $manager->find(Entity\Anr::class, $anrId);
            self::assertSame($modelId, $reloaded->getModel()->getId());
            self::assertSame($reloaded, $reloaded->getModel()->getAnr());
        } finally {
            $manager->close();
        }
    }
}
