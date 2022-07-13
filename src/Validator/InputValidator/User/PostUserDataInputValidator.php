<?php declare(strict_types=1);
/**
 * @link      https://github.com/monarc-project for the canonical source repository
 * @copyright Copyright (c) 2016-2022 SMILE GIE Securitymadein.lu - Licensed under GNU Affero GPL v3
 * @license   MONARC is licensed under GNU Affero General Public License version 3
 */

namespace Monarc\Core\Validator\InputValidator\User;

use Laminas\Filter\StringTrim;
use Laminas\InputFilter\ArrayInput;
use Laminas\InputFilter\InputFilter;
use Laminas\Validator\Callback;
use Laminas\Validator\EmailAddress;
use Laminas\Validator\NotEmpty;
use Laminas\Validator\StringLength;
use Monarc\Core\Model\Entity\UserRole;
use Monarc\Core\Service\ConnectedUserService;
use Monarc\Core\Validator\FieldValidator\LanguageValidator;
use Monarc\Core\Validator\FieldValidator\UniqueEmail;
use Monarc\Core\Validator\InputValidator\AbstractInputValidator;
use Monarc\Core\Table\UserTable;

class PostUserDataInputValidator extends AbstractInputValidator
{
    protected UserTable $userTable;

    protected ConnectedUserService $connectedUserService;

    public function __construct(
        InputFilter $inputFilter,
        array $config,
        UserTable $userTable,
        ConnectedUserService $connectedUserService
    ) {
        $this->userTable = $userTable;
        $this->connectedUserService = $connectedUserService;

        parent::__construct($inputFilter, $config);
    }

    protected function getRules(): array
    {
        return [
            [
                'name' => 'firstname',
                'required' => true,
                'filters' => [
                    [
                        'name' => StringTrim::class,
                    ],
                ],
                'validators' => [
                    [
                        'name' => StringLength::class,
                        'options' => [
                            'min' => 1,
                            'max' => 100,
                        ]
                    ],
                ],
            ],
            [
                'name' => 'lastname',
                'required' => true,
                'filters' => [
                    [
                        'name' => StringTrim::class,
                    ],
                ],
                'validators' => [
                    [
                        'name' => StringLength::class,
                        'options' => [
                            'min' => 1,
                            'max' => 100,
                        ]
                    ],
                ],
            ],
            [
                'name' => 'password',
                'required' => false,
                'filters' => [
                    [
                        'name' => StringTrim::class,
                    ],
                ],
                'validators' => [
                    [
                        'name' => StringLength::class,
                        'options' => [
                            'min' => 9,
                        ]
                    ],
                ],
            ],
            [
                'name' => 'email',
                'required' => true,
                'filters' => [
                    [
                        'name' => StringTrim::class,
                    ],
                ],
                'validators' => [
                    [
                        'name' => EmailAddress::class,
                    ],
                    [
                        'name' => UniqueEmail::class,
                        'options' => [
                            'userTable' => $this->userTable,
                            'currentUserId' => $this->connectedUserService->getConnectedUser()->getId(),
                        ],
                    ],
                ],
            ],
            [
                'name' => 'role',
                'required' => true,
                'type' => ArrayInput::class,
                'validators' => [
                    [
                        'name' => NotEmpty::class,
                    ],
                    [
                        'name' => Callback::class,
                        'options' => [
                            'callback' => [$this, 'validateRoles'],
                        ],
                    ],
                ],
            ],
            [
                'name' => 'language',
                'allowEmpty' => true,
                'continueIfEmpty' => true,
                'required' => false,
                'filters' => [
                    ['name' => 'ToInt'],
                ],
                'validators' => [
                    [
                        'name' => LanguageValidator::class,
                        'options' => [
                            'systemLanguageIndexes' => $this->systemLanguageIndexes,
                        ]
                    ]
                ],
            ],
        ];
    }

    public function validateRoles($value): bool
    {
        return \in_array($value, UserRole::getAvailableRoles(), true);
    }
}
