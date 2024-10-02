<?php declare(strict_types=1);
/**
 * @link      https://github.com/monarc-project for the canonical source repository
 * @copyright Copyright (c) 2016-2023 Luxembourg House of Cybersecurity LHC.lu - Licensed under GNU Affero GPL v3
 * @license   MONARC is licensed under GNU Affero General Public License version 3
 */

namespace Monarc\Core\Validator\InputValidator\User;

use Laminas\Filter\StringTrim;
use Laminas\InputFilter\ArrayInput;
use Laminas\Validator\Callback;
use Laminas\Validator\EmailAddress;
use Laminas\Validator\NotEmpty;
use Laminas\Validator\StringLength;
use Monarc\Core\Entity\UserRole;
use Monarc\Core\Entity\UserSuperClass;
use Monarc\Core\Service\ConnectedUserService;
use Monarc\Core\Validator\FieldValidator\LanguageValidator;
use Monarc\Core\Validator\FieldValidator\UniqueEmail;
use Monarc\Core\Validator\InputValidator\AbstractInputValidator;
use Monarc\Core\Table\UserTable;
use Monarc\Core\Validator\InputValidator\InputValidationTranslator;

class PostUserDataInputValidator extends AbstractInputValidator
{
    protected UserTable $userTable;

    protected ?UserSuperClass $connectedUser;

    /** @var string[] */
    protected array $userRoles;

    public function __construct(
        array $config,
        InputValidationTranslator $translator,
        UserTable $userTable,
        ConnectedUserService $connectedUserService
    ) {
        $this->userTable = $userTable;
        $this->connectedUser = $connectedUserService->getConnectedUser();
        $this->userRoles = UserRole::getAvailableRoles();

        parent::__construct($config, $translator);
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
                            'currentUserId' => $this->connectedUser !== null ? $this->connectedUser->getId() : 0,
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
                'required' => false,
                'allow_empty' => true,
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
            [
                'name' => 'status',
                'required' => false,
                'allow_empty' => true,
                'filters' => [],
                'validators' => [],
            ],
        ];
    }

    public function validateRoles($value): bool
    {
        return \in_array($value, $this->userRoles, true);
    }
}
