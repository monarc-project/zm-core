<?php declare(strict_types=1);
/**
 * @link      https://github.com/monarc-project for the canonical source repository
 * @copyright Copyright (c) 2016-2026 Luxembourg House of Cybersecurity LHC.lu - Licensed under GNU Affero GPL v3
 * @license   MONARC is licensed under GNU Affero General Public License version 3
 */

namespace Unit\Guide;

use Doctrine\ORM\EntityNotFoundException;
use Doctrine\ORM\Tools\SchemaValidator;
use Monarc\Core\Entity\Guide;
use Monarc\Core\Entity\GuideItem;
use Monarc\Core\Exception\Exception;
use Monarc\Core\InputFormatter\Guide\GetGuidesInputFormatter;
use Monarc\Core\InputFormatter\Guide\GetGuideItemsInputFormatter;
use Monarc\Core\Service\ConnectedUserService;
use Monarc\Core\Tests\Fixture\GuideTestEnvironment;
use PHPUnit\Framework\TestCase;

/** @coversNothing */
class GuideWorkflowTest extends TestCase
{
    private GuideTestEnvironment $env;

    public static function setUpBeforeClass(): void
    {
        require_once __DIR__ . '/../../Fixture/GuideTestEnvironment.php';
    }

    protected function setUp(): void
    {
        $identity = $this->createMock(ConnectedUserService::class);
        $identity->method('getConnectedUser')->willReturn(GuideTestEnvironment::user());
        $this->env = new GuideTestEnvironment($identity);
    }

    protected function tearDown(): void
    {
        $this->env->manager->close();
    }

    public function testMappingsReuseDescriptionsAndRemoveUnusedAnalysisColumns(): void
    {
        $validator = new SchemaValidator($this->env->manager);
        foreach ([Guide::class, GuideItem::class] as $class) {
            $metadata = $this->env->manager->getClassMetadata($class);
            self::assertSame([], $validator->validateClass($metadata));
            self::assertSame('text', $metadata->fieldMappings['description3']['type']);
            self::assertSame($class === Guide::class, $metadata->fieldMappings['description3']['nullable']);
            self::assertFalse($metadata->hasField('anr'));
            self::assertFalse($metadata->hasAssociation('anr'));
            self::assertContains(
                \Monarc\Core\Entity\Traits\DescriptionsEntityTrait::class,
                class_uses($class)
            );
            self::assertSame($class, (new \ReflectionMethod($class, 'getId'))->getDeclaringClass()->getName());
            $columns = $this->env->manager->getConnection()->createSchemaManager()->listTableColumns(
                $metadata->getTableName()
            );
            self::assertArrayNotHasKey('anr_id', $columns);
            if ($class === GuideItem::class) {
                self::assertFalse($metadata->associationMappings['guide']['joinColumns'][0]['nullable']);
                self::assertTrue($columns['guide_id']->getNotnull());
            }
        }
        self::assertFalse(is_subclass_of(Guide::class, \Monarc\Core\Entity\AbstractEntity::class));
        self::assertFalse(is_subclass_of(GuideItem::class, \Monarc\Core\Entity\AbstractEntity::class));
    }

    public function testCrudPreservesTranslationsTypeLabelsAndAuditFields(): void
    {
        $id = $this->guide(1, ['description3' => 'Kontext', 'description4' => '', 'isWithItems' => false]);
        $data = $this->env->guideService->getGuideData($id);
        self::assertSame('Risk analysis context', $data['type']);
        self::assertSame('Kontext', $data['description3']);
        self::assertSame('', $data['description4']);
        self::assertNull($data['description2']);
        self::assertSame(0, $data['isWithItems']);
        self::assertSame('guide@example.invalid', $data['creator']);
        self::assertInstanceOf(\DateTime::class, $data['createdAt']);
        self::assertNull($data['updatedAt']);
        $list = $this->env->guideService->getList((new GetGuidesInputFormatter())->format([]));
        self::assertSame(1, $list[0]['type_id']);
        $this->env->guideService->update(
            $id,
            $this->env->guideValidator->validate(['description3' => null, 'isWithItems' => true], true)
        );
        $data = $this->env->guideService->getGuideData($id);
        self::assertNull($data['description3']);
        self::assertSame('Context', $data['description1']);
        self::assertSame(1, $data['isWithItems']);
        self::assertArrayNotHasKey('anr', $data);
        self::assertSame('guide@example.invalid', $data['updater']);
        self::assertInstanceOf(\DateTime::class, $data['updatedAt']);
        $this->env->guideService->update(
            $id,
            $this->env->guideValidator->validate(['type' => '1', 'isWithItems' => '0'], false)
        );
        self::assertNull($this->env->guideService->getGuideData($id)['description1']);
        $this->env->guideService->delete($id);
        self::assertSame(0, $this->env->guides->countAll());
    }

