<?php
/**
 * @link      https://github.com/monarc-project for the canonical source repository
 * @copyright Copyright (c) 2016-2019  SMILE GIE Securitymadein.lu - Licensed under GNU Affero GPL v3
 * @license   MONARC is licensed under GNU Affero General Public License version 3
 */

namespace Monarc\Core\Model\Table;

use Monarc\Core\Model\Db;
use Monarc\Core\Model\Entity\ObjectCategory;
use Monarc\Core\Service\ConnectedUserService;

/**
 * Object Category Table
 *
 * Class ObjectCategoryTable
 * @package Monarc\Core\Model\Table
 */
class ObjectCategoryTable extends AbstractEntityTable
{
    public function __construct(Db $dbService, ConnectedUserService $connectedUserService)
    {
        parent::__construct($dbService, ObjectCategory::class, $connectedUserService);
    }

    /**
     * Get Child
     *
     * @param $id
     * @return array
     */
    public function getChild($id)
    {
        $child = $this->getRepository()->createQueryBuilder('t')
            ->select(array('t.id'))
            ->where('t.parent = :parent')
            ->setParameter(':parent', $id)
            ->getQuery()
            ->getResult();

        return $child;
    }
}
