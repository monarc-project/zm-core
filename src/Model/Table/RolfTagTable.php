<?php
/**
 * @link      https://github.com/monarc-project for the canonical source repository
 * @copyright Copyright (c) 2016-2020 SMILE GIE Securitymadein.lu - Licensed under GNU Affero GPL v3
 * @license   MONARC is licensed under GNU Affero General Public License version 3
 */

namespace Monarc\Core\Model\Table;

use Doctrine\ORM\EntityNotFoundException;
use Monarc\Core\Model\Db;
use Monarc\Core\Entity\RolfTag;
use Monarc\Core\Entity\RolfTagSuperClass;
use Monarc\Core\Service\ConnectedUserService;

/**
 * Class RolfTagTable
 * @package Monarc\Core\Model\Table
 */
class RolfTagTable extends AbstractEntityTable
{
    public function __construct(Db $dbService, ConnectedUserService $connectedUserService)
    {
        parent::__construct($dbService, RolfTag::class, $connectedUserService);
    }

    /**
     * @throws EntityNotFoundException
     */
    public function findById(int $id): RolfTagSuperClass
    {
        /** @var RolfTagSuperClass|null $rolfTag */
        $rolfTag = $this->getRepository()->find($id);
        if ($rolfTag === null) {
            throw EntityNotFoundException::fromClassNameAndIdentifier(\get_class($this), [$id]);
        }

        return $rolfTag;
    }
}
