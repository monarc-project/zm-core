<?php
/**
 * @link      https://github.com/CASES-LU for the canonical source repository
 * @copyright Copyright (c) Cases is a registered trademark of SECURITYMADEIN.LU
 * @license   MyCases is licensed under the GNU Affero GPL v3 - See license.txt for more information
 */

namespace MonarcCore\Service;

/**
 * Object Object Service Factory
 *
 * Class ObjectObjectServiceFactory
 * @package MonarcCore\Service
 */
class ObjectObjectServiceFactory extends AbstractServiceFactory
{
    protected $ressources = [
        'table' => '\MonarcCore\Model\Table\ObjectObjectTable',
        'anrTable' => '\MonarcCore\Model\Table\AnrTable',
        'instanceTable' => '\MonarcCore\Model\Table\InstanceTable',
        'objectTable' => '\MonarcCore\Model\Table\ObjectTable',
        'entity' => '\MonarcCore\Model\Entity\ObjectObject',
        'childTable' => '\MonarcCore\Model\Table\ObjectTable',
        'fatherTable' => '\MonarcCore\Model\Table\ObjectTable',
        'modelTable' => '\MonarcCore\Model\Table\modelTable',
    ];
}