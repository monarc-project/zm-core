<?php declare(strict_types=1);
/**
 * @link      https://github.com/monarc-project for the canonical source repository
 * @copyright Copyright (c) 2016-2024 Luxembourg House of Cybersecurity LHC.lu - Licensed under GNU Affero GPL v3
 * @license   MONARC is licensed under GNU Affero General Public License version 3
 */

namespace Monarc\Core\Validator\InputValidator\Asset;

use Laminas\Filter\StringTrim;
use Laminas\Validator\InArray;
use Laminas\Validator\StringLength;
use Monarc\Core\Entity\AssetSuperClass;
use Monarc\Core\Table\Interfaces\UniqueCodeTableInterface;
use Monarc\Core\Validator\FieldValidator\UniqueCode;
use Monarc\Core\Validator\InputValidator\AbstractInputValidator;
use Monarc\Core\Validator\InputValidator\FilterFieldsValidationTrait;
use Monarc\Core\Validator\InputValidator\InputValidationTranslator;

/**
 * Note. For UniqueCode validator $excludeFilter/$includeFilter properties have to be set before calling isValid method.
 */
class PostAssetDataInputValidator extends AbstractInputValidator
{
    use FilterFieldsValidationTrait;

    public function __construct(
        array $config,
        InputValidationTranslator $translator,
        protected UniqueCodeTableInterface $assetTable
    ) {

        parent::__construct($config, $translator);
    }

    protected function getRules(): array
    {
        $rules = [
            [
                'name' => 'uuid',
                'required' => false,
                'filters' => [],
                'validators' => [
                    [
                        'name' => StringLength::class,
                        'options' => [
                            'min' => 36,
                            'max' => 36,
                        ]
                    ],
                ],
            ],
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
                    [
                        'name' => UniqueCode::class,
                        'options' => [
                            'uniqueCodeValidationTable' => $this->assetTable,
                            'includeFilter' => $this->includeFilter,
                            'excludeFilter' => $this->excludeFilter,
                        ],
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
