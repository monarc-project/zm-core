<?php declare(strict_types=1);
/**
 * @link      https://github.com/monarc-project for the canonical source repository
 * @copyright Copyright (c) 2016-2026 Luxembourg House of Cybersecurity LHC.lu - Licensed under GNU Affero GPL v3
 * @license   MONARC is licensed under GNU Affero General Public License version 3
 */

namespace Monarc\Core\Validator\InputValidator\Guide;

use Monarc\Core\Validator\InputValidator\AbstractInputValidator;

class GuideItemDataInputValidator extends AbstractInputValidator
{
    protected array $integerFields = ['guide', 'position', 'implicitPosition', 'previous'];

    protected array $defaultValues = ['description1' => '', 'description2' => '',
        'description3' => '', 'description4' => ''];

    protected function getRules(): array
    {
        $rules = [
            $this->getIntegerRule('guide', true, 1, self::MAX_INTEGER),
            $this->getIntegerRule('position', false),
            $this->getIntegerRule('implicitPosition', false, 1, 3),
            $this->getIntegerRule('previous', false, 1, self::MAX_INTEGER),
        ];
        foreach ($this->systemLanguageIndexes as $index) {
            $rules[] = $this->getTextRule('description' . $index, nullable: false);
        }

        return $rules;
    }
}