    public function testExistingNullAuditFieldsRemainReadableWithoutAnalysisFields(): void
    {
        $connection = $this->env->manager->getConnection();
        $connection->insert('guides', ['id' => 900, 'type' => 4, 'is_with_items' => 1]);
        $connection->insert('guides_items', [
            'id' => 901, 'guide_id' => 900, 'position' => 1,
            'description1' => '', 'description2' => '', 'description3' => '', 'description4' => '',
        ]);
        $rows = [$this->env->guideService->getGuideData(900), $this->env->itemService->getGuideItemData(901)];
        foreach ($rows as $data) {
            self::assertSame('', $data['creator']);
            self::assertNull($data['createdAt']);
            self::assertSame('', $data['updater']);
            self::assertNull($data['updatedAt']);
            self::assertArrayNotHasKey('anr', $data);
        }
    }

    public function testDuplicateCategoriesAreRejectedOnCreationAndUpdate(): void
    {
        $first = $this->guide(1);
        $second = $this->guide(2);
        $this->reject(fn () => $this->guide(1));
        $this->reject(
            fn () => $this->env->guideService->update(
                $second,
                $this->env->guideValidator->validate(['type' => 1], true)
            )
        );
        self::assertSame(2, $this->env->guides->countAll());
        self::assertSame('Risk management context', $this->env->guideService->getGuideData($second)['type']);
        $this->env->guideService->update($first, $this->env->guideValidator->validate(['type' => 1], true));
    }

    /** @dataProvider invalidGuides */
    public function testInvalidGuideWritesNeverPersist(array $data): void
    {
        $this->reject(fn () => $this->env->guideService->create($this->env->guideValidator->validate($data)));
        self::assertSame(0, $this->env->guides->countAll());
    }

    public static function invalidGuides(): array
    {
        return [
            [[]], [['type' => 0, 'isWithItems' => 1]], [['type' => 5, 'isWithItems' => 1]],
            [['type' => 'label', 'isWithItems' => 0]], [['type' => 1, 'isWithItems' => null]],
            [['type' => 1, 'isWithItems' => 2]], [['type' => 1, 'isWithItems' => 'bad']],
            [['type' => 1, 'isWithItems' => 1, 'description1' => []]],
        ];
    }

    public function testItemInsertionMovementDeletionAndPartialEditKeepContiguousOrdering(): void
    {
        $guide = $this->guide(1);
        $a = $this->item($guide, 'A');
        $b = $this->item($guide, 'B');
        $c = $this->item($guide, 'C', ['implicitPosition' => 1]);
        $d = $this->item($guide, 'D', ['implicitPosition' => 3, 'previous' => $a]);
        self::assertSame(['C', 'A', 'D', 'B'], $this->labels($guide));
        $this->env->itemService->update(
            $b,
            $this->env->itemValidator->validate(['implicitPosition' => 3, 'previous' => $c], true)
        );
        self::assertSame(['C', 'B', 'A', 'D'], $this->labels($guide));
        $this->env->itemService->update($b, $this->env->itemValidator->validate(['description2' => 'übersetzt'], true));
        self::assertSame('B', $this->env->itemService->getGuideItemData($b)['description1']);
        self::assertSame('übersetzt', $this->env->itemService->getGuideItemData($b)['description2']);
        $this->env->itemService->update($c, $this->env->itemValidator->validate(['implicitPosition' => 2], true));
        self::assertSame(['B', 'A', 'D', 'C'], $this->labels($guide));
        $this->env->itemService->deleteList($this->env->itemValidator->validateIds([$a, $d]));
        self::assertSame(['B', 'C'], $this->labels($guide));
        self::assertSame([1, 2], array_column($this->rows($guide), 'position'));
        self::assertSame($guide, $this->env->itemService->getGuideItemData($b)['guide']['id']);
        self::assertSame(1, $this->env->itemService->getGuideItemData($b)['guide']['type']);
    }

    /** @dataProvider invalidItems */
    public function testInvalidItemWritesFailBeforeChangingOrderingOrPersisting(array $data): void
    {
        $guide = $this->guide(1);
        $id = $this->item($guide, 'Retained');
        $this->reject(fn () => $this->env->itemService->update($id, $this->env->itemValidator->validate($data, true)));
        self::assertSame(['Retained'], $this->labels($guide));
        self::assertSame(1, $this->env->itemService->getGuideItemData($id)['position']);
    }

    public static function invalidItems(): array
    {
        return [
            [['guide' => null]], [['guide' => []]], [['implicitPosition' => 0]],
            [['implicitPosition' => 4]], [['implicitPosition' => 3]], [['previous' => 'invalid']],
            [['description2' => null]], [['description1' => false]], [['position' => 'invalid']],
        ];
    }

