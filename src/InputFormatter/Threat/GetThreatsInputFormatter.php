<?php declare(strict_types=1);
/**
 * @link      https://github.com/monarc-project for the canonical source repository
 * @copyright Copyright (c) 2016-2022 SMILE GIE Securitymadein.lu - Licensed under GNU Affero GPL v3
 * @license   MONARC is licensed under GNU Affero General Public License version 3
 */

namespace Monarc\Core\InputFormatter\Threat;

use Monarc\Core\InputFormatter\AbstractInputFormatter;
use Monarc\Core\Model\Entity\ThreatSuperClass;
use Monarc\Core\Service\ConnectedUserService;

class GetThreatsInputFormatter extends AbstractInputFormatter
{
    protected static array $allowedSearchFields = [
        'label1',
        'label2',
        'label3',
        'label4',
        'description1',
        'description2',
        'description3',
        'description4',
        'code',
    ];

    protected static array $allowedFilterFields = [
        'status' => [
            'default' => ThreatSuperClass::STATUS_ACTIVE,
            'type' => 'int',
        ],
    ];

    protected static array $ignoredFilterFieldValues = ['status' => 'all'];

    public function __construct(ConnectedUserService $connectedUserService)
    {
        $this->setDefaultLanguageIndex($connectedUserService->getConnectedUser()->getLanguage());
    }
}
