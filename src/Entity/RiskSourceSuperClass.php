<?php declare(strict_types=1);
/**
 * @link      https://github.com/monarc-project for the canonical source repository
 * @copyright Copyright (c) 2016-2026 Luxembourg House of Cybersecurity LHC.lu - Licensed under GNU Affero GPL v3
 * @license   MONARC is licensed under GNU Affero General Public License version 3
 */

namespace Monarc\Core\Entity;

use Doctrine\ORM\Mapping as ORM;
use Monarc\Core\Entity\Traits\CreateEntityTrait;
use Monarc\Core\Entity\Traits\UpdateEntityTrait;
use Monarc\Core\Traits\TranslationNormalizationTrait;

/**
 * @ORM\Table(
 *     name="risk_sources",
 *     indexes={
 *         @ORM\Index(name="risk_sources_is_active_indx", columns={"is_active"})
 *     }
 * )
 * @ORM\MappedSuperclass
 * @ORM\HasLifecycleCallbacks()
 */
class RiskSourceSuperClass
{
    use CreateEntityTrait;
    use UpdateEntityTrait;
    use TranslationNormalizationTrait;

    /**
     * @ORM\Column(name="id", type="integer", nullable=false, options={"unsigned": true})
     * @ORM\Id
     * @ORM\GeneratedValue(strategy="IDENTITY")
     */
    protected int $id;

    /** @ORM\Column(name="label", type="text", nullable=false) */
    protected string $label;

    /** @ORM\Column(name="is_default", type="boolean", nullable=false, options={"default": 0}) */
    protected bool $isDefault = false;

    /** @ORM\Column(name="is_active", type="boolean", nullable=false, options={"default": 1}) */
    protected bool $isActive = true;

    public function getId(): int
    {
        return $this->id;
    }

    public function getLabel(): string
    {
        return $this->label;
    }

    public function setLabel(string $label): self
    {
        $this->label = $label;

        return $this;
    }

    public function isDefault(): bool
    {
        return $this->isDefault;
    }

    public function setIsDefault(bool $isDefault): self
    {
        $this->isDefault = $isDefault;

        return $this;
    }

    public function isActive(): bool
    {
        return $this->isActive;
    }

    public function setIsActive(bool $isActive): self
    {
        $this->isActive = $isActive;

        return $this;
    }

    /**
     * @return array<string, string>
     */
    public function getLabelTranslations(): array
    {
        return $this->decodeTranslations($this->label);
    }

    /**
     * @param array<string, string> $labelTranslations
     */
    public function setLabelTranslations(array $labelTranslations): self
    {
        $this->label = $this->encodeTranslations($labelTranslations);

        return $this;
    }

    /**
     * @return array<string, string>
     */
    private function decodeTranslations(string $value): array
    {
        if ($value === '') {
            return [];
        }

        $decoded = json_decode($value, true);
        return $this->normalizeTranslations($decoded);
    }

    /**
     * @param array<string, string> $translations
     */
    private function encodeTranslations(array $translations): string
    {
        return json_encode($this->normalizeTranslations($translations), JSON_THROW_ON_ERROR);
    }
}
