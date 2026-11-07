<?php declare(strict_types=1);
/**
 * @link      https://github.com/monarc-project for the canonical source repository
 * @copyright Copyright (c) 2016-2026 Luxembourg House of Cybersecurity LHC.lu - Licensed under GNU Affero GPL v3
 * @license   MONARC is licensed under GNU Affero General Public License version 3
 */

namespace Monarc\Core\InputFormatter\Question;

use Monarc\Core\Exception\Exception;
use Monarc\Core\InputFormatter\AbstractInputFormatter;
use Monarc\Core\InputFormatter\FormattedInputParams;

class GetQuestionsInputFormatter extends AbstractInputFormatter
{
    protected static array $allowedSearchFields = ['label1', 'label2', 'label3', 'label4'];

    protected static array $allowedFilterFields = [
        'type' => ['type' => 'int', 'inArray' => [1, 2]],
        'multichoice' => ['type' => 'int', 'inArray' => [0, 1]],
    ];

    protected static string $defaultOrderFields = 'position:id';

    protected array $allowedOrderFields = [
        'id', 'type', 'multichoice', 'position', 'label1', 'label2', 'label3', 'label4',
        'createdAt', 'updatedAt', 'creator', 'updater',
    ];

    public function format(array $inputParams): FormattedInputParams
    {
        foreach (['page', 'limit', 'type', 'multichoice', 'question'] as $field) {
            if (isset($inputParams[$field])
                && ((!is_int($inputParams[$field])
                    && !(is_string($inputParams[$field]) && ctype_digit($inputParams[$field])))
                    || $inputParams[$field] < (in_array($field, ['question', 'type'], true) ? 1 : 0))
            ) {
                throw new Exception('Invalid query parameter: ' . $field, 412);
            }
        }
        if (isset($inputParams['filter']) && !is_string($inputParams['filter'])) {
            throw new Exception('Invalid query parameter: filter', 412);
        }
        if (isset($inputParams['order'])) {
            if (!is_string($inputParams['order'])) {
                throw new Exception('Invalid query parameter: order', 412);
            }
            foreach (explode(':', $inputParams['order']) as $field) {
                if (!in_array(ltrim($field, '-'), $this->allowedOrderFields, true)) {
                    throw new Exception('Invalid order field', 412);
                }
            }
        }

        return parent::format($inputParams);
    }
}