    public function testDatabaseReloadPreservesLongDescriptionsNullsEmptyStringsAndRelationships(): void
    {
        $description = str_repeat('é', 300);
        $guide = $this->guide(1, ['description3' => $description]);
        $id = $this->item($guide, 'Child', ['description3' => $description]);
        $this->env->manager->clear();
        $item = $this->env->itemService->getGuideItemData($id);
        self::assertSame($description, $item['description3']);
        self::assertSame('', $item['description2']);
        self::assertSame($guide, $item['guide']['id']);
        self::assertSame($description, $item['guide']['description3']);
        self::assertNull($item['guide']['description2']);
        self::assertSame('guide@example.invalid', $item['creator']);
    }

    public function testMovingBetweenGuidesRenumbersBothAndRejectsForeignPrevious(): void
    {
        $one = $this->guide(1);
        $two = $this->guide(2);
        $a = $this->item($one, 'A');
        $b = $this->item($one, 'B');
        $other = $this->item($two, 'Other');
        $this->reject(fn () => $this->env->itemService->update($a, $this->env->itemValidator->validate([
            'implicitPosition' => 3, 'previous' => $other,
        ], true)));
        $this->reject(fn () => $this->env->itemService->update($a, $this->env->itemValidator->validate([
            'implicitPosition' => 3, 'previous' => $a,
        ], true)));
        self::assertSame(['A', 'B'], $this->labels($one));
        $this->env->itemService->update(
            $a,
            $this->env->itemValidator->validate(['guide' => $two, 'implicitPosition' => 1], true)
        );
        self::assertSame(['B'], $this->labels($one));
        self::assertSame(['A', 'Other'], $this->labels($two));
        self::assertSame(1, $this->env->itemService->getGuideItemData($b)['position']);
    }

    public function testGlobalGuideContentIgnoresAnalysisFields(): void
    {
        $guide = $this->guide(1, ['anr' => 9]);
        $item = $this->item($guide, 'Global item', ['anr_id' => 9]);
        self::assertArrayNotHasKey('anr', $this->env->guideService->getGuideData($guide));
        self::assertArrayNotHasKey('anr', $this->env->itemService->getGuideItemData($item));
        self::assertSame($guide, $this->env->itemService->getGuideItemData($item)['guide']['id']);
    }

    public function testAngularSelectorsAndTranslationsKeepTheirValues(): void
    {
        $guide = $this->env->guideService->create($this->env->guideValidator->validate([
            'type' => '1', 'isWithItems' => '0', 'mode' => null,
            'description1' => '  Guide text  ', 'description2' => '', 'description3' => null,
        ]));
        $data = $this->env->guideService->getGuideData($guide);
        self::assertSame(0, $data['isWithItems']);
        self::assertSame('  Guide text  ', $data['description1']);
        self::assertNull($data['description3']);
        $item = $this->env->itemService->create($this->env->itemValidator->validate([
            'guide' => (string)$guide, 'implicitPosition' => '2', 'previous' => null,
            'description1' => '<p>Item text</p>',
        ]));
        $this->env->itemService->update(
            $item,
            $this->env->itemValidator->validate(['description2' => '  Translation  '], true)
        );
        $data = $this->env->itemService->getGuideItemData($item);
        self::assertSame('<p>Item text</p>', $data['description1']);
        self::assertSame('  Translation  ', $data['description2']);
        self::assertSame('', $data['description4']);
        self::assertSame($guide, $data['guide']['id']);
        $this->env->itemService->update($item, $this->env->itemValidator->validate(['position' => 70000], true));
        self::assertSame(1, $this->env->itemService->getGuideItemData($item)['position']);
        $this->env->itemService->update($item, $this->env->itemValidator->validate(['position' => 0], true));
        self::assertSame(1, $this->env->itemService->getGuideItemData($item)['position']);
        $largeDescription = str_repeat('x', 65536);
        foreach ([$this->env->guideValidator, $this->env->itemValidator] as $validator) {
            self::assertSame(
                $largeDescription,
                $validator->validate(['description1' => $largeDescription], true)['description1']
            );
        }
    }

    public function testCountAndListUseTheSameGuideAndSearchFiltersBeforePagination(): void
    {
        $one = $this->guide(1);
        $two = $this->guide(2);
        $this->item($one, 'match one');
        $this->item($one, 'match two');
        $this->item($one, 'different');
        $this->item($two, 'match outside');
        $params = (new GetGuideItemsInputFormatter())->format([
            'guide' => (string)$one, 'filter' => 'match', 'page' => 2, 'limit' => 1,
        ]);
        self::assertSame(2, $this->env->itemService->getCount($params));
        self::assertSame('match two', $this->env->itemService->getList($params)[0]['description1']);
    }

