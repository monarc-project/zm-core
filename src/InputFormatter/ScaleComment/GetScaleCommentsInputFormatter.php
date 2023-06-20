<?php declare(strict_types=1);
/**
 * @link      https://github.com/monarc-project for the canonical source repository
 * @copyright Copyright (c) 2016-2023 Luxembourg House of Cybersecurity LHC.lu - Licensed under GNU Affero GPL v3
 * @license   MONARC is licensed under GNU Affero General Public License version 3
 */

namespace Monarc\Core\InputFormatter\ScaleComment;

use Monarc\Core\InputFormatter\AbstractInputFormatter;

class GetScaleCommentsInputFormatter extends AbstractInputFormatter
{
    protected const DEFAULT_LIMIT = 0;

    protected static array $allowedFilterFields = [
        'anr',
        'scale',
    ];
}
