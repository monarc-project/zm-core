<?php declare(strict_types=1);
/**
 * @link      https://github.com/monarc-project for the canonical source repository
 * @copyright Copyright (c) 2016-2026 Luxembourg House of Cybersecurity LHC.lu - Licensed under GNU Affero GPL v3
 * @license   MONARC is licensed under GNU Affero General Public License version 3
 */

namespace Monarc\Core\Service;

use Monarc\Core\Entity\ReassessmentTrigger;
use Monarc\Core\Entity\UserSuperClass;
use Monarc\Core\InputFormatter\FormattedInputParams;
use Monarc\Core\Table\ReassessmentTriggerTable;
use Monarc\Core\Traits\TranslationNormalizationTrait;
use Monarc\Core\Traits\TranslationResolverTrait;

class ReassessmentTriggerService
{
    use TranslationNormalizationTrait;
    use TranslationResolverTrait;

    private UserSuperClass $connectedUser;

    /* ConfigService is used in TranslationResolverTrait. */
    public function __construct(
        private ReassessmentTriggerTable $reassessmentTriggerTable,
        private ConfigService $configService,
        ConnectedUserService $connectedUserService
    ) {
        $this->connectedUser = $connectedUserService->getConnectedUser();
    }

    /**
     * @return ReassessmentTrigger[]
     */
    public function getList(FormattedInputParams $params): array
    {
        return $this->reassessmentTriggerTable->findByParams($params);
    }

    public function getCount(FormattedInputParams $params): int
    {
        return $this->reassessmentTriggerTable->countByParams($params, 'id');
    }
    
    public function get(int $id): ReassessmentTrigger
    {
        /** @var ReassessmentTrigger $reassessmentTrigger */
        $reassessmentTrigger = $this->reassessmentTriggerTable->findById($id);

        return $reassessmentTrigger;
    }

    public function create(array $data): ReassessmentTrigger
    {
        $reassessmentTrigger = (new ReassessmentTrigger())
            ->setTriggerTypeTranslations(
                $this->normalizeTranslations($data['triggerTypes'] ?? [], $this->getSupportedLanguageCodes())
            )
            ->setDescriptionTranslations(
                $this->normalizeTranslations($data['descriptions'] ?? [], $this->getSupportedLanguageCodes())
            )
            ->setMonitoringApproachTranslations(
                $this->normalizeTranslations($data['monitoringApproaches'] ?? [], $this->getSupportedLanguageCodes())
            )
            ->setIsActive((bool)($data['isActive'] ?? true))
            ->setCreator($this->connectedUser->getEmail());

        $this->applyCreatePosition($reassessmentTrigger, $data);
        $this->reassessmentTriggerTable->save($reassessmentTrigger);

        return $reassessmentTrigger;
    }

    public function update(int $id, array $data): ReassessmentTrigger
    {
        $reassessmentTrigger = $this->get($id);

        if (!empty($data['triggerTypes'])) {
            $reassessmentTrigger->setTriggerTypeTranslations(
                $this->normalizeTranslations($data['triggerTypes'], $this->getSupportedLanguageCodes())
            );
        }
        if (!empty($data['descriptions'])) {
            $reassessmentTrigger->setDescriptionTranslations(
                $this->normalizeTranslations($data['descriptions'], $this->getSupportedLanguageCodes()));
        }
        if (!empty($data['monitoringApproaches'])) {
            $reassessmentTrigger->setMonitoringApproachTranslations(
                $this->normalizeTranslations($data['monitoringApproaches'], $this->getSupportedLanguageCodes())
            );
        }
        if (isset($data['isActive'])) {
            $reassessmentTrigger->setIsActive((bool)$data['isActive']);
        }

        $reassessmentTrigger->setUpdater($this->connectedUser->getEmail());

        if (isset($data['position']) && (int)$data['position'] !== $reassessmentTrigger->getPosition()) {
            $this->applyUpdatedPosition($reassessmentTrigger, (int)$data['position']);
        }

        $this->reassessmentTriggerTable->save($reassessmentTrigger);

        return $reassessmentTrigger;
    }

    public function delete(int $id): void
    {
        $reassessmentTrigger = $this->get($id);
        $this->reassessmentTriggerTable->incrementPositions(
            $reassessmentTrigger->getPosition() + 1,
            -1,
            -1,
            [],
            $this->connectedUser->getEmail()
        );
        $this->reassessmentTriggerTable->remove($reassessmentTrigger);
    }

