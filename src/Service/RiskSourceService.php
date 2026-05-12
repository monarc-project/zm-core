<?php declare(strict_types=1);
/**
 * @link      https://github.com/monarc-project for the canonical source repository
 * @copyright Copyright (c) 2016-2026 Luxembourg House of Cybersecurity LHC.lu - Licensed under GNU Affero GPL v3
 * @license   MONARC is licensed under GNU Affero General Public License version 3
 */

namespace Monarc\Core\Service;

use Monarc\Core\Entity\RiskSource;
use Monarc\Core\Entity\UserSuperClass;
use Monarc\Core\Exception\Exception;
use Monarc\Core\InputFormatter\FormattedInputParams;
use Monarc\Core\Table\RiskSourceTable;

class RiskSourceService
{
    private UserSuperClass $connectedUser;

    public function __construct(
        private RiskSourceTable $riskSourceTable,
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

        $riskSource = (new RiskSource())
            ->setLabelTranslations($labels)
            ->setIsDefault(false)
            ->setIsActive((bool)($data['isActive'] ?? true))
            ->setCreator($this->connectedUser->getEmail());

        $this->riskSourceTable->save($riskSource);

        return $riskSource;
    }

    public function update(int $id, array $data): RiskSource
    {
        $riskSource = $this->get($id);
        if (isset($data['isActive'])) {
            $riskSource->setIsActive((bool)$data['isActive']);
        }
        if (!empty($data['labels']) || isset($data['label'])) {
            $labels = $this->normalizeLabels($data);
            $riskSource->setLabelTranslations($labels);
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

        $this->riskSourceTable->remove($riskSource);
    }

    /**
     * @param RiskSource[] $riskSources
     * @return array<int, string>
     */
    public function getDisplayLabelsByRiskSourceId(array $riskSources): array
    {
        $labelsById = [];
        foreach ($riskSources as $riskSource) {
            $labelsById[$riskSource->getId()] = $this->resolveDisplayValue(
                $riskSource->getLabelTranslations(),
                $riskSource->getLabel(),
                $this->getCurrentLanguageCode()
            );
        }

        return $labelsById;
    }

    public function getDisplayLabel(RiskSource $riskSource): string
    {
        return $this->resolveDisplayValue(
            $riskSource->getLabelTranslations(),
            $riskSource->getLabel(),
            $this->getCurrentLanguageCode()
        );
    }

    public function getLabels(RiskSource $riskSource): array
    {
        return $this->getLabelsWithFallback($riskSource->getLabelTranslations(), $riskSource->getLabel());
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

    private function resolveDisplayValue(array $labels, string $fallbackLabel, ?string $languageCode = null): string
    {
        $languageCode ??= $this->getCurrentLanguageCode();
        if (isset($labels[$languageCode]) && $labels[$languageCode] !== '') {
            return $labels[$languageCode];
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

    /**
     * @param array<string, string> $labels
     * @return array<string, string>
     */
    private function getLabelsWithFallback(array $labels, string $fallbackLabel): array
    {
        $localizedLabels = [];
        foreach ($this->getSupportedLanguageCodes() as $languageCode) {
            $localizedLabels[$languageCode] = $this->resolveDisplayValue($labels, $fallbackLabel, $languageCode);
        }

        return $localizedLabels;
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
