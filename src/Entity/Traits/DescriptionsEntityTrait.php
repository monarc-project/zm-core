<?php declare(strict_types=1);
/**
 * @link      https://github.com/monarc-project for the canonical source repository
 * @copyright Copyright (c) 2016-2023 Luxembourg House of Cybersecurity LHC.lu - Licensed under GNU Affero GPL v3
 * @license   MONARC is licensed under GNU Affero General Public License version 3
 */

namespace Monarc\Core\Entity\Traits;

trait DescriptionsEntityTrait
{
    /**
     * @var string
     *
     * @ORM\Column(name="description1", type="text", nullable=true)
     */
    protected $description1;

    /**
     * @var string
     *
     * @ORM\Column(name="description2", type="text", nullable=true)
     */
    protected $description2;

    /**
     * @var string
     *
     * @ORM\Column(name="description3", type="text", nullable=true)
     */
    protected $description3;

    /**
     * @var string
     *
     * @ORM\Column(name="description4", type="text", nullable=true)
     */
    protected $description4;

    public function getDescription(int $languageIndex): string
    {
        if (!\in_array($languageIndex, range(1, 4), true)) {
            return '';
        }

        return (string)$this->{'description' . $languageIndex};
    }

    public function setDescriptions(array $descriptions): self
    {
        foreach (range(1, 4) as $index) {
            $key = 'description' . $index;
            if (isset($descriptions[$key])) {
                $this->{$key} = $descriptions[$key];
            }
        }

        return $this;
    }

    public function getDescriptions(): array
    {
        return [
            'description1' => $this->description1,
            'description2' => $this->description2,
            'description3' => $this->description3,
            'description4' => $this->description4,
        ];
    }
}
