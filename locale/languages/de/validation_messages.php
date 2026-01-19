<?php declare(strict_types=1);
/**
 * @link      https://github.com/monarc-project for the canonical source repository
 * @copyright Copyright (c) 2016-2023 Luxembourg House of Cybersecurity LHC.lu - Licensed under GNU Affero GPL v3
 * @license   MONARC is licensed under GNU Affero General Public License version 3
 */

return [
    // Monarc\Core\Validator\FieldValidator\LanguageValidator
    'The language index "%s" is not supported' => 'Der Sprachindex "%s" wird nicht unterstützt',

    // Monarc\Core\Validator\FieldValidator\PasswordStrength
    'be at least 12 characters in length' => 'mindestens 12 Zeichen lang sein',
    'contain at least one uppercase letter' => 'mindestens einen Großbuchstaben enthalten',
    'contain at least one lowercase letter' => 'mindestens einen Kleinbuchstaben enthalten',
    'contain at least one digit character' => 'mindestens eine Ziffer enthalten',
    'contain at least one special character' => 'mindestens ein Sonderzeichen enthalten',

    // Monarc\Core\Validator\FieldValidator\UniqueCode
    'The code is unique. Please, specify another value.' =>
        'Der Code ist einzigartig. Bitte geben Sie einen anderen Wert an.',

    // Monarc\Core\Validator\FieldValidator\UniqueDeliveryModel
    'This category is already used.' => 'Diese Kategorie wird bereits verwendet.',
    'Maximum number of templates reached for this category.' =>
        'Maximale Anzahl an Vorlagen für diese Kategorie erreicht.',

    // Monarc\Core\Validator\FieldValidator\UniqueEmail
    'This email is already used' => 'Diese E-Mail wird bereits verwendet',
];
