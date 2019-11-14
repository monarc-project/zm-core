<?php

namespace Monarc\Core\Validator;

use Zend\Validator\AbstractValidator;

class LanguageValidator extends AbstractValidator
{
    public function isValid($value): bool
    {
        if (!in_array($value, array_column($this->getOptions()['availableLanguages'], 'index'), true)) {
            $this->error(sprintf('The language index "%s" is not supported', $value));

            return false;
        }

        return true;
    }
}
