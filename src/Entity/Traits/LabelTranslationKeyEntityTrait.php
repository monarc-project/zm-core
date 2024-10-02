<?php declare(strict_types=1);
/**
 * @link      https://github.com/monarc-project for the canonical source repository
 * @copyright Copyright (c) 2016-2023 Luxembourg House of Cybersecurity LHC.lu - Licensed under GNU Affero GPL v3
 * @license   MONARC is licensed under GNU Affero General Public License version 3
 */

namespace Monarc\Core\Entity\Traits;

trait LabelTranslationKeyEntityTrait
{
    /**
     * @var string
     *
     * @ORM\Column(name="label_translation_key", type="string", length=255, nullable=false, options={"default": ""})
     */
    protected $labelTranslationKey = '';

    public function getLabelTranslationKey(): string
    {
        return $this->labelTranslationKey;
    }

    public function setLabelTranslationKey(string $labelTranslationKey): self
    {
        $this->labelTranslationKey = $labelTranslationKey;

        return $this;
    }
}
