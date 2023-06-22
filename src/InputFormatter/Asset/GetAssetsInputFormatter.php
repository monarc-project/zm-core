<?php declare(strict_types=1);
/**
 * @link      https://github.com/monarc-project for the canonical source repository
 * @copyright Copyright (c) 2016-2023 Luxembourg House of Cybersecurity LHC.lu - Licensed under GNU Affero GPL v3
 * @license   MONARC is licensed under GNU Affero General Public License version 3
 */

namespace Monarc\Core\InputFormatter\Asset;

use Monarc\Core\InputFormatter\AbstractInputFormatter;
use Monarc\Core\Model\Entity\AssetSuperClass;
use Monarc\Core\Service\ConnectedUserService;

class GetAssetsInputFormatter extends AbstractInputFormatter
{
    protected static array $allowedSearchFields = [
        'label{languageIndex}',
        'description{languageIndex}',
        'code',
    ];

    protected static array $allowedFilterFields = [
        'status' => [
            'default' => AssetSuperClass::STATUS_ACTIVE,
            'type' => 'int',
        ],
        'type' => [
            'type' => 'int',
        ],
    ];

    protected static array $ignoredFilterFieldValues = ['status' => 'all'];

    public function __construct(ConnectedUserService $connectedUserService)
    {
        $this->setDefaultLanguageIndex($connectedUserService->getConnectedUser()->getLanguage());
    }
}
