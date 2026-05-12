<?php declare(strict_types=1);
/**
 * @link      https://github.com/monarc-project for the canonical source repository
 * @copyright Copyright (c) 2016-2026 Luxembourg House of Cybersecurity LHC.lu - Licensed under GNU Affero GPL v3
 * @license   MONARC is licensed under GNU Affero General Public License version 3
 */

namespace Monarc\Core\Service;

use Monarc\Core\Entity\ReassessmentTrigger;
use Monarc\Core\Entity\UserSuperClass;
use Monarc\Core\Exception\Exception;
use Monarc\Core\InputFormatter\FormattedInputParams;
use Monarc\Core\Table\ReassessmentTriggerTable;

class ReassessmentTriggerService
{
    private UserSuperClass $connectedUser;

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
        $triggerTypeTranslations = $this->normalizeTranslations($data, 'triggerType', false);
        if ($triggerTypeTranslations === []) {
            throw new Exception('Reassessment trigger type is required.', 412);
        }

        $descriptionTranslations = $this->normalizeTranslations($data, 'description', true);

        $reassessmentTrigger = (new ReassessmentTrigger())
            ->setTriggerType($this->encodeTranslations($triggerTypeTranslations))
            ->setDescription($this->encodeTranslations($descriptionTranslations))
            ->setIsActive((bool)($data['isActive'] ?? true))
            ->setCreator($this->connectedUser->getEmail());

        $this->applyCreatePosition($reassessmentTrigger, $data);
        $this->reassessmentTriggerTable->save($reassessmentTrigger);

