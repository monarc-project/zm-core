<?php declare(strict_types=1);

namespace Unit\Service;

use Monarc\Core\Entity\RiskSource;
use Monarc\Core\Entity\User;
use Monarc\Core\Exception\Exception;
use Monarc\Core\Service\ConfigService;
use Monarc\Core\Service\ConnectedUserService;
use Monarc\Core\Service\RiskSourceService;
use Monarc\Core\Table\RiskSourceTable;
use PHPUnit\Framework\TestCase;

class RiskSourceServiceTest extends TestCase
{
    /**
     * @covers RiskSourceService::create
     */
    public function testCreateStoresLabelsAsInlineTranslations(): void
    {
        $table = $this->createMock(RiskSourceTable::class);
        $table->expects($this->once())
            ->method('save')
            ->with($this->callback(function (RiskSource $riskSource) {
                return $riskSource->getLabelTranslations() === ['fr' => 'Cloud provider failure']
                    && $riskSource->isActive() === true
                    && $riskSource->isDefault() === false
                    && $riskSource->getCreator() === 'risk@example.com';
            }), true);

        $service = $this->createService($table);

        $riskSource = $service->create(['label' => '  Cloud provider failure  ']);

        self::assertSame(['fr' => 'Cloud provider failure'], $riskSource->getLabelTranslations());
        self::assertTrue($riskSource->isActive());
        self::assertFalse($riskSource->isDefault());
    }

    /**
     * @covers RiskSourceService::update
     */
    public function testUpdateChangesLabelsAndStatus(): void
    {
        $riskSource = (new RiskSource())
            ->setLabelTranslations([
                'fr' => 'Attaquant externe',
                'en' => 'External attacker',
            ])
            ->setIsDefault(true)
            ->setIsActive(true);

        $table = $this->createMock(RiskSourceTable::class);
        $table->expects($this->once())
            ->method('findById')
            ->with(7)
            ->willReturn($riskSource);
        $table->expects($this->once())
            ->method('save')
            ->with($riskSource, true);

        $service = $this->createService($table);
        $updatedRiskSource = $service->update(7, [
            'label' => ' Supplier / third party ',
            'labels' => [
                'fr' => ' Fournisseur / tiers ',
                'en' => ' Supplier / third party ',
            ],
            'isActive' => false,
        ]);

        self::assertSame([
            'fr' => 'Fournisseur / tiers',
            'en' => 'Supplier / third party',
        ], $updatedRiskSource->getLabelTranslations());
        self::assertFalse($updatedRiskSource->isActive());
        self::assertSame('risk@example.com', $updatedRiskSource->getUpdater());
    }

    /**
     * @covers RiskSourceService::getDisplayLabel
     * @covers RiskSourceService::getLabels
     */
    public function testDisplayAndLabelsUseConfiguredLanguageFallbacks(): void
    {
        $riskSource = (new RiskSource())->setLabelTranslations([
            'en' => 'External attacker',
            'de' => 'Externer Angreifer',
        ]);

        $service = $this->createService($this->createMock(RiskSourceTable::class));

        self::assertSame('External attacker', $service->getDisplayLabel($riskSource));
        self::assertSame([
            'fr' => 'External attacker',
            'en' => 'External attacker',
            'de' => 'Externer Angreifer',
            'nl' => 'External attacker',
        ], $service->getLabels($riskSource));
    }

    /**
     * @covers RiskSourceService::delete
     */
    public function testDeleteRemovesCustomRiskSourceWhenUnused(): void
    {
        $riskSource = (new RiskSource())
            ->setLabelTranslations(['en' => 'Temporary source'])
            ->setIsDefault(false);

        $table = $this->createMock(RiskSourceTable::class);
        $table->expects($this->once())
            ->method('findById')
            ->with(9)
            ->willReturn($riskSource);
        $table->expects($this->once())
            ->method('isUsedInRisks')
            ->with($riskSource)
            ->willReturn(false);
        $table->expects($this->once())
            ->method('remove')
            ->with($riskSource);

        $service = $this->createService($table);
        $service->delete(9);
    }

    /**
     * @covers RiskSourceService::delete
     */
    public function testDeleteRejectsDefaultRiskSource(): void
    {
        $riskSource = (new RiskSource())
            ->setLabelTranslations(['en' => 'Default source'])
            ->setIsDefault(true);

        $table = $this->createMock(RiskSourceTable::class);
        $table->expects($this->once())
            ->method('findById')
            ->with(4)
            ->willReturn($riskSource);
        $table->expects($this->never())->method('isUsedInRisks');
        $table->expects($this->never())->method('remove');

        $service = $this->createService($table);

        $this->expectException(Exception::class);
        $this->expectExceptionMessage('Default risk sources cannot be removed.');
        $service->delete(4);
    }

    /**
     * @covers RiskSourceService::delete
     */
    public function testDeleteRejectsRiskSourceLinkedToInstanceRisks(): void
    {
        $riskSource = (new RiskSource())
            ->setLabelTranslations(['en' => 'Used source'])
            ->setIsDefault(false);

        $table = $this->createMock(RiskSourceTable::class);
        $table->expects($this->once())
            ->method('findById')
            ->with(5)
            ->willReturn($riskSource);
        $table->expects($this->once())
            ->method('isUsedInRisks')
            ->with($riskSource)
            ->willReturn(true);
        $table->expects($this->never())->method('remove');

        $service = $this->createService($table);

        $this->expectException(Exception::class);
        $this->expectExceptionMessage('Risk source linked to instance risks cannot be removed.');
        $service->delete(5);
    }

    private function createService(RiskSourceTable $riskSourceTable): RiskSourceService
    {
        $connectedUserService = $this->createMock(ConnectedUserService::class);
        $connectedUserService->method('getConnectedUser')->willReturn(
            new User([
                'firstname' => 'Risk',
                'lastname' => 'Owner',
                'email' => 'risk@example.com',
                'language' => 1,
                'creator' => 'Tests',
                'role' => [],
            ])
        );

        $configService = $this->createMock(ConfigService::class);
        $configService->method('getLanguageCodes')->willReturn([1 => 'fr', 2 => 'en', 3 => 'de', 4 => 'nl']);
        $configService->method('getActiveLanguageCodes')->willReturn([1 => 'fr', 2 => 'en', 3 => 'de', 4 => 'nl']);
        $configService->method('getConfigOption')->willReturnMap([
            ['defaultLanguageIndex', 1, 1],
        ]);

        return new RiskSourceService($riskSourceTable, $configService, $connectedUserService);
    }
}
