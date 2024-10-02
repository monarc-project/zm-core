<?php declare(strict_types=1);
/**
 * @link      https://github.com/monarc-project for the canonical source repository
 * @copyright Copyright (c) 2016-2023 Luxembourg House of Cybersecurity LHC.lu - Licensed under GNU Affero GPL v3
 * @license   MONARC is licensed under GNU Affero General Public License version 3
 */

namespace Monarc\Core\Validator\FieldValidator;

use Laminas\Validator\AbstractValidator;

class LanguageValidator extends AbstractValidator
{
    public function isValid($value): bool
    {
        if (!\in_array($value, $this->getOptions()['systemLanguageIndexes'], true)) {
            $this->error(sprintf('The language index "%s" is not supported', $value));

            return false;
        }

        return true;
    }
}
