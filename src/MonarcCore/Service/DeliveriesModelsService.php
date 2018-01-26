<?php
/**
 * @link      https://github.com/monarc-project for the canonical source repository
 * @copyright Copyright (c) 2018 SMILE GIE Securitymadein.lu - Licensed under GNU Affero GPL v3
 * @license   MONARC is licensed under GNU Affero General Public License version 3
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