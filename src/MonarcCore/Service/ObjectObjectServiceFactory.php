<?php
/**
 * @link      https://github.com/monarc-project for the canonical source repository
 * @copyright Copyright (c) 2018 SMILE GIE Securitymadein.lu - Licensed under GNU Affero GPL v3
 * @license   MONARC is licensed under GNU Affero General Public License version 3
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
        'MonarcObjectTable' => '\MonarcCore\Model\Table\MonarcObjectTable',
        'entity' => '\MonarcCore\Model\Entity\ObjectObject',
        'childTable' => '\MonarcCore\Model\Table\MonarcObjectTable',
        'fatherTable' => '\MonarcCore\Model\Table\MonarcObjectTable',
        'modelTable' => '\MonarcCore\Model\Table\modelTable',
    ];
}