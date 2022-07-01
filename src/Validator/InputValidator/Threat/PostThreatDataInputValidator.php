<?php declare(strict_types=1);
/**
 * @link      https://github.com/monarc-project for the canonical source repository
 * @copyright Copyright (c) 2016-2022 SMILE GIE Securitymadein.lu - Licensed under GNU Affero GPL v3
 * @license   MONARC is licensed under GNU Affero General Public License version 3
 */

namespace Monarc\Core\Validator\InputValidator\Threat;

use Laminas\Filter\StringTrim;
use Laminas\Validator\InArray;
use Laminas\Validator\StringLength;
use Monarc\Core\Model\Entity\ThreatSuperClass;
use Monarc\Core\Validator\InputValidator\AbstractInputValidator;

class PostThreatDataInputValidator extends AbstractInputValidator
{
    protected function getRules(): array
    {
        return [
            [
                'name' => 'code',
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
                            'max' => 255,
                        ]
                    ],
                ],
            ],
            [
                'name' => 'label' . $this->languageIndex,
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
                            'max' => 255,
                        ]
                    ],
                ],
            ],
            $this->getLabelRule(1),
            $this->getLabelRule(2),
            $this->getLabelRule(3),
            $this->getLabelRule(4),
            $this->getDescriptionRule(1),
            $this->getDescriptionRule(2),
            $this->getDescriptionRule(3),
            $this->getDescriptionRule(4),
            [
                'name' => 'mode',
                'required' => true,
                'filters' => [
                    [
                        'name' => 'ToInt'
                    ],
                ],
                'validators' => [
                    [
                        'name' => InArray::class,
                        'options' => [
                            'haystack' => [ThreatSuperClass::MODE_GENERIC, ThreatSuperClass::MODE_SPECIFIC],
                        ]
                    ],
                ],
            ],
            [
                'name' => 'c',
                'required' => true,
                'filters' => [
                    [
                        'name' => 'ToInt'
                    ],
                ],
                'validators' => [
                    [
                        'name' => InArray::class,
                        'options' => [
                            'haystack' => [false, true],
                        ]
                    ],
                ],
            ],
            [
                'name' => 'i',
                'required' => true,
                'filters' => [
                    [
                        'name' => 'ToInt'
                    ],
                ],
                'validators' => [
                    [
                        'name' => InArray::class,
                        'options' => [
                            'haystack' => [false, true],
                        ]
                    ],
                ],
            ],
            [
                'name' => 'a',
                'required' => true,
                'filters' => [
                    [
                        'name' => 'ToInt'
                    ],
                ],
                'validators' => [
                    [
                        'name' => InArray::class,
                        'options' => [
                            'haystack' => [false, true],
                        ]
                    ],
                ],
            ],
            [
                'name' => 'comment',
                'required' => false,
                'filters' => [
                ],
                'validators' => [],
            ],
            [
                'name' => 'qualification',
                'required' => false,
                'filters' => [
                    [
                        'name' => 'ToInt'
                    ],
                ],
                'validators' => [],
            ],
            [
                'name' => 'theme',
                'required' => false,
                'filters' => [
                    [
                        'name' => 'ToInt'
                    ],
                ],
                'validators' => [],
            ],
            [
                'name' => 'trend',
                'required' => false,
                'filters' => [
                    [
                        'name' => 'ToInt'
                    ],
                ],
                'validators' => [],
            ],
            [
                'name' => 'models',
                'required' => false,
                'filters' => [],
                'validators' => [],
            ],
            [
                'name' => 'follow',
                'required' => false,
                'filters' => [],
                'validators' => [],
            ],
            [
                'name' => 'status',
                'required' => false,
                'filters' => [
                    [
                        'name' => 'ToInt'
                    ],
                ],
                'validators' => [
                    [
                        'name' => InArray::class,
                        'options' => [
                            'haystack' => [ThreatSuperClass::STATUS_ACTIVE, ThreatSuperClass::STATUS_INACTIVE],
                        ]
                    ],
                ],
            ],
        ];
    }

    protected function getLabelRule(int $languageIndex): array
    {
        return [
            'name' => 'label' . $languageIndex,
            'required' => $this->languageIndex === $languageIndex,
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
                        'max' => 255,
                    ]
                ],
            ],
        ];
    }

    protected function getDescriptionRule(int $languageIndex): array
    {
        return [
            'name' => 'description' . $languageIndex,
            'required' => false,
            'filters' => [
                [
                    'name' => StringTrim::class,
                ],
            ],
        ];
    }
}
