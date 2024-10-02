<?php declare(strict_types=1);
/**
 * @link      https://github.com/monarc-project for the canonical source repository
 * @copyright Copyright (c) 2016-2023 Luxembourg House of Cybersecurity LHC.lu - Licensed under GNU Affero GPL v3
 * @license   MONARC is licensed under GNU Affero General Public License version 3
 */

namespace Monarc\Core\Entity\Traits;

trait NamesEntityTrait
{
    /**
     * @var string
     *
     * @ORM\Column(name="name1", type="string", length=255, nullable=true)
     */
    protected $name1;

    /**
     * @var string
     *
     * @ORM\Column(name="name2", type="string", length=255, nullable=true)
     */
    protected $name2;

    /**
     * @var string
     *
     * @ORM\Column(name="name3", type="string", length=255, nullable=true)
     */
    protected $name3;

    /**
     * @var string
     *
     * @ORM\Column(name="name4", type="string", length=255, nullable=true)
     */
    protected $name4;

    public function getName(int $languageIndex): string
    {
        if (!\in_array($languageIndex, range(1, 4), true)) {
            return '';
        }

        return (string)$this->{'name' . $languageIndex};
    }

    public function setNames(array $names): self
    {
        foreach (range(1, 4) as $index) {
            $key = 'name' . $index;
            if (isset($names[$key])) {
                $this->{$key} = $names[$key];
            }
        }

        return $this;
    }

    public function getNames(): array
    {
        return [
            'name1' => $this->name1,
            'name2' => $this->name2,
            'name3' => $this->name3,
            'name4' => $this->name4,
        ];
    }
}
