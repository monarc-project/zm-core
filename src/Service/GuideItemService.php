<?php
/**
 * @link      https://github.com/monarc-project for the canonical source repository
 * @copyright Copyright (c) 2016-2020 SMILE GIE Securitymadein.lu - Licensed under GNU Affero GPL v3
 * @license   MONARC is licensed under GNU Affero General Public License version 3
 */

namespace Monarc\Core\Service;

use Monarc\Core\Model\Entity\GuideItem;

/**
 * Guide Item Service
 *
 * Class GuideItemService
 * @package Monarc\Core\Service
 */
class GuideItemService extends AbstractService
{
    protected $guideTable;
    protected $dependencies = ['guide'];

    /**
     * @inheritdoc
     */
    public function create($data, $last = true)
    {
        $dependencies = (property_exists($this, 'dependencies')) ? $this->dependencies : [];

        /** @var GuideItem $guideItem */
        $guideItem = $this->get('entity');

        $guideItem->exchangeArray($data);

        $this->setDependencies($guideItem, $dependencies);

        $guideItem->setCreator(
            $this->getConnectedUser()->getFirstname() . ' ' . $this->getConnectedUser()->getLastname()
        );

        return $this->get('table')->save($guideItem);
    }

    /**
     * @inheritdoc
     */
    public function update($id, $data)
    {
        /** @var GuideItem $guideItem */
        $guideItem = $this->get('table')->getEntity($id);
        $guideItem->setDbAdapter($this->table->getDb());
        $guideItem->exchangeArray($data);

        $dependencies = property_exists($this, 'dependencies') ? $this->dependencies : [];
        $this->setDependencies($guideItem, $dependencies);

        $guideItem->setUpdater(
            $this->getConnectedUser()->getFirstname() . ' ' . $this->getConnectedUser()->getLastname()
        );

        return $this->get('table')->save($guideItem);
    }

    /**
     * @inheritdoc
     */
    public function delete($id)
    {
        $this->get('table')->delete($id);
    }
}
