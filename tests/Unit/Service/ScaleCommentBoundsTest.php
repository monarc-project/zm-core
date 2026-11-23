<?php declare(strict_types=1);
/**
 * @link      https://github.com/monarc-project for the canonical source repository
 * @copyright Copyright (c) 2016-2026 Luxembourg House of Cybersecurity LHC.lu - Licensed under GNU Affero GPL v3
 * @license   MONARC is licensed under GNU Affero General Public License version 3
 */

namespace Monarc\Core\Tests\Unit\Service;

use Monarc\Core\Entity as Entity;
use Monarc\Core\Entity\UserSuperClass;
use Monarc\Core\Exception\Exception;
use Monarc\Core\Service\ConnectedUserService;
use Monarc\Core\Service\ScaleCommentService;
use Monarc\Core\Table as Table;
use PHPUnit\Framework\TestCase;
use Monarc\Core\Tests\Fixture\MappingTestEnvironment;
use Monarc\Core\Tests\Fixture\ScaleAssociationAssertions;

/** @coversNothing */
class ScaleCommentBoundsTest extends TestCase
{

    /** @dataProvider boundaries */
    public function testCreateChecksPersistedScaleBoundsBeforeSaving(int $min, int $max, int $index, bool $valid): void
    {
        $anr = new Entity\Anr();
        $scale = new Entity\Scale($anr, [
            'type' => Entity\Scale::TYPE_IMPACT,
            'min' => $min,
            'max' => $max,
        ]);
        $scaleTable = $this->createMock(Table\ScaleTable::class);
        $scaleTable->expects(self::once())->method('findByIdAndAnr')->with(7, $anr)->willReturn($scale);
        $scaleCommentTable = $this->createMock(Table\ScaleCommentTable::class);
        $scaleCommentTable->expects($valid ? self::once() : self::never())->method('save');
        $user = $this->createMock(UserSuperClass::class);
        $user->method('getEmail')->willReturn('scale@example.invalid');
        $connectedUserService = $this->createMock(ConnectedUserService::class);
        $connectedUserService->method('getConnectedUser')->willReturn($user);
        $service = new ScaleCommentService(
            $scaleCommentTable,
            $scaleTable,
            $this->createMock(Table\ScaleImpactTypeTable::class),
            $connectedUserService
        );
        if (!$valid) {
            $this->expectException(Exception::class);
            $this->expectExceptionCode(0);
            $this->expectExceptionMessage(sprintf('The scale index "%d" is out of bounds.', $index));
        }
        $scaleComment = $service->create($anr, ['scaleId' => 7, 'scaleIndex' => $index, 'scaleValue' => 0]);
        self::assertSame($index, $scaleComment->getScaleIndex());
        self::assertSame(0, $scaleComment->getScaleValue());
        self::assertSame($anr, $scaleComment->getAnr());
        self::assertSame('scale@example.invalid', $scaleComment->getCreator());
        self::assertTrue($scale->getScaleComments()->contains($scaleComment));
    }

    public static function boundaries(): iterable
    {
        yield 'below minimum' => [-2, 2, -3, false];
        yield 'minimum' => [-2, 2, -2, true];
        yield 'zero' => [-2, 2, 0, true];
        yield 'maximum' => [-2, 2, 2, true];
        yield 'above maximum' => [-2, 2, 3, false];
        yield 'single value range' => [0, 0, 0, true];
        yield 'above single value' => [0, 0, 1, false];
    }
    public function testBidirectionalPersistenceAndFreshEntityCopies(): void
    {
        require_once __DIR__ . '/../../Fixture/MappingTestEnvironment.php';
        require_once __DIR__ . '/../../Fixture/ScaleAssociationAssertions.php';
        $core = require dirname(__DIR__, 4) . '/zm-core/config/module.config.php';
        $drivers = $core['doctrine']['driver'];
        $manager = MappingTestEnvironment::create($drivers, 'orm_cli');
        try {
            ScaleAssociationAssertions::checkScalePersistenceAndCopy(
                $manager,
                Entity\Anr::class,
                Entity\Scale::class,
                Entity\ScaleComment::class
            );
        } finally {
            $manager->close();
        }
    }
}
