<?php declare(strict_types=1);
/**
 * @link      https://github.com/monarc-project for the canonical source repository
 * @copyright Copyright (c) 2016-2024 Luxembourg House of Cybersecurity LHC.lu - Licensed under GNU Affero GPL v3
 * @license   MONARC is licensed under GNU Affero General Public License version 3
 */

namespace Monarc\Core\Validator\InputValidator\RolfRisk;

use Laminas\Filter\StringTrim;
use Laminas\InputFilter\ArrayInput;
use Laminas\Validator\StringLength;
use Monarc\Core\Table\Interfaces\UniqueCodeTableInterface;
use Monarc\Core\Validator\FieldValidator\UniqueCode;
use Monarc\Core\Validator\InputValidator\AbstractInputValidator;
use Monarc\Core\Validator\InputValidator\FilterFieldsValidationTrait;
use Monarc\Core\Validator\InputValidator\InputValidationTranslator;

class PostRolfRiskDataInputValidator extends AbstractInputValidator
{
    use FilterFieldsValidationTrait;

    public function __construct(
        array $config,
        InputValidationTranslator $translator,
        protected UniqueCodeTableInterface $rolfRiskTable
    ) {
        parent::__construct($config, $translator);
    }

    protected function getRules(): array
    {
        $labelRules = [];
        foreach ($this->systemLanguageIndexes as $systemLanguageIndex) {
            $labelRules[] = $this->getLabelRule($systemLanguageIndex);
        }
        $descriptionRules = [];
        foreach ($this->systemLanguageIndexes as $systemLanguageIndex) {
            $descriptionRules[] = $this->getDescriptionRule($systemLanguageIndex);
        }

        return array_merge([
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
                            'uniqueCodeValidationTable' => $this->rolfRiskTable,
                            'includeFilter' => $this->includeFilter,
                            'excludeFilter' => $this->excludeFilter,
                        ],
                    ],
                ],
            ],
            [
                'name' => 'measures',
                'required' => false,
                'type' => ArrayInput::class,
                'filters' => [],
                'validators' => [],
            ],
            [
                'name' => 'tags',
                'required' => false,
                'type' => ArrayInput::class,
                'filters' => [],
                'validators' => [],
            ],
        ], $labelRules, $descriptionRules);
    }
}