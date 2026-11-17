<?php declare(strict_types=1);
/**
 * @link      https://github.com/monarc-project for the canonical source repository
 * @copyright Copyright (c) 2016-2026 Luxembourg House of Cybersecurity LHC.lu - Licensed under GNU Affero GPL v3
 * @license   MONARC is licensed under GNU Affero General Public License version 3
 */

namespace Monarc\Core\Entity;

use Doctrine\ORM\Mapping as ORM;
use InvalidArgumentException;

/**
 * @ORM\Table(name="scenario_catalog_template_package_links", uniqueConstraints={
 *     @ORM\UniqueConstraint(name="scenario_package_source", columns={"template_id", "source_type", "source_identity"}),
 *     @ORM\UniqueConstraint(name="scenario_package_position", columns={"template_id", "position"})
 * })
 * @ORM\Entity
 */
class ScenarioTemplatePackageLink
{
    public const SOURCE_TYPE_ASSET = 'asset';
    public const SOURCE_TYPE_ASSET_TYPE = 'asset-type';
    public const SOURCE_TYPE_THREAT = 'threat';
    public const SOURCE_TYPE_VULNERABILITY = 'vulnerability';
    public const SOURCE_TYPE_REFERENTIAL = 'referential';
    public const SOURCE_TYPE_CONTROL = 'control';
    public const SOURCE_TYPE_RISK_SOURCE = 'risk-source';

    public const SOURCE_TYPES = [
        self::SOURCE_TYPE_ASSET,
        self::SOURCE_TYPE_ASSET_TYPE,
        self::SOURCE_TYPE_THREAT,
        self::SOURCE_TYPE_VULNERABILITY,
        self::SOURCE_TYPE_REFERENTIAL,
        self::SOURCE_TYPE_CONTROL,
        self::SOURCE_TYPE_RISK_SOURCE,
    ];

    // Controls are included through a selected referential, never authored independently.
    public const AUTHORABLE_SOURCE_TYPES = [
        self::SOURCE_TYPE_ASSET,
        self::SOURCE_TYPE_ASSET_TYPE,
        self::SOURCE_TYPE_THREAT,
        self::SOURCE_TYPE_VULNERABILITY,
        self::SOURCE_TYPE_REFERENTIAL,
        self::SOURCE_TYPE_RISK_SOURCE,
    ];

    /**
     * @ORM\Id
     * @ORM\GeneratedValue(strategy="IDENTITY")
     * @ORM\Column(name="id", type="bigint", options={"unsigned": true})
     */
    protected ?string $id = null;

    /** @ORM\Column(name="template_id", type="integer", options={"unsigned": true}) */
    protected int $templateId;

    /** @ORM\Column(name="source_type", type="string", length=30) */
    protected string $sourceType;

    /** @ORM\Column(name="source_identity", type="string", length=36) */
    protected string $sourceIdentity;

    /** @ORM\Column(name="guidance", type="text", nullable=true) */
    protected ?string $guidance = null;

    /** @ORM\Column(name="position", type="integer", options={"unsigned": true}) */
    protected int $position;

    public function getId(): ?string
    {
        return $this->id;
    }

    public function getTemplateId(): int
    {
        return $this->templateId;
    }

    public function setTemplateId(int $templateId): self
    {
        $this->templateId = $templateId;

        return $this;
    }

    public function getSourceType(): string
    {
        return $this->sourceType;
    }

    public function setSourceType(string $sourceType): self
    {
        if (!in_array($sourceType, self::SOURCE_TYPES, true)) {
            throw new InvalidArgumentException('Unsupported template package source type.');
        }
        $this->sourceType = $sourceType;

        return $this;
    }

    public function getSourceIdentity(): string
    {
        return $this->sourceIdentity;
    }

    public function setSourceIdentity(string $sourceIdentity): self
    {
        $this->sourceIdentity = $sourceIdentity;

        return $this;
    }

    public function getGuidance(): ?string
    {
        return $this->guidance;
    }

    public function setGuidance(?string $guidance): self
    {
        $this->guidance = $guidance;

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
}
