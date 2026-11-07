<?php declare(strict_types=1);
/**
 * @link      https://github.com/monarc-project for the canonical source repository
 * @copyright Copyright (c) 2016-2026 Luxembourg House of Cybersecurity LHC.lu - Licensed under GNU Affero GPL v3
 * @license   MONARC is licensed under GNU Affero General Public License version 3
 */

namespace Monarc\Core\Validator\InputValidator;

use Laminas\Filter\StringTrim;
use Laminas\InputFilter\InputFilter;
use Laminas\Validator\Between;
use Laminas\Validator\Callback;
use Laminas\Validator\Digits;
use Laminas\Validator\AbstractValidator;
use Laminas\Validator\StringLength;
use Monarc\Core\Exception\Exception;

abstract class AbstractInputValidator
{
    public const MAX_INTEGER = 2_147_483_647;
    public const MAX_UNSIGNED_SMALLINT = 65_535;
    public const MAX_STRING_LENGTH = 255;

    protected InputFilter $inputFilter;

    protected int $defaultLanguageIndex;

    protected array $systemLanguageIndexes;

    protected array $initialData = [];

    protected array $integerFields = [];

    protected array $defaultValues = [];

    private array $validData = [];

    private bool $areRulesInitialized = false;

    public function __construct(array $config, InputValidationTranslator $translator)
    {
        $this->inputFilter = new InputFilter();
        /* The defaultLanguageIndex property is set in the ControllerRequestResponseHandlerTrait from the anr language,
          before the validation is executed. Usually it is done for the FrontOffice side.*/
        $this->defaultLanguageIndex = $config['defaultLanguageIndex'] ?? 1;
        /* THe systemLanguageIndexes property is used to create label1,2,3,4 etc. related validation rules. */
        $this->systemLanguageIndexes = [1, 2, 3, 4];

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

    /** Validate one write without accumulating data or retaining a previous PATCH validation group. */
    public function validate(array $data, bool $partial = false): array
    {
        $filter = new InputFilter();
        foreach ($this->getRules() as $rule) {
            if (!$partial || array_key_exists($rule['name'], $data)) {
                $filter->add($rule);
            }
        }
        $filter->setData($data);
        if (!$filter->isValid()) {
            throw new Exception(json_encode(['validationErrors' => $filter->getMessages()], JSON_THROW_ON_ERROR), 412);
        }
        $values = array_intersect_key($filter->getValues(), $data);
        foreach ($this->integerFields as $field) {
            if (isset($values[$field])) {
                $values[$field] = (int)$values[$field];
            }
        }

        return $partial ? $values : $values + $this->defaultValues;
    }

    public function validateIds(array $ids): array
    {
        return array_values(array_unique(array_map('intval', $ids)));
    }

    public function setDefaultLanguageIndex(int $languageIndex): self
    {
        $this->defaultLanguageIndex = $languageIndex;

        return $this;
    }

    abstract protected function getRules(): array;

    protected function getIntegerRule(string $name, bool $required, ?int $min = null, ?int $max = null): array
    {
        $validators = [['name' => Digits::class]];
        if ($min !== null && $max !== null) {
            $validators[] = ['name' => Between::class, 'options' => ['min' => $min, 'max' => $max]];
        }

        return [
            'name' => $name, 'required' => $required, 'allow_empty' => !$required,
            'continue_if_empty' => $required,
            'validators' => $validators,
        ];
    }

    /** These legacy text fields allow empty translations and preserve whitespace. */
    protected function getTextRule(string $name, ?int $max = null, bool $nullable = true): array
    {
        $validator = $max === null
            ? new Callback(['callback' => static fn ($value): bool => is_string($value)])
            : new StringLength(['max' => $max]);

        return [
            'name' => $name, 'required' => false, 'allow_empty' => true, 'continue_if_empty' => true,
            'validators' => $nullable ? [[
                'name' => Callback::class,
                'options' => ['callback' => static fn ($value): bool => $value === null || $validator->isValid($value)],
            ]] : [$validator],
        ];
    }

    protected function getLabelRule(int $languageIndex, int $maxLength = self::MAX_STRING_LENGTH): array
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
                        'max' => $maxLength,
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
