<?php declare(strict_types=1);
/**
 * @link      https://github.com/monarc-project for the canonical source repository
 * @copyright Copyright (c) 2016-2023 Luxembourg House of Cybersecurity LHC.lu - Licensed under GNU Affero GPL v3
 * @license   MONARC is licensed under GNU Affero General Public License version 3
 */

return [
    // Monarc\Core\Validator\FieldValidator\LanguageValidator
    'The language index "%s" is not supported' => 'L\'index de langue "%s" n\'est pas pris en charge',

    // Monarc\Core\Validator\FieldValidator\PasswordStrength
    'be at least 12 characters in length' => 'comporter au moins 12 caractères',
    'contain at least one uppercase letter' => 'contient au moins une lettre majuscule',
    'contain at least one lowercase letter' => 'contenir au moins une lettre minuscule',
    'contain at least one digit character' => 'contenir au moins un caractère numérique',
    'contain at least one special character' => 'contenir au moins un caractère spécial',

    // Monarc\Core\Validator\FieldValidator\UniqueCode
    'The code is unique. Please, specify another value.' => 'Le code est unique. Veuillez spécifier une autre valeur.',

    // Monarc\Core\Validator\FieldValidator\UniqueDeliveryModel
    'This category is already used.' => 'Cette catégorie est déjà utilisée.',
    'Maximum number of templates reached for this category.' =>
        'Nombre maximum de modèles atteint pour cette catégorie.',

    // Monarc\Core\Validator\FieldValidator\UniqueEmail
    'This email is already used' => 'Cet e-mail est déjà pris',
];
