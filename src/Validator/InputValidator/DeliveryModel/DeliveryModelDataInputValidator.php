<?php declare(strict_types=1);
/**
 * @link      https://github.com/monarc-project for the canonical source repository
 * @copyright Copyright (c) 2016-2026 Luxembourg House of Cybersecurity LHC.lu - Licensed under GNU Affero GPL v3
 * @license   MONARC is licensed under GNU Affero General Public License version 3
 */

namespace Monarc\Core\Validator\InputValidator\DeliveryModel;

use Laminas\InputFilter\FileInput;
use Laminas\InputFilter\InputFilter;
use Laminas\Validator\Callback;
use Laminas\Validator\File\Extension;
use Laminas\Validator\File\Size;
use Laminas\Validator\File\UploadFile;
use Laminas\Validator\IsArray;
use Monarc\Core\Validator\FieldValidator\DeliveryModelUpload;
use Monarc\Core\Validator\InputValidator\AbstractInputValidator;
use Monarc\Core\Validator\InputValidator\InputValidationTranslator;

class DeliveryModelDataInputValidator extends AbstractInputValidator
{
    private array $dataLanguageIndexes;

    public function __construct(array $config, InputValidationTranslator $translator)
    {
        parent::__construct($config, $translator);
        $this->dataLanguageIndexes = array_column($config['languages'], 'index');
    }

    protected function getRules(): array
    {
        $uploads = new InputFilter();
        foreach ($this->dataLanguageIndexes as $languageIndex) {
            $uploads->add($this->getUploadInput(), (string)$languageIndex);
        }

        $rules = [
            $this->getIntegerRule('category', true, 1, 7),
            $this->getIntegerRule('editable', false, 0, 1),
            [
                'name' => 'file',
                'required' => false,
                'allow_empty' => true,
                'continue_if_empty' => true,
                'validators' => [
                    ['name' => IsArray::class, 'break_chain_on_failure' => true],
                    [
                        'name' => Callback::class,
                        'break_chain_on_failure' => true,
                        'options' => [
                            'callback' => fn (array $files): bool =>
                                array_diff(array_keys($files), $this->dataLanguageIndexes) === [],
                        ],
                    ],
                    [
                        'name' => Callback::class,
                        'options' => [
                            // One file per language; FileInput's multi-file mode would renumber the language keys.
                            'callback' => static fn (array $files): bool =>
                                array_all($files, static fn ($file): bool => isset($file['tmp_name']))
                                && $uploads->setData($files)->isValid(),
                            'messages' => [
                                Callback::INVALID_VALUE =>
                                    'Select a readable DOCX document for each uploaded language.',
                            ],
                        ],
                    ],
                ],
            ],
        ];
        // A new record needs a category; updates preserve omitted fields.
        foreach ($this->dataLanguageIndexes as $index) {
            $rules[] = $this->getTextRule('description' . $index);
        }

        return $rules;
    }

    protected function getUploadInput(): FileInput
    {
        $input = new FileInput('file');
        $input->setRequired(false)->setContinueIfEmpty(true);
        $input->getValidatorChain()
            ->attach(new UploadFile(), true)
            ->attach(new Extension(['extension' => 'docx']), true)
            ->attach(new Size(['min' => 1]), true)
            ->attach(new DeliveryModelUpload(), true);

        return $input;
    }
}
