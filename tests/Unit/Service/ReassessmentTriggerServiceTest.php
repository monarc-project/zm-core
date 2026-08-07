<?php declare(strict_types=1);

namespace Unit\Service;

use Monarc\Core\Entity\ReassessmentTrigger;
use Monarc\Core\Entity\User;
use Monarc\Core\Service\ConfigService;
use Monarc\Core\Service\ConnectedUserService;
use Monarc\Core\Service\ReassessmentTriggerService;
use Monarc\Core\Table\ReassessmentTriggerTable;
use PHPUnit\Framework\TestCase;

class ReassessmentTriggerServiceTest extends TestCase
{
    /**
     * @covers ReassessmentTriggerService::create
     */
    public function testCreateStoresTranslationFields(): void
    {
        $table = $this->createMock(ReassessmentTriggerTable::class);
        $table->expects($this->once())
            ->method('save')
            ->with($this->callback(function (ReassessmentTrigger $reassessmentTrigger) {
                return $reassessmentTrigger->getTriggerTypeTranslations() === ['fr' => 'Periodic review']
                    && $reassessmentTrigger->getDescriptionTranslations() === ['fr' => 'Review trigger']
                    && $reassessmentTrigger->getMonitoringApproachTranslations() === ['fr' => 'SOC alerts'];
            }));

        $reassessmentTrigger = $this->createService($table)->create([
            'triggerTypes' => ['fr' => 'Periodic review'],
            'descriptions' => ['fr' => 'Review trigger'],
            'monitoringApproaches' => ['fr' => 'SOC alerts'],
        ]);

        self::assertSame(['fr' => 'Periodic review'], $reassessmentTrigger->getTriggerTypeTranslations());
        self::assertSame(['fr' => 'Review trigger'], $reassessmentTrigger->getDescriptionTranslations());
        self::assertSame(['fr' => 'SOC alerts'], $reassessmentTrigger->getMonitoringApproachTranslations());
    }

    /**
     * @covers ReassessmentTriggerService::create
     */
    public function testCreateStoresMonitoringApproachesForMultipleLanguages(): void
    {
        $table = $this->createMock(ReassessmentTriggerTable::class);
        $table->expects($this->once())
            ->method('save')
            ->with($this->callback(function (ReassessmentTrigger $reassessmentTrigger) {
                return $reassessmentTrigger->getMonitoringApproachTranslations() === [
                    'fr' => 'Approche FR',
                    'en' => 'Approach EN',
                    'de' => 'Ansatz DE',
                ];
            }));

        $service = $this->createService($table);

        $reassessmentTrigger = $service->create([
            'triggerType' => 'Periodic review',
            'description' => 'Review trigger',
            'monitoringApproaches' => [
                'fr' => 'Approche FR',
                'en' => 'Approach EN',
                'de' => 'Ansatz DE',
            ],
        ]);

        self::assertSame(
            [
                'fr' => 'Approche FR',
                'en' => 'Approach EN',
                'de' => 'Ansatz DE',
            ],
            $reassessmentTrigger->getMonitoringApproachTranslations()
        );
    }

    /**
     * @covers ReassessmentTriggerService::create
     */
    public function testCreateDropsUnsupportedAndEmptyMonitoringApproachTranslations(): void
    {
        $table = $this->createMock(ReassessmentTriggerTable::class);
        $table->expects($this->once())
            ->method('save')
            ->with($this->callback(function (ReassessmentTrigger $reassessmentTrigger) {
                return $reassessmentTrigger->getMonitoringApproachTranslations() === [
                    'fr' => 'Approche FR',
                ];
            }));

        $service = $this->createService($table);

        $reassessmentTrigger = $service->create([
            'triggerType' => 'Periodic review',
            'description' => 'Review trigger',
            'monitoringApproaches' => [
                'fr' => ' Approche FR ',
                'en' => '',
                'xx' => 'Unsupported language',
            ],
        ]);

        self::assertSame(
            [
                'fr' => 'Approche FR',
            ],
            $reassessmentTrigger->getMonitoringApproachTranslations()
        );
    }

    /**
     * @covers ReassessmentTriggerService::getTriggerTypes
     * @covers ReassessmentTriggerService::getDescriptions
     * @covers ReassessmentTriggerService::getMonitoringApproaches
     */
    public function testGetTranslationsUsesSupportedLanguagesAndFallbacks(): void
    {
        $reassessmentTrigger = (new ReassessmentTrigger())
            ->setTriggerTypeTranslations(['en' => 'Periodic review'])
            ->setDescriptionTranslations(['de' => 'Deutsche Beschreibung'])
            ->setMonitoringApproachTranslations(['en' => 'SOC alerts']);

        $service = $this->createService($this->createMock(ReassessmentTriggerTable::class));

        self::assertSame([
            'fr' => 'Periodic review',
            'en' => 'Periodic review',
            'de' => 'Periodic review',
            'nl' => 'Periodic review',
        ], $service->getTriggerTypes($reassessmentTrigger));
        self::assertSame([
            'fr' => 'Deutsche Beschreibung',
            'en' => 'Deutsche Beschreibung',
            'de' => 'Deutsche Beschreibung',
            'nl' => 'Deutsche Beschreibung',
        ], $service->getDescriptions($reassessmentTrigger));
        self::assertSame([
            'fr' => 'SOC alerts',
            'en' => 'SOC alerts',
            'de' => 'SOC alerts',
            'nl' => 'SOC alerts',
        ], $service->getMonitoringApproaches($reassessmentTrigger));
    }

    /**
     * @covers ReassessmentTriggerService::update
     */
    public function testUpdateDoesNotClearMonitoringApproachWhenEmptyTranslationsAreIgnored(): void
    {
        $reassessmentTrigger = (new ReassessmentTrigger())
            ->setTriggerTypeTranslations(['en' => 'Periodic review'])
            ->setDescriptionTranslations(['en' => 'Review trigger'])
            ->setMonitoringApproachTranslations(['en' => 'Existing monitoring approach']);

        $table = $this->createMock(ReassessmentTriggerTable::class);
        $table->expects($this->once())
            ->method('findById')
            ->with(3)
            ->willReturn($reassessmentTrigger);
        $table->expects($this->once())
            ->method('save')
            ->with($this->callback(function (ReassessmentTrigger $updatedTrigger) {
                return $updatedTrigger->getMonitoringApproachTranslations() === [
                    'en' => 'Existing monitoring approach',
                ];
            }));

        $service = $this->createService($table);
        $updatedTrigger = $service->update(3, [
            'monitoringApproaches' => [],
        ]);

        self::assertSame(['en' => 'Existing monitoring approach'], $updatedTrigger->getMonitoringApproachTranslations());
    }

    private function createService(ReassessmentTriggerTable $reassessmentTriggerTable): ReassessmentTriggerService
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
        $configService->method('getActiveLanguageCodes')->willReturn(['fr', 'en', 'de', 'nl']);
        $configService->method('getDefaultLanguageCode')->willReturn('fr');
        $configService->method('getConfigOption')->willReturnMap([
            ['defaultLanguageIndex', 1, 1],
        ]);

        return new ReassessmentTriggerService($reassessmentTriggerTable, $configService, $connectedUserService);
    }
}
