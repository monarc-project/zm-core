<?php declare(strict_types=1);
/**
 * @link      https://github.com/monarc-project for the canonical source repository
 * @copyright Copyright (c) 2016-2026 Luxembourg House of Cybersecurity LHC.lu - Licensed under GNU Affero GPL v3
 * @license   MONARC is licensed under GNU Affero General Public License version 3
 */

namespace Monarc\Core\Service;

use Monarc\Core\Entity\RiskSource;
use Monarc\Core\Entity\Translation;
use Monarc\Core\Entity\TranslationSuperClass;
use Monarc\Core\Entity\UserSuperClass;
use Monarc\Core\Exception\Exception;
use Monarc\Core\InputFormatter\FormattedInputParams;
use Monarc\Core\Table\RiskSourceTable;
use Monarc\Core\Table\TranslationTable;
use Ramsey\Uuid\Uuid;

class RiskSourceService
{
    private UserSuperClass $connectedUser;

    public function __construct(
        private RiskSourceTable $riskSourceTable,
        private TranslationTable $translationTable,
        private ConfigService $configService,
        ConnectedUserService $connectedUserService
    ) {
        $this->connectedUser = $connectedUserService->getConnectedUser();
    }

    /**
     * @return RiskSource[]
     */
    public function getList(FormattedInputParams $params): array
    {
        return $this->riskSourceTable->findByParams($params);
    }

    public function getCount(FormattedInputParams $params): int
    {
        return $this->riskSourceTable->countByParams($params, 'id');
    }

    public function get(int $id): RiskSource
    {
        /** @var RiskSource $riskSource */
        $riskSource = $this->riskSourceTable->findById($id);

        return $riskSource;
    }

    public function create(array $data): RiskSource
    {
        $labels = $this->normalizeLabels($data);
        $labelTranslationKey = Uuid::uuid4()->toString();

        $riskSource = (new RiskSource())
            ->setLabelTranslationKey($labelTranslationKey)
            ->setLabel($this->getPrimaryLabel($labels, trim((string)$data['label'])))
            ->setIsDefault(false)
            ->setIsActive((bool)($data['isActive'] ?? true))
            ->setCreator($this->connectedUser->getEmail());

        foreach ($labels as $languageCode => $label) {
            $this->translationTable->save(
                (new Translation())
                    ->setType(TranslationSuperClass::RISK_SOURCE)
                    ->setKey($labelTranslationKey)
                    ->setLang($languageCode)
                    ->setValue($label)
                    ->setCreator($this->connectedUser->getEmail()),
                false
            );
        }

        $this->riskSourceTable->save($riskSource);

        return $riskSource;
    }

    public function update(int $id, array $data): RiskSource
    {
        $riskSource = $this->get($id);

        if (isset($data['label'])) {
            $riskSource->setLabel(trim((string)$data['label']));
        }
        if (isset($data['isActive'])) {
            $riskSource->setIsActive((bool)$data['isActive']);
        }
        if (!empty($data['labels']) || isset($data['label'])) {
            $labels = $this->normalizeLabels($data);
            if ($riskSource->getLabelTranslationKey() === '') {
                $riskSource->setLabelTranslationKey(Uuid::uuid4()->toString());
            }

            foreach ($labels as $languageCode => $label) {
                $translation = $this->translationTable->findByTypeKeyAndLanguage(
                    TranslationSuperClass::RISK_SOURCE,
                    $riskSource->getLabelTranslationKey(),
                    $languageCode
                );
                if ($translation === null) {
                    $translation = (new Translation())
                        ->setType(TranslationSuperClass::RISK_SOURCE)
                        ->setKey($riskSource->getLabelTranslationKey())
                        ->setLang($languageCode);
                }
                $translation->setValue($label)->setUpdater($this->connectedUser->getEmail());
                $this->translationTable->save($translation, false);
            }

            $riskSource->setLabel($this->getPrimaryLabel($labels, $riskSource->getLabel()));
        }

        $riskSource->setUpdater($this->connectedUser->getEmail());

        $this->riskSourceTable->save($riskSource);

        return $riskSource;
    }

