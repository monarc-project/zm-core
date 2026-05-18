<?php declare(strict_types=1);
/**
 * @link      https://github.com/monarc-project for the canonical source repository
 * @copyright Copyright (c) 2016-2026 Luxembourg House of Cybersecurity LHC.lu - Licensed under GNU Affero GPL v3
 * @license   MONARC is licensed under GNU Affero General Public License version 3
 */

namespace Monarc\Core\Entity;

use Doctrine\ORM\Mapping as ORM;
use Monarc\Core\Entity\Interfaces\PositionedEntityInterface;
use Monarc\Core\Entity\Interfaces\PropertyStateEntityInterface;
use Monarc\Core\Entity\Traits\CreateEntityTrait;
use Monarc\Core\Entity\Traits\PropertyStateEntityTrait;
use Monarc\Core\Entity\Traits\UpdateEntityTrait;
use Monarc\Core\Traits\TranslationNormalizationTrait;

/**
 * @ORM\Table(
 *     name="anr_reassessment_triggers",
 *     indexes={
 *         @ORM\Index(name="anr_reassessment_triggers_trigger_type_indx", columns={"trigger_type"}),
 *         @ORM\Index(name="anr_reassessment_triggers_position_indx", columns={"position"}),
 *         @ORM\Index(name="anr_reassessment_triggers_is_active_indx", columns={"is_active"})
 *     }
 * )
 * @ORM\MappedSuperclass
 * @ORM\HasLifecycleCallbacks()
 */
class ReassessmentTriggerSuperClass implements PositionedEntityInterface, PropertyStateEntityInterface
{
    use PropertyStateEntityTrait;
    use CreateEntityTrait;
    use UpdateEntityTrait;
    use TranslationNormalizationTrait;

    /**
     * @ORM\Column(name="id", type="integer", nullable=false, options={"unsigned": true})
     * @ORM\Id
     * @ORM\GeneratedValue(strategy="IDENTITY")
     */
    protected int $id;

    /** @ORM\Column(name="trigger_type", type="text", nullable=true) */
    protected ?string $triggerType = null;

    /** @ORM\Column(name="description", type="text", nullable=false) */
    protected string $description;

    /** @ORM\Column(name="monitoring_approach", type="text", nullable=true) */
    protected ?string $monitoringApproach = null;

    /** @ORM\Column(name="is_active", type="boolean", nullable=false, options={"default": 1}) */
    protected bool $isActive = true;

    /** @ORM\Column(name="position", type="integer", nullable=false, options={"unsigned": true, "default": 0}) */
    protected int $position = 0;

    public function getId(): int
    {
        return $this->id;
    }

    public function getTriggerType(): ?string
    {
        return $this->triggerType;
    }

    public function setTriggerType(?string $triggerType): self
    {
        $this->triggerType = $triggerType;

        return $this;
    }

    public function getDescription(): string
    {
        return $this->description;
    }

    public function setDescription(string $description): self
    {
        $this->description = $description;

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

    public function getMonitoringApproach(): ?string
    {
        return $this->monitoringApproach;
    }

    public function setMonitoringApproach(?string $monitoringApproach): self
    {
        $this->monitoringApproach = $monitoringApproach;

        return $this;
    }

    public function getPosition(): int
    {
        return $this->position;
    }

    public function setPosition(int $position): self
    {
        $this->position = $position;

        return $this;
    }

    public function getImplicitPositionRelationsValues(): array
    {
        return [];
    }

    /**
     * @return array<string, string>
     */
    public function getTriggerTypeTranslations(): array
    {
        return $this->decodeTranslations($this->triggerType);
    }

    /**
     * @param array<string, string> $triggerTypeTranslations
     */
    public function setTriggerTypeTranslations(array $triggerTypeTranslations): self
    {
        $this->triggerType = $this->encodeTranslations($triggerTypeTranslations);

        return $this;
    }

    /**
     * @return array<string, string>
     */
    public function getDescriptionTranslations(): array
    {
        return $this->decodeTranslations($this->description);
    }

    /**
     * @param array<string, string> $descriptionTranslations
     */
    public function setDescriptionTranslations(array $descriptionTranslations): self
    {
        $this->description = $this->encodeTranslations($descriptionTranslations);

        return $this;
    }

    /**
     * @return array<string, string>
     */
    public function getMonitoringApproachTranslations(): array
    {
        return $this->decodeTranslations($this->monitoringApproach);
    }

    /**
     * @param array<string, string> $monitoringApproachTranslations
     */
    public function setMonitoringApproachTranslations(array $monitoringApproachTranslations): self
    {
        $this->monitoringApproach = $monitoringApproachTranslations === []
            ? null
            : $this->encodeTranslations($monitoringApproachTranslations);

        return $this;
    }

    /**
     * @return array<string, string>
     */
    private function decodeTranslations(?string $value): array
    {
        if ($value === null || $value === '') {
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
