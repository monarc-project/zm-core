<?php declare(strict_types=1);
/**
 * @link      https://github.com/monarc-project for the canonical source repository
 * @copyright Copyright (c) 2016-2024 Luxembourg House of Cybersecurity LHC.lu - Licensed under GNU Affero GPL v3
 * @license   MONARC is licensed under GNU Affero General Public License version 3
 */

namespace Monarc\Core\Validator\InputValidator\Measure;

use Laminas\Filter\StringTrim;
use Laminas\Filter\ToInt;
use Laminas\Validator\StringLength;
use Monarc\Core\Table\Interfaces\UniqueCodeTableInterface;
use Monarc\Core\Validator\FieldValidator\UniqueCode;
use Monarc\Core\Validator\InputValidator\AbstractInputValidator;
use Monarc\Core\Validator\InputValidator\FilterFieldsValidationTrait;
use Monarc\Core\Validator\InputValidator\InputValidationTranslator;

/**
 * Note. For UniqueCode validator $excludeFilter/$includeFilter properties have to be set before calling isValid method.
 */
class PostMeasureDataInputValidator extends AbstractInputValidator
{
    use FilterFieldsValidationTrait;

    private UniqueCodeTableInterface $measureTable;

    public function __construct(
        array $config,
        InputValidationTranslator $translator,
        UniqueCodeTableInterface $measureTable
    ) {
        $this->measureTable = $measureTable;

        parent::__construct($config, $translator);
    }

    protected function getRules(): array
    {
        if (!empty($this->initialData['referentialUuid'])) {
            $this->includeFilter['referential'] = $this->initialData['referentialUuid'];
        }

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
                    [
                        'name' => UniqueCode::class,
                        'options' => [
                            'uniqueCodeValidationTable' => $this->measureTable,
                            'includeFilter' => $this->includeFilter,
                            'excludeFilter' => $this->excludeFilter,
                        ],
                    ],
                ],
            ],
            [
                'name' => 'referentialUuid',
                'required' => true,
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
                'name' => 'categoryId',
                'required' => true,
                'filters' => [
                    ['name' => ToInt::class],
                ],
                'validators' => [],
            ],
        ];

        $labelRules = [];
        foreach ($this->systemLanguageIndexes as $systemLanguageIndex) {
            $labelRules[] = $this->getLabelRule($systemLanguageIndex);
        }

        return array_merge($labelRules, $rules);
    }
}
