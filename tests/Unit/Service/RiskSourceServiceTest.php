<?php declare(strict_types=1);

namespace Unit\Service;

use Monarc\Core\Entity\RiskSource;
use Monarc\Core\Entity\Translation;
use Monarc\Core\Entity\User;
use Monarc\Core\Exception\Exception;
use Monarc\Core\Service\ConfigService;
use Monarc\Core\Service\ConnectedUserService;
use Monarc\Core\Service\RiskSourceService;
use Monarc\Core\Table\RiskSourceTable;
use Monarc\Core\Table\TranslationTable;
use PHPUnit\Framework\TestCase;

class RiskSourceServiceTest extends TestCase
{
    /**
     * @covers RiskSourceService::create
     */
    public function testCreateTrimsLabelAndMarksSourceAsCustom(): void
    {
        $table = $this->createMock(RiskSourceTable::class);
        $table->expects($this->once())
            ->method('save')
            ->with($this->callback(function (RiskSource $riskSource) {
                return $riskSource->getLabel() === 'Cloud provider failure'
                    && $riskSource->isActive() === true
                    && $riskSource->isDefault() === false
                    && $riskSource->getCreator() === 'risk@example.com';
            }), false);
        $table->expects($this->once())
            ->method('flush');
        $translationTable = $this->createMock(TranslationTable::class);
        $translationTable->expects($this->once())
            ->method('save')
            ->with($this->callback(function (Translation $translation) {
                return $translation->getType() === 'risk-source'
                    && $translation->getLang() === 'fr'
                    && $translation->getValue() === 'Cloud provider failure';
            }), false);

        $service = $this->createService($table, $translationTable);

        $riskSource = $service->create(['label' => '  Cloud provider failure  ']);

        self::assertSame('Cloud provider failure', $riskSource->getLabel());
        self::assertTrue($riskSource->isActive());
        self::assertFalse($riskSource->isDefault());
        self::assertNotSame('', $riskSource->getLabelTranslationKey());
    }

    /**
     * @covers RiskSourceService::update
     */
    public function testUpdateChangesLabelAndStatus(): void
    {
        $riskSource = (new RiskSource())
            ->setLabel('External attacker')
            ->setLabelTranslationKey('risk-source-external-attacker')
            ->setIsDefault(true)
            ->setIsActive(true);

        $table = $this->createMock(RiskSourceTable::class);
        $table->expects($this->once())
            ->method('findById')
            ->with(7)
            ->willReturn($riskSource);
        $table->expects($this->once())
            ->method('save')
            ->with($riskSource, false);
        $table->expects($this->once())
            ->method('flush');
        $translationTable = $this->createMock(TranslationTable::class);
        $translationTable->expects($this->once())
            ->method('findByTypeKeyAndLanguage')
            ->with('risk-source', 'risk-source-external-attacker', 'fr')
            ->willReturn(
                (new Translation())
                    ->setType('risk-source')
                    ->setKey('risk-source-external-attacker')
                    ->setLang('fr')
                    ->setValue('External attacker')
            );
        $translationTable->expects($this->once())
            ->method('save')
            ->with($this->callback(function (Translation $translation) {
                return $translation->getKey() === 'risk-source-external-attacker'
                    && $translation->getLang() === 'fr'
                    && $translation->getValue() === 'Supplier / third party';
            }), false);

        $service = $this->createService($table, $translationTable);
        $updatedRiskSource = $service->update(7, [
            'label' => ' Supplier / third party ',
            'isActive' => false,
        ]);

        self::assertSame('Supplier / third party', $updatedRiskSource->getLabel());
        self::assertFalse($updatedRiskSource->isActive());
        self::assertSame('risk@example.com', $updatedRiskSource->getUpdater());
    }

    /**
     * @covers RiskSourceService::deactivate
     */
    public function testDeactivateMarksRiskSourceAsInactive(): void
    {
        $riskSource = (new RiskSource())
            ->setLabel('Natural event')
            ->setIsActive(true);

        $table = $this->createMock(RiskSourceTable::class);
        $table->expects($this->once())
            ->method('findById')
            ->with(3)
            ->willReturn($riskSource);
        $table->expects($this->once())
            ->method('save')
            ->with($riskSource, false);
        $table->expects($this->once())
            ->method('flush');

        $service = $this->createService($table);
        $deactivatedRiskSource = $service->deactivate(3);

        self::assertFalse($deactivatedRiskSource->isActive());
        self::assertSame('risk@example.com', $deactivatedRiskSource->getUpdater());
    }

    /**
     * @covers RiskSourceService::delete
     */
    public function testDeleteRemovesCustomRiskSourceWhenUnused(): void
    {
        $riskSource = (new RiskSource())
            ->setLabelTranslationKey('risk-source-temporary')
            ->setLabel('Temporary source')
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
        $translationTable = $this->createMock(TranslationTable::class);
        $translationTable->expects($this->once())
            ->method('deleteListByKeys')
            ->with(['risk-source-temporary']);

        $service = $this->createService($table, $translationTable);
        $service->delete(9);
    }

    /**
     * @covers RiskSourceService::delete
     */
    public function testDeleteRejectsDefaultRiskSource(): void
    {
        $riskSource = (new RiskSource())
            ->setLabel('Default source')
            ->setIsDefault(true);

        $table = $this->createMock(RiskSourceTable::class);
        $table->expects($this->once())
            ->method('findById')
            ->with(4)
            ->willReturn($riskSource);
        $table->expects($this->never())
            ->method('isUsedInRisks');
        $table->expects($this->never())
            ->method('remove');

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
            ->setLabel('Used source')
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
        $table->expects($this->never())
            ->method('remove');

        $service = $this->createService($table);

        $this->expectException(Exception::class);
        $this->expectExceptionMessage('Risk source linked to instance risks cannot be removed.');
        $service->delete(5);
    }

    private function createService(
        RiskSourceTable $riskSourceTable,
        ?TranslationTable $translationTable = null
    ): RiskSourceService
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

        $translationTable ??= $this->createMock(TranslationTable::class);
        $configService = $this->createMock(ConfigService::class);
        $configService->method('getLanguageCodes')->willReturn([1 => 'fr', 2 => 'en', 3 => 'de', 4 => 'nl']);
        $configService->method('getActiveLanguageCodes')->willReturn([1 => 'fr', 2 => 'en', 3 => 'de', 4 => 'nl']);
        $configService->method('getConfigOption')->willReturnMap([
            ['defaultLanguageIndex', 1, 1],
        ]);

        return new RiskSourceService($riskSourceTable, $translationTable, $configService, $connectedUserService);
    }
}
