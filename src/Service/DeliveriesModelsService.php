<?php
/**
 * @link      https://github.com/monarc-project for the canonical source repository
 * @copyright Copyright (c) 2016-2020 SMILE GIE Securitymadein.lu - Licensed under GNU Affero GPL v3
 * @license   MONARC is licensed under GNU Affero General Public License version 3
 */

namespace Monarc\Core\Service;

/**
 * DocModels Service
 *
 * Class ModelService
 * @package Monarc\Core\Service
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

        return parent::patch($id, $data);
    }

    /**
     * @inheritdoc
     */
    public function delete($id)
    {
        $entity = $this->get('table')->getEntity($id);

        $pathModel = getenv('APP_CONF_DIR') ?: '';

        $entitiesPaths = array($entity->path1, $entity->path2, $entity->path3, $entity->path4);

        foreach ($entitiesPaths as $entityPath) {
            $currentFile = $pathModel . $entityPath;
            if (file_exists($currentFile)) {
                unlink($currentFile);
            }
        }

        return parent::delete($id);
    }
}
