<?php
/**
 * @link      https://github.com/monarc-project for the canonical source repository
 * @copyright Copyright (c) 2016-2020 SMILE GIE Securitymadein.lu - Licensed under GNU Affero GPL v3
 * @license   MONARC is licensed under GNU Affero General Public License version 3
 */

namespace Monarc\Core\Model\Table;

use Doctrine\ORM\EntityNotFoundException;
use Monarc\Core\Model\Db;
use Monarc\Core\Entity\Referential;
use Monarc\Core\Service\ConnectedUserService;

class ReferentialTable extends AbstractEntityTable
{
    public function __construct(Db $dbService, ConnectedUserService $connectedUserService)
    {
        parent::__construct($dbService, Referential::class, $connectedUserService);
    }

    public function findByUuid(string $uuid): Referential
    {
        $referential = $this->getRepository()->find($uuid);
        if ($referential === null) {
            throw new EntityNotFoundException(sprintf('Referential with uuid "%s" was not found', $uuid));
        }

        return $referential;
    }
}
