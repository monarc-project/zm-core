<?php declare(strict_types=1);
/**
 * @link      https://github.com/monarc-project for the canonical source repository
 * @copyright Copyright (c) 2016-2022 SMILE GIE Securitymadein.lu - Licensed under GNU Affero GPL v3
 * @license   MONARC is licensed under GNU Affero General Public License version 3
 */

namespace Monarc\Core\Validator\InputValidator;

use Laminas\Filter\StringTrim;
use Laminas\I18n\Translator\Resources;
use Laminas\InputFilter\InputFilter;
use Laminas\Validator\AbstractValidator;
use Laminas\Validator\StringLength;
use Monarc\Core\Service\ConnectedUserService;

abstract class AbstractInputValidator
{
    protected InputFilter $inputFilter;

    protected int $defaultLanguageIndex;

    protected array $systemLanguageIndexes;

    protected array $initialData = [];

    private array $validData = [];

    public function __construct(array $config, ConnectedUserService $connectedUserService)
    {
        $this->inputFilter = new InputFilter();
        $this->defaultLanguageIndex = $config['defaultLanguageIndex'] ?? 1;
        $this->systemLanguageIndexes = array_column($config['languages'], 'index');

        $this->initRules();

        if ($connectedUserService->getConnectedUser() !== null) {
            $translator = (new InputValidationTranslator())
                ->addTranslationFilePattern('phpArray', Resources::getBasePath(), Resources::getPatternForValidator())
                ->addTranslationFilePattern(
                    'phpArray',
                    __DIR__ . '/../../../locale/languages/',
                    '%s/validation_messages.php'
                );

            $activeLanguages = $config['activeLanguages'] ?? ['fr', 'en', 'de', 'nl'];
            $translator->setLocale($activeLanguages[$connectedUserService->getConnectedUser()->getLanguage() - 1]);

            AbstractValidator::setDefaultTranslator($translator);
        }
    }

    public function isValid(array $data): bool
    {
        $this->initialData = $data;
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
