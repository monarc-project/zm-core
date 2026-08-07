<?php declare(strict_types=1);
/**
 * @link      https://github.com/monarc-project for the canonical source repository
 * @copyright Copyright (c) 2016-2026 Luxembourg House of Cybersecurity LHC.lu - Licensed under GNU Affero GPL v3
 * @license   MONARC is licensed under GNU Affero General Public License version 3
 */

namespace Monarc\Core\Service;

use Monarc\Core\Entity\RiskSource;
use Monarc\Core\Entity\UserSuperClass;
use Monarc\Core\InputFormatter\FormattedInputParams;
use Monarc\Core\Table\RiskSourceTable;
use Monarc\Core\Traits\TranslationNormalizationTrait;
use Monarc\Core\Traits\TranslationResolverTrait;

class RiskSourceService
{
    use TranslationNormalizationTrait;
    use TranslationResolverTrait;

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
        $riskSource = (new RiskSource())
            ->setLabelTranslations(
                $this->normalizeTranslations($data['labels'] ?? [], $this->getSupportedLanguageCodes())
            )
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
        if (!empty($data['labels'])) {
            $riskSource->setLabelTranslations(
                $this->normalizeTranslations($data['labels'], $this->getSupportedLanguageCodes())
            );
        }

        $riskSource->setUpdater($this->connectedUser->getEmail());

        $this->riskSourceTable->save($riskSource);

        return $riskSource;
    }

    public function delete(int $id): void
    {
        $riskSource = $this->get($id);

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
            $labelsById[$riskSource->getId()] = $this->resolveTranslation(
                $riskSource->getLabelTranslations(),
                $riskSource->getLabel(),
                $this->getCurrentLanguageCode()
            );
        }

        return $labelsById;
    }

    public function getDisplayLabel(RiskSource $riskSource): string
    {
        return $this->resolveTranslation(
            $riskSource->getLabelTranslations(),
            $riskSource->getLabel(),
            $this->getCurrentLanguageCode()
        );
    }

    public function getLabels(RiskSource $riskSource): array
    {
        return $this->resolveTranslations($riskSource->getLabelTranslations(), $riskSource->getLabel());
    }
}
