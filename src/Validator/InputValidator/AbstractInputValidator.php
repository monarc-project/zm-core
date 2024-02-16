<?php declare(strict_types=1);
/**
 * @link      https://github.com/monarc-project for the canonical source repository
 * @copyright Copyright (c) 2016-2023 Luxembourg House of Cybersecurity LHC.lu - Licensed under GNU Affero GPL v3
 * @license   MONARC is licensed under GNU Affero General Public License version 3
 */

namespace Monarc\Core\Validator\InputValidator;

use Laminas\Filter\StringTrim;
use Laminas\InputFilter\InputFilter;
use Laminas\Validator\AbstractValidator;
use Laminas\Validator\StringLength;

abstract class AbstractInputValidator
{
    protected InputFilter $inputFilter;

    protected int $defaultLanguageIndex;

    protected array $systemLanguageIndexes;

    protected array $initialData = [];

    private array $validData = [];

    private bool $areRulesInitialized = false;

    public function __construct(array $config, InputValidationTranslator $translator)
    {
        $this->inputFilter = new InputFilter();
        /* The defaultLanguageIndex property is set in the ControllerRequestResponseHandlerTrait from the anr language,
          before the validation is executed. Usually it is done for the FrontOffice side.*/
        $this->defaultLanguageIndex = $config['defaultLanguageIndex'] ?? 1;
        $activeLanguages = array_intersect_key($config['languages'], array_flip($config['activeLanguages']));
        $this->systemLanguageIndexes = array_column($activeLanguages, 'index');

        AbstractValidator::setDefaultTranslator($translator);
    }

    public function isValid(array $data): bool
    {
        $this->initialData = $data;
        if (!$this->areRulesInitialized) {
            /* Rules initialisation is not done in the constructor to allow setting up additional properties and
            options (e.g. defaultLanguageIndex, anr, excludeFilter etc). */
            $this->initRules();
            $this->areRulesInitialized = true;
        }
        $this->inputFilter->setData($data);

        $isValid = $this->inputFilter->isValid();
        if ($isValid) {
            $this->validData[] = $this->inputFilter->getValues();
        }

        return $isValid;
    }

    public function getErrorMessages(): array
    {
        return $this->inputFilter->getMessages();
    }

    public function getErrorMessagesRow(bool $wereBatchDataValidated, int $rowNum): string
    {
        $errorMessages = [];
        foreach ($this->getErrorMessages() as $field => $messages) {
            $errorMessages[$field] = array_map('htmlentities', array_values($messages));
        }

        $result = [
            'validationErrors' => $errorMessages,
        ];
        if ($wereBatchDataValidated) {
            $result['row'] = $rowNum + 1;
        }

        return html_entity_decode(json_encode($result, JSON_THROW_ON_ERROR));
    }

    public function getValidData(int $validatedSetNum = 0): array
    {
        return $this->validData[$validatedSetNum] ?? [];
    }

    public function getValidDataSets(): array
    {
        return $this->validData;
    }

    public function setDefaultLanguageIndex(int $languageIndex): self
    {
        $this->defaultLanguageIndex = $languageIndex;

        return $this;
    }

    abstract protected function getRules(): array;

    protected function getLabelRule(int $languageIndex): array
    {
        return [
            'name' => 'label' . $languageIndex,
            'required' => $this->defaultLanguageIndex === $languageIndex,
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

    private function initRules(): void
    {
        foreach ($this->getRules() as $rule) {
            $this->inputFilter->add($rule);
        }
    }
}