        return $reassessmentTrigger;
    }

    public function update(int $id, array $data): ReassessmentTrigger
    {
        $reassessmentTrigger = $this->get($id);

        if (array_key_exists('triggerTypes', $data) || array_key_exists('triggerType', $data)) {
            $triggerTypeTranslations = $this->normalizeTranslations($data, 'triggerType', false);
            if ($triggerTypeTranslations === []) {
                throw new Exception('Reassessment trigger type is required.', 412);
            }
            $reassessmentTrigger->setTriggerType($this->encodeTranslations($triggerTypeTranslations));
        }

        if (array_key_exists('descriptions', $data) || array_key_exists('description', $data)) {
            $reassessmentTrigger->setDescription(
                $this->encodeTranslations($this->normalizeTranslations($data, 'description', true))
            );
        }

        if (array_key_exists('isActive', $data)) {
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
                'isActive' => $reassessmentTrigger->isActive(),
                'position' => $reassessmentTrigger->getPosition(),
            ];
        }

        return $selectionData;
    }

    public function getDisplayTriggerType(ReassessmentTrigger $reassessmentTrigger, ?string $languageCode = null): string
    {
        return $this->resolveDisplayValue(
            $reassessmentTrigger->getTriggerTypeTranslations(),
            $reassessmentTrigger->getTriggerType() ?? '',
            $languageCode
        );
    }

    public function getDisplayDescription(ReassessmentTrigger $reassessmentTrigger, ?string $languageCode = null): string
    {
        return $this->resolveDisplayValue(
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
        return $this->getTranslationsWithFallback(
            $reassessmentTrigger->getTriggerTypeTranslations(),
            $reassessmentTrigger->getTriggerType() ?? ''
        );
    }

    /**
     * @return array<string, string>
     */
    public function getDescriptions(ReassessmentTrigger $reassessmentTrigger): array
    {
        return $this->getTranslationsWithFallback(
            $reassessmentTrigger->getDescriptionTranslations(),
            $reassessmentTrigger->getDescription()
        );
    }

    /**
     * @param array<string, mixed> $data
     * @return array<string, string>
     */
    private function normalizeTranslations(array $data, string $fieldName, bool $allowEmpty): array
    {
        $translations = [];
        $multiValueFieldName = $fieldName . 's';
        if (isset($data[$multiValueFieldName]) && is_array($data[$multiValueFieldName])) {
            foreach ($data[$multiValueFieldName] as $languageCode => $value) {
                $translations[(string)$languageCode] = trim((string)$value);
            }
        }

        if ($translations === [] && array_key_exists($fieldName, $data)) {
            $translations[$this->getCurrentLanguageCode()] = trim((string)$data[$fieldName]);
        }

        if (!$allowEmpty) {
            $translations = array_filter($translations, static fn (string $value): bool => $value !== '');
        }

        return $translations;
    }

    /**
     * @param array<string, string> $translations
     */
    private function encodeTranslations(array $translations): string
    {
        $normalizedTranslations = [];
        foreach ($translations as $languageCode => $value) {
            $normalizedTranslations[(string)$languageCode] = trim((string)$value);
        }

        return json_encode($normalizedTranslations, JSON_THROW_ON_ERROR);
    }

    /**
     * @param array<string, string> $translations
     */
    private function resolveDisplayValue(array $translations, string $fallbackValue, ?string $languageCode = null): string
    {
        $languageCode ??= $this->getCurrentLanguageCode();
        if (isset($translations[$languageCode]) && $translations[$languageCode] !== '') {
            return $translations[$languageCode];
        }

        $defaultLanguageCode = $this->configService->getLanguageCodes()[
            $this->configService->getConfigOption('defaultLanguageIndex', 1)
        ] ?? null;
        if ($defaultLanguageCode !== null && isset($translations[$defaultLanguageCode])) {
            return $translations[$defaultLanguageCode];
        }

        foreach ($translations as $translation) {
            if ($translation !== '') {
                return $translation;
            }
        }

        return $fallbackValue;
    }

    /**
     * @param array<string, string> $translations
     * @return array<string, string>
     */
    private function getTranslationsWithFallback(array $translations, string $fallbackValue): array
    {
        $localizedValues = [];
        foreach ($this->getSupportedLanguageCodes() as $languageCode) {
            $localizedValues[$languageCode] = $this->resolveDisplayValue($translations, $fallbackValue, $languageCode);
        }

        return $localizedValues;
    }

    private function getCurrentLanguageCode(): string
    {
        return $this->configService->getLanguageCodes()[$this->connectedUser->getLanguage()]
            ?? $this->configService->getLanguageCodes()[$this->configService
                ->getConfigOption('defaultLanguageIndex', 1)]
            ?? 'en';
    }

    /**
     * @return string[]
     */
    private function getSupportedLanguageCodes(): array
    {
        $languageCodes = $this->configService->getActiveLanguageCodes();

        return $languageCodes !== [] ? $languageCodes : $this->configService->getLanguageCodes();
    }

    private function applyCreatePosition(ReassessmentTrigger $reassessmentTrigger, array $data): void
    {
        $position = isset($data['position']) ? (int)$data['position'] : 0;
        $maxPosition = $this->reassessmentTriggerTable->findMaxPosition([]);

        if ($position <= 0 || $position > $maxPosition + 1) {
            $reassessmentTrigger->setPosition($maxPosition + 1);

            return;
        }

        $this->reassessmentTriggerTable->incrementPositions(
            $position,
            -1,
            1,
            [],
            $this->connectedUser->getEmail()
        );
        $reassessmentTrigger->setPosition($position);
    }

    private function applyUpdatedPosition(ReassessmentTrigger $reassessmentTrigger, int $newPosition): void
    {
        $oldPosition = $reassessmentTrigger->getPosition();
        $maxPosition = $this->reassessmentTriggerTable->findMaxPosition([]);
        $newPosition = max(1, min($newPosition, $maxPosition));

        if ($newPosition < $oldPosition) {
            $this->reassessmentTriggerTable->incrementPositions(
                $newPosition,
                $oldPosition - 1,
                1,
                [],
                $this->connectedUser->getEmail()
            );
        } elseif ($newPosition > $oldPosition) {
            $this->reassessmentTriggerTable->incrementPositions(
                $oldPosition + 1,
                $newPosition,
                -1,
                [],
                $this->connectedUser->getEmail()
            );
        }

        $reassessmentTrigger->setPosition($newPosition);
    }
}
