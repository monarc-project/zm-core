<?php declare(strict_types=1);
/**
 * @link      https://github.com/monarc-project for the canonical source repository
 * @copyright Copyright (c) 2016-2020 SMILE GIE Securitymadein.lu - Licensed under GNU Affero GPL v3
 * @license   MONARC is licensed under GNU Affero General Public License version 3
 */

namespace Monarc\Core\Exception;

use Throwable;

class ActionForbiddenException extends \Exception
{
    public function __construct($message = "The action is forbidden.", $code = 403, Throwable $previous = null)
    {
        parent::__construct($message, $code, $previous);
    }
}
