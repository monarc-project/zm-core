<?php declare(strict_types=1);
/**
 * @link      https://github.com/monarc-project for the canonical source repository
 * @copyright Copyright (c) 2016-2024 Luxembourg House of Cybersecurity LHC.lu - Licensed under GNU Affero GPL v3
 * @license   MONARC is licensed under GNU Affero General Public License version 3
 */

namespace Monarc\Core\Entity;

use Doctrine\ORM\Mapping as ORM;
use Monarc\Core\Entity\Traits\CreateEntityTrait;
use Monarc\Core\Entity\Traits\UpdateEntityTrait;

/**
 * @ORM\Table(name="soa_scale_comments", indexes={
 *      @ORM\Index(name="anr", columns={"anr_id"})
 * })
 * @ORM\MappedSuperclass
 * @ORM\HasLifecycleCallbacks()
 */
class SoaScaleCommentSuperClass
{
    use CreateEntityTrait;
    use UpdateEntityTrait;

    /**
     * @var int
     *
     * @ORM\Column(name="id", type="integer", nullable=false)
     * @ORM\Id
     * @ORM\GeneratedValue(strategy="IDENTITY")
     */
    protected $id;

    /**
     * @var AnrSuperClass
     *
     * @ORM\ManyToOne(targetEntity="Anr")
     * @ORM\JoinColumns({
     *   @ORM\JoinColumn(name="anr_id", referencedColumnName="id", nullable=true, onDelete="CASCADE")
     * })
     */
    protected $anr;

    /**
     * @var int
     *
     * @ORM\Column(name="scale_index", type="integer", options={"unsigned": true})
     */
    protected $scaleIndex;

    /**
     * @var int
     *
     * @ORM\Column(name="is_hidden", type="smallint", options={"default": 0})
     */
    protected $isHidden = 0;

    /**
     * @var string
     *
     * @ORM\Column(name="colour", type="string", length=255, options={"default": ""})
     */
    protected $colour = '';

    /**
     * @return int
     */
    public function getId()
    {
        return $this->id;
    }

    public function getAnr(): AnrSuperClass
    {
        return $this->anr;
    }

    public function setAnr(AnrSuperClass $anr): self
    {
        $this->anr = $anr;

        return $this;
    }

    public function getScaleIndex(): int
    {
        return $this->scaleIndex;
    }

    public function setScaleIndex(int $scaleIndex): self
    {
        $this->scaleIndex = $scaleIndex;

        return $this;
    }

    public function isHidden(): bool
    {
        return (bool)$this->isHidden;
    }

    public function setIsHidden(bool $isHidden): self
    {
        $this->isHidden = (int)$isHidden;

        return $this;
    }

    public function getColour(): string
    {
        return $this->colour;
    }

    public function setColour(string $colour): self
    {
        $this->colour = $colour;

        return $this;
    }

    public static function getDefaultCommentsData(): array
    {
        return [
            'fr' => [
                ['scaleIndex' => 0, 'colour' => '#FFFFFF', 'isHidden' => false, 'comment' => 'Inexistant'],
                ['scaleIndex' => 1, 'colour' => '#FD661F', 'isHidden' => false, 'comment' => 'Initialisé'],
                ['scaleIndex' => 2, 'colour' => '#FD661F', 'isHidden' => false, 'comment' => 'Reproductible'],
                ['scaleIndex' => 3, 'colour' => '#FFBC1C', 'isHidden' => false, 'comment' => 'Défini'],
                ['scaleIndex' => 4, 'colour' => '#FFBC1C', 'isHidden' => false, 'comment' => 'Géré quantitativement'],
                ['scaleIndex' => 5, 'colour' => '#D6F107', 'isHidden' => false, 'comment' => 'Optimisé'],
            ],
            'en' => [
                ['scaleIndex' => 0, 'colour' => '#FFFFFF', 'isHidden' => false, 'comment' => 'Non-existent'],
                ['scaleIndex' => 1, 'colour' => '#FD661F', 'isHidden' => false, 'comment' => 'Initial'],
                ['scaleIndex' => 2, 'colour' => '#FD661F', 'isHidden' => false, 'comment' => 'Managed'],
                ['scaleIndex' => 3, 'colour' => '#FFBC1C', 'isHidden' => false, 'comment' => 'Defined'],
                ['scaleIndex' => 4, 'colour' => '#FFBC1C', 'isHidden' => false, 'comment' => 'Quantitatively managed'],
                ['scaleIndex' => 5, 'colour' => '#D6F107', 'isHidden' => false, 'comment' => 'Optimized'],
            ],
            'de' => [
                ['scaleIndex' => 0, 'colour' => '#FFFFFF', 'isHidden' => false, 'comment' => 'Nicht vorhanden'],
                ['scaleIndex' => 1, 'colour' => '#FD661F', 'isHidden' => false, 'comment' => 'Initial'],
                ['scaleIndex' => 2, 'colour' => '#FD661F', 'isHidden' => false, 'comment' => 'Reproduzierbar'],
                ['scaleIndex' => 3, 'colour' => '#FFBC1C', 'isHidden' => false, 'comment' => 'Definiert'],
                ['scaleIndex' => 4, 'colour' => '#FFBC1C', 'isHidden' => false, 'comment' => 'Quantitativ verwaltet'],
                ['scaleIndex' => 5, 'colour' => '#D6F107', 'isHidden' => false, 'comment' => 'Optimiert'],
            ],
            'nl' => [
                ['scaleIndex' => 0, 'colour' => '#FFFFFF', 'isHidden' => false, 'comment' => 'Onbestaand'],
                ['scaleIndex' => 1, 'colour' => '#FD661F', 'isHidden' => false, 'comment' => 'Initieel'],
                ['scaleIndex' => 2, 'colour' => '#FD661F', 'isHidden' => false, 'comment' => 'Beheerst'],
                ['scaleIndex' => 3, 'colour' => '#FFBC1C', 'isHidden' => false, 'comment' => 'Gedefinieerd'],
                ['scaleIndex' => 4, 'colour' => '#FFBC1C', 'isHidden' => false, 'comment' => 'Kwantitatief beheerst'],
                ['scaleIndex' => 5, 'colour' => '#D6F107', 'isHidden' => false, 'comment' => 'Optimaliserend'],
            ],
        ];
    }
}
