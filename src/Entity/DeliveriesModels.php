<?php declare(strict_types=1);
/**
 * @link      https://github.com/monarc-project for the canonical source repository
 * @copyright Copyright (c) 2016-2026 Luxembourg House of Cybersecurity LHC.lu - Licensed under GNU Affero GPL v3
 * @license   MONARC is licensed under GNU Affero General Public License version 3
 */

namespace Monarc\Core\Entity;

use Doctrine\ORM\Mapping as ORM;
use Monarc\Core\Entity\Traits\CreateEntityTrait;
use Monarc\Core\Entity\Traits\DescriptionsEntityTrait;
use Monarc\Core\Entity\Traits\UpdateEntityTrait;

#[ORM\Table(name: 'deliveries_models')]
#[ORM\Entity]
#[ORM\HasLifecycleCallbacks]
class DeliveriesModels
{
    use CreateEntityTrait;
    use UpdateEntityTrait;

    use DescriptionsEntityTrait;

    public const MODEL_CONTEXT_VALIDATION = 1; // Document model for Context validation
    public const MODEL_ASSETS_AND_MODELS_VALIDATION = 2; // Document model for Assets and models validation
    public const MODEL_RISK_ANALYSIS = 3; // Document model for Risk analysis
    public const MODEL_IMPLEMENTATION_PLAN = 4; // Document model for implementation plan
    public const MODEL_STATEMENT_OF_APPLICABILITY = 5; // Document model for Statement of applicability
    public const MODEL_RECORD_OF_PROCESSING_ACTIVITIES = 6; // Document model for Record of processing activities
    public const MODEL_ALL_RECORD_OF_PROCESSING_ACTIVITIES = 7; // Document model for all Rec. of processing activities

    /**
     * @var integer
     */
    #[ORM\Column(name: 'id', type: 'integer', nullable: false)]
    #[ORM\Id]
    #[ORM\GeneratedValue(strategy: 'IDENTITY')]
    protected int $id;

    /**
     * @var integer
     */
    #[ORM\Column(name: 'category', type: 'smallint', length: 4, options: ['default' => 0])]
    protected int $category = 0;

    /**
     * @var string
     */
    #[ORM\Column(name: 'path1', type: 'string', length: 255, nullable: true)]
    protected ?string $path1 = null;

    /**
     * @var string
     */
    #[ORM\Column(name: 'path2', type: 'string', length: 255, nullable: true)]
    protected ?string $path2 = null;

    /**
     * @var string
     */
    #[ORM\Column(name: 'path3', type: 'string', length: 255, nullable: true)]
    protected ?string $path3 = null;

    /**
     * @var string
     */
    #[ORM\Column(name: 'path4', type: 'string', length: 255, nullable: true)]
    protected ?string $path4 = null;

    /**
     * @var boolean
     */
    #[ORM\Column(name: 'editable', type: 'boolean', options: ['default' => true])]
    protected bool $editable = true;

    public function getPath(int $languageIndex): ?string
    {
        if (!\in_array($languageIndex, range(1, 4), true)) {
            return '';
        }

        return $this->{'path' . $languageIndex};
    }

    public function setPath(array $paths): self
    {
        foreach (range(1, 4) as $index) {
            $key = 'path' . $index;
            if (array_key_exists($key, $paths)) {
                $this->{$key} = $paths[$key];
            }
        }

        return $this;
    }

    public function getPaths(): array
    {
        return [
            'path1' => $this->path1,
            'path2' => $this->path2,
            'path3' => $this->path3,
            'path4' => $this->path4,
        ];
    }

    public function getId(): int
    {
        return $this->id;
    }

    public function getCategory(): int
    {
        return $this->category;
    }

    public function setCategory(int $category): self
    {
        $this->category = $category;

        return $this;
    }

    public function isEditable(): bool
    {
        return $this->editable;
    }

    public function setEditable(bool $editable): self
    {
        $this->editable = $editable;

        return $this;
    }
}
