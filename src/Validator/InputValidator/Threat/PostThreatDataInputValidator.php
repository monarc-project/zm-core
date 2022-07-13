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
        $rules = [
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

        $labelDescriptionRules = [];
        foreach ($this->systemLanguageIndexes as $systemLanguageIndex) {
            $labelDescriptionRules[] = $this->getLabelRule($systemLanguageIndex);
            $labelDescriptionRules[] = $this->getDescriptionRule($systemLanguageIndex);
        }

        return array_merge($labelDescriptionRules, $rules);
    }
}