    /**
     * It is called from the ng-client (FO) to fetch the predefined data from the common DB. 
     *
     * @return array<int, array<string, mixed>>
     */
    public function getSelectionData(string $languageCode, bool $includeInactive = false): array
    {
        $selectionData = [];
        foreach ($this->reassessmentTriggerTable->findActiveOrdered($includeInactive) as $reassessmentTrigger) {
            $selectionData[] = [
                'id' => $reassessmentTrigger->getId(),
                'triggerType' => $this->getDisplayTriggerType($reassessmentTrigger, $languageCode),
                'description' => $this->getDisplayDescription($reassessmentTrigger, $languageCode),
                'monitoringApproach' => $this->getDisplayMonitoringApproach($reassessmentTrigger, $languageCode),
                'isActive' => $reassessmentTrigger->isActive(),
                'position' => $reassessmentTrigger->getPosition(),
            ];
        }

        return $selectionData;
    }

    public function getDisplayTriggerType(ReassessmentTrigger $reassessmentTrigger, ?string $languageCode = null): string
    {
        return $this->resolveTranslation(
            $reassessmentTrigger->getTriggerTypeTranslations(),
            $reassessmentTrigger->getTriggerType() ?? '',
            $languageCode
        );
    }

    public function getDisplayDescription(ReassessmentTrigger $reassessmentTrigger, ?string $languageCode = null): string
    {
        return $this->resolveTranslation(
            $reassessmentTrigger->getDescriptionTranslations(),
            $reassessmentTrigger->getDescription(),
            $languageCode
        );
    }

    /**
     * @return array<string, string>
     */
    public function getTriggerTypes(ReassessmentTrigger $reassessmentTrigger): array
    {
        return $this->resolveTranslations(
            $reassessmentTrigger->getTriggerTypeTranslations(),
            $reassessmentTrigger->getTriggerType() ?? ''
        );
    }

    /**
     * @return array<string, string>
     */
    public function getDescriptions(ReassessmentTrigger $reassessmentTrigger): array
    {
        return $this->resolveTranslations(
            $reassessmentTrigger->getDescriptionTranslations(),
            $reassessmentTrigger->getDescription()
        );
    }

    public function getDisplayMonitoringApproach(
        ReassessmentTrigger $reassessmentTrigger,
        ?string $languageCode = null
    ): string {
        return $this->resolveTranslation(
            $reassessmentTrigger->getMonitoringApproachTranslations(),
            $reassessmentTrigger->getMonitoringApproach() ?? '',
            $languageCode
        );
    }

    /**
     * @return array<string, string>
     */
    public function getMonitoringApproaches(ReassessmentTrigger $reassessmentTrigger): array
    {
        return $this->resolveTranslations(
            $reassessmentTrigger->getMonitoringApproachTranslations(),
            $reassessmentTrigger->getMonitoringApproach() ?? ''
        );
    }

    private function applyCreatePosition(ReassessmentTrigger $reassessmentTrigger, array $data): void
    {
        $position = isset($data['position']) ? (int)$data['position'] : 0;
        $maxPosition = $this->reassessmentTriggerTable->findMaxPosition([]);

        if ($position <= 0 || $position > $maxPosition + 1) {
            $reassessmentTrigger->setPosition($maxPosition + 1);

            return;
        }

        $this->reassessmentTriggerTable->incrementPositions($position, -1, 1, [], $this->connectedUser->getEmail());
        $reassessmentTrigger->setPosition($position);
    }

    private function applyUpdatedPosition(ReassessmentTrigger $reassessmentTrigger, int $newPosition): void
    {
        $oldPosition = $reassessmentTrigger->getPosition();
        $maxPosition = $this->reassessmentTriggerTable->findMaxPosition([]);
        $newPosition = max(1, min($newPosition, $maxPosition));

        if ($newPosition < $oldPosition) {
            $this->reassessmentTriggerTable
                ->incrementPositions($newPosition, $oldPosition - 1, 1, [], $this->connectedUser->getEmail());
        } elseif ($newPosition > $oldPosition) {
            $this->reassessmentTriggerTable
                ->incrementPositions($oldPosition + 1, $newPosition, -1, [], $this->connectedUser->getEmail());
        }

        $reassessmentTrigger->setPosition($newPosition);
    }
}