    public function delete(int $id): void
    {
        $riskSource = $this->get($id);

        if ($riskSource->isDefault()) {
            throw new Exception('Default risk sources cannot be removed.', 412);
        }
        if ($this->riskSourceTable->isUsedInRisks($riskSource)) {
            throw new Exception('Risk source linked to instance risks cannot be removed.', 412);
        }

        if ($riskSource->getLabelTranslationKey() !== '') {
            $this->translationTable->deleteListByKeys([$riskSource->getLabelTranslationKey()]);
        }

        $this->riskSourceTable->remove($riskSource);
    }

    /**
     * @param RiskSource[] $riskSources
     * @return array<int, string>
     */
    public function getDisplayLabelsByRiskSourceId(array $riskSources): array
    {
        $labelsById = [];
        $keys = [];
        foreach ($riskSources as $riskSource) {
            if ($riskSource->getLabelTranslationKey() !== '') {
                $keys[] = $riskSource->getLabelTranslationKey();
            }
        }

        $translations = $this->translationTable->findByTypeAndLanguageIndexedByKeys(
            TranslationSuperClass::RISK_SOURCE,
            $keys,
            $this->getCurrentLanguageCode()
        );

        foreach ($riskSources as $riskSource) {
            $translationKey = $riskSource->getLabelTranslationKey();
            $labelsById[$riskSource->getId()] = $translationKey !== ''
                ? ($translations[$translationKey]?->getValue() ?? $riskSource->getLabel())
                : $riskSource->getLabel();
        }

        return $labelsById;
    }

    public function getDisplayLabel(RiskSource $riskSource): string
    {
        return $this->getDisplayLabelsByRiskSourceId([$riskSource])[$riskSource->getId()] ?? $riskSource->getLabel();
    }

    public function getLabels(RiskSource $riskSource): array
    {
        $labels = [];
        foreach ($this->getSupportedLanguageCodes() as $languageCode) {
            $labels[$languageCode] = $riskSource->getLabel();
        }

        if ($riskSource->getLabelTranslationKey() === '') {
            return $labels;
        }

        foreach ($this->translationTable->findByTypeAndKey(
            TranslationSuperClass::RISK_SOURCE,
            $riskSource->getLabelTranslationKey()
        ) as $translation) {
            $labels[$translation->getLang()] = $translation->getValue();
        }

        return $labels;
    }

    private function normalizeLabels(array $data): array
    {
        $labels = [];
        if (isset($data['labels']) && is_array($data['labels'])) {
            foreach ($data['labels'] as $languageCode => $label) {
                $trimmedLabel = trim((string)$label);
                if ($trimmedLabel !== '') {
                    $labels[(string)$languageCode] = $trimmedLabel;
                }
            }
        }

        if ($labels === [] && isset($data['label'])) {
            $labels[$this->getCurrentLanguageCode()] = trim((string)$data['label']);
        }

        return $labels;
    }

    private function getPrimaryLabel(array $labels, string $fallbackLabel): string
    {
        $currentLanguageCode = $this->getCurrentLanguageCode();
        if (isset($labels[$currentLanguageCode])) {
            return $labels[$currentLanguageCode];
        }

        $defaultLanguageCode = $this->configService
            ->getLanguageCodes()[$this->configService->getConfigOption('defaultLanguageIndex', 1)] ?? null;
        if ($defaultLanguageCode !== null && isset($labels[$defaultLanguageCode])) {
            return $labels[$defaultLanguageCode];
        }

        if ($labels !== []) {
            return (string)reset($labels);
        }

        return $fallbackLabel;
    }

    private function getCurrentLanguageCode(): string
    {
        return $this->configService->getLanguageCodes()[$this->connectedUser->getLanguage()]
            ?? $this->configService->getLanguageCodes()[$this->configService
                ->getConfigOption('defaultLanguageIndex', 1)]
            ?? 'en';
    }

    private function getSupportedLanguageCodes(): array
    {
        $languageCodes = $this->configService->getActiveLanguageCodes();

        return $languageCodes !== [] ? $languageCodes : $this->configService->getLanguageCodes();
    }
}
