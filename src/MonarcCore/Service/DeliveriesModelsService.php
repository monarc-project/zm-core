<?php
/**
 * @link      https://github.com/CASES-LU for the canonical source repository
 * @copyright Copyright (c) Cases is a registered trademark of SECURITYMADEIN.LU
 * @license   MyCases is licensed under the GNU Affero GPL v3 - See license.txt for more information
 */

namespace MonarcCore\Service;

/**
 * DocModels Service
 *
 * Class ModelService
 * @package MonarcCore\Service
 */
class DeliveriesModelsService extends AbstractService
{
    protected $filterColumns = [
        //'category',
        //'description',
    ];

    /**
     * @inheritdoc
     */
    public function patch($id, $data)
    {
        if (isset($data['description'])) {
            $data['description' . $this->getLanguage()] = $data['description'];
            unset($data['description']);
        }

        return parent::patch($id,$data);
    }
}