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
use Monarc\Core\Model\Entity\AnrSuperClass;
use Monarc\Core\Model\Entity\AssetSuperClass;
use Monarc\Core\Table\Interfaces\UniqueCodeTableInterface;
use Monarc\Core\Validator\FieldValidator\UniqueCode;
use Monarc\Core\Validator\InputValidator\AbstractInputValidator;

class PostAssetDataInputValidator extends AbstractInputValidator
{
    private UniqueCodeTableInterface $assetTable;

    private ?AnrSuperClass $anr = null;

    private array $excludeFilter = [];

    public function __construct(
        array $config,
        UniqueCodeTableInterface $assetTable
    ) {
        $this->assetTable = $assetTable;

        parent::__construct($config);
    }

    public function setAnr(AnrSuperClass $anr): self
    {
        $this->anr = $anr;

        return $this;
    }

    public function setExcludeFilter(array $excludeFilter): self
    {
        $this->excludeFilter = $excludeFilter;

        return $this;
    }

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
                    [
                        'name' => UniqueCode::class,
                        'options' => [
                            'uniqueCodeValidationTable' => $this->assetTable,
                            'anr' => function () {
                                return $this->anr;
                            },
                            'excludeFilter' => function () {
                                return $this->excludeFilter;
                            },
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
