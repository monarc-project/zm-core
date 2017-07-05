<?php
/**
 * @link      https://github.com/monarc-project for the canonical source repository
 * @copyright Copyright (c) Cases is a registered trademark of SECURITYMADEIN.LU
 * @license   MyCases is licensed under the GNU Affero GPL v3 - See license.txt for more information
 */

namespace MonarcCore\Service;

/**
 * Scale Comment Service Factory
 *
 * Class ScaleCommentServiceFactory
 * @package MonarcCore\Service
 */
class ScaleCommentServiceFactory extends AbstractServiceFactory
{
    protected $ressources = [
        'table' => 'MonarcCore\Model\Table\ScaleCommentTable',
        'entity' => 'MonarcCore\Model\Entity\ScaleComment',
        'anrTable' => 'MonarcCore\Model\Table\AnrTable',
        'scaleTable' => 'MonarcCore\Model\Table\ScaleTable',
        'scaleImpactTypeTable' => 'MonarcCore\Model\Table\ScaleImpactTypeTable',
        'scaleService' => 'MonarcCore\Service\ScaleService',
        'scaleImpactTypeService' => 'MonarcCore\Service\ScaleImpactTypeService',
    ];
}