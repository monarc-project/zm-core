<?php declare(strict_types=1);
/**
 * @link      https://github.com/monarc-project for the canonical source repository
 * @copyright Copyright (c) 2016-2023 Luxembourg House of Cybersecurity LHC.lu - Licensed under GNU Affero GPL v3
 * @license   MONARC is licensed under GNU Affero General Public License version 3
 */

return [
    // Monarc\Core\Validator\FieldValidator\LanguageValidator
    'The language index "%s" is not supported' => 'De taalindex "%s" wordt niet ondersteund',

    // Monarc\Core\Validator\FieldValidator\PasswordStrength
    'be at least 8 characters in length' => 'minimaal 8 tekens lang zijn',
    'contain at least one uppercase letter' => 'bevat ten minste een hoofdletter',
    'contain at least one lowercase letter' => 'minstens één kleine letter bevatten',
    'contain at least one digit character' => 'minstens één cijfer bevatten',
    'contain at least one special character' => 'minstens één speciaal teken bevatten',

    // Monarc\Core\Validator\FieldValidator\UniqueCode
    'The code is unique. Please, specify another value.' => 'De code is uniek. Geef een andere waarde op.',

    // Monarc\Core\Validator\FieldValidator\UniqueDeliveryModel
    'This category is already used.' => 'Deze categorie wordt al gebruikt.',
    'Maximum number of templates reached for this category.' =>
        'Maximum aantal sjablonen bereikt voor deze categorie.',

    // Monarc\Core\Validator\FieldValidator\UniqueEmail
    'This email is already used' => 'deze email is al in gebruik',
];
