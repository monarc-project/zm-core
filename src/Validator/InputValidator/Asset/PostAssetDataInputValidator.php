<?php declare(strict_types=1);
/**
 * @link      https://github.com/monarc-project for the canonical source repository
 * @copyright Copyright (c) 2016-2022 SMILE GIE Securitymadein.lu - Licensed under GNU Affero GPL v3
 * @license   MONARC is licensed under GNU Affero General Public License version 3
 */

namespace Monarc\Core\Validator\InputValidator\Asset;

use Laminas\Filter\StringTrim;
use Laminas\Validator\InArray;
use Laminas\Validator\StringLength;
use Monarc\Core\Model\Entity\AssetSuperClass;
use Monarc\Core\Validator\InputValidator\AbstractInputValidator;

class PostAssetDataInputValidator extends AbstractInputValidator
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
                'name' => 'type',
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
                            'haystack' => [AssetSuperClass::TYPE_PRIMARY, AssetSuperClass::TYPE_SECONDARY],
                        ]
                    ],
                ],
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
                            'haystack' => [AssetSuperClass::STATUS_ACTIVE, AssetSuperClass::STATUS_INACTIVE],
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
