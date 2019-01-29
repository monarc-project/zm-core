<?php
/**
 * @link      https://github.com/monarc-project for the canonical source repository
 * @copyright Copyright (c) 2016-2019  SMILE GIE Securitymadein.lu - Licensed under GNU Affero GPL v3
 * @license   MONARC is licensed under GNU Affero General Public License version 3
 */

namespace MonarcCore\Service;

/**
 * Referential Service Factory
 *
 * Class ReferentialServiceFactory
 * @package MonarcCore\Service
 */
class ReferentialServiceFactory extends AbstractServiceFactory
{
    protected $ressources = [
        'table' => 'MonarcCore\Model\Table\ReferentialTable',
        'entity' => 'MonarcCore\Model\Entity\Referential',
    ];
}
