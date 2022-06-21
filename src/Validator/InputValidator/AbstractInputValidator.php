<?php declare(strict_types=1);
/**
 * @link      https://github.com/monarc-project for the canonical source repository
 * @copyright Copyright (c) 2016-2022 SMILE GIE Securitymadein.lu - Licensed under GNU Affero GPL v3
 * @license   MONARC is licensed under GNU Affero General Public License version 3
 */

namespace Monarc\Core\Validator\InputValidator;

use Laminas\InputFilter\InputFilter;

abstract class AbstractInputValidator
{
    protected InputFilter $inputFilter;

    protected int $languageIndex;

    public function __construct(InputFilter $inputFilter, array $config)
    {
        $this->inputFilter = $inputFilter;
        $this->languageIndex = $config['defaultLanguageIndex'] ?? 1;

        $this->initRules();
    }

    public function isValid(array $data): bool
    {
        $this->inputFilter->setData($data);

        return $this->inputFilter->isValid();
    }

    public function getErrorMessages(): array
    {
        return $this->inputFilter->getMessages();
    }

    public function getValidData(): array
    {
        return $this->inputFilter->getValues();
    }

    public function setLanguageIndex(int $languageIndex): self
    {
        $this->languageIndex = $languageIndex;

        return $this;
    }

    abstract protected function getRules(): array;

    private function initRules(): void
    {
        foreach ($this->getRules() as $rule) {
            $this->inputFilter->add($rule);
        }
    }
}