    public function testInvalidCreatesAndMissingDeleteIdsCannotPartiallyMutate(): void
    {
        $guide = $this->guide(1);
        $item = $this->item($guide, 'Retained');
        $this->reject(
            fn () => $this->env->itemService->create($this->env->itemValidator->validate(['guide' => 'bad']))
        );
        try {
            $this->env->itemService->deleteList($this->env->itemValidator->validateIds([$item, 999]));
            self::fail('Missing identifier must fail the whole deletion');
        } catch (EntityNotFoundException $error) {
            self::assertStringContainsString('was not found', $error->getMessage());
            self::assertSame(1, $this->env->items->countAll());
        }
        $this->reject(
            fn () => $this->env->guideService->deleteList($this->env->guideValidator->validateIds([$guide, 'bad'])),
            EntityNotFoundException::class
        );
        self::assertSame(['Retained'], $this->labels($guide));
        $this->env->guideService->delete($guide);
        self::assertSame(0, $this->env->items->countAll());
    }

    public function testInvalidPersistedReferencesUseTableExceptionsWithoutMutation(): void
    {
        $one = $this->guide(1);
        $two = $this->guide(2);
        $previous = $this->item($two, 'Outside');
        $this->reject(fn () => $this->env->guideService->getGuideData(999), EntityNotFoundException::class);
        $this->reject(fn () => $this->env->itemService->getGuideItemData(999), EntityNotFoundException::class);
        $this->reject(
            fn () => $this->env->itemService->create($this->env->itemValidator->validate(['guide' => 999])),
            EntityNotFoundException::class
        );
        $this->reject(fn () => $this->env->itemService->create($this->env->itemValidator->validate([
            'guide' => $one, 'implicitPosition' => 3, 'previous' => $previous,
        ])));
        self::assertSame([], $this->labels($one));
        $this->reject(
            fn () => $this->env->guideService->deleteList($this->env->guideValidator->validateIds([$one, 999])),
            EntityNotFoundException::class
        );
        self::assertSame(2, $this->env->guides->countAll());
    }

    public function testSerializedNumericDataCanBeCopiedWithoutReusingIdentifiersOrAuditInput(): void
    {
        $id = $this->guide(1, ['description3' => 'Copy me']);
        $original = $this->env->guides->findById($id);
        $snapshot = clone $original;
        self::assertSame($original->getDescriptions(), $snapshot->getDescriptions());
        $data = $this->env->guideService->serialize($snapshot, false);
        $data['type'] = 2;
        $data['creator'] = 'spoofed';
        $copy = $this->env->guideService->create($this->env->guideValidator->validate($data));
        self::assertNotSame($id, $copy);
        self::assertSame('Copy me', $this->env->guideService->getGuideData($copy)['description3']);
        self::assertSame('guide@example.invalid', $this->env->guideService->getGuideData($copy)['creator']);
        $item = $this->item($id, 'Child');
        $itemData = $this->env->itemService->getGuideItemData($item);
        $itemData['guide'] = $copy;
        $newItem = $this->env->itemService->create($this->env->itemValidator->validate($itemData));
        self::assertNotSame($item, $newItem);
        self::assertSame(['Child'], $this->labels($copy));
    }

    /** @dataProvider invalidQueries */
    public function testQueryFormatterRejectsUnsupportedFieldsAndMalformedValues(array $query): void
    {
        $this->reject(fn () => (new GetGuideItemsInputFormatter())->format($query));
    }

    public static function invalidQueries(): array
    {
        return [[['order' => 'position DESC; DROP TABLE guides']], [['order' => ['id']]],
            [['filter' => ['description1' => 'x']]], [['guide' => 'oops']], [['limit' => -1]], [['order' => 'anr']]];
    }

    private function guide(int $type, array $extra = []): int
    {
        return $this->env->guideService->create($this->env->guideValidator->validate(array_merge([
            'type' => $type, 'isWithItems' => 1, 'description1' => 'Context',
        ], $extra)));
    }

    private function item(int $guide, string $label, array $extra = []): int
    {
        return $this->env->itemService->create($this->env->itemValidator->validate(array_merge([
            'guide' => $guide, 'description1' => $label,
        ], $extra)));
    }

    private function rows(int $guide): array
    {
        return $this->env->itemService->getList((new GetGuideItemsInputFormatter())->format(['guide' => $guide]));
    }

    private function labels(int $guide): array
    {
        return array_column($this->rows($guide), 'description1');
    }

    private function reject(callable $action, string $exceptionClass = Exception::class): void
    {
        try {
            $action();
            self::fail('Expected validation failure');
        } catch (\Exception $error) {
            self::assertInstanceOf($exceptionClass, $error);
            if ($error instanceof Exception) {
                self::assertSame(412, $error->getCode());
            }
        }
    }
}
