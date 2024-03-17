<?php declare(strict_types=1);
/**
 * @link      https://github.com/monarc-project for the canonical source repository
 * @copyright Copyright (c) 2016-2023 Luxembourg House of Cybersecurity LHC.lu - Licensed under GNU Affero GPL v3
 * @license   MONARC is licensed under GNU Affero General Public License version 3
 */

namespace Monarc\Core\Validator\InputValidator\Profile;

use Laminas\Filter\StringTrim;
use Laminas\Filter\ToInt;
use Laminas\Validator\EmailAddress;
use Laminas\Validator\StringLength;
use Monarc\Core\Entity\UserSuperClass;
use Monarc\Core\Service\ConnectedUserService;
use Monarc\Core\Validator\FieldValidator\UniqueEmail;
use Monarc\Core\Validator\InputValidator\AbstractInputValidator;
use Monarc\Core\Table\UserTable;
use Monarc\Core\Validator\InputValidator\InputValidationTranslator;

class PatchProfileDataInputValidator extends AbstractInputValidator
{
    protected UserTable $userTable;

    protected ?UserSuperClass $connectedUser;

    public function __construct(
        array $config,
        InputValidationTranslator $translator,
        UserTable $userTable,
        ConnectedUserService $connectedUserService
    ) {
        $this->userTable = $userTable;
        $this->connectedUser = $connectedUserService->getConnectedUser();

        parent::__construct($config, $translator);
    }

    protected function getRules(): array
    {
        return [
            [
                'name' => 'firstname',
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
                            'min' => 1,
                            'max' => 100,
                        ]
                    ],
                ],
            ],
            [
                'name' => 'lastname',
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
                            'min' => 1,
                            'max' => 100,
                        ]
                    ],
                ],
            ],
            [
                'name' => 'email',
                'required' => false,
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
                'name' => 'language',
                'required' => false,
                'filters' => [
                    [
                        'name' => ToInt::class,
                    ],
                ],
                'validators' => [
                ],
            ],
            [
                'name' => 'mospApiKey',
                'required' => false,
                'filters' => [
                    [
                        'name' => StringTrim::class,
                    ],
                ],
                'validators' => [
                ],
            ],
        ];
    }
}
