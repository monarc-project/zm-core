<?php
/**
 * @link      https://github.com/monarc-project for the canonical source repository
 * @copyright Copyright (c) 2016-2020 SMILE GIE Securitymadein.lu - Licensed under GNU Affero GPL v3
 * @license   MONARC is licensed under GNU Affero General Public License version 3
 */

namespace Monarc\Core\Service;

use Doctrine\ORM\Mapping\MappingException;
use Doctrine\ORM\Query\QueryException;
use Monarc\Core\Exception\Exception;
use Monarc\Core\Model\Entity\Anr;
use Monarc\Core\Model\Entity\AnrSuperClass;
use Monarc\Core\Model\Entity\ObjectCategorySuperClass;
use Monarc\Core\Model\Entity\ObjectSuperClass;
use Monarc\Core\Model\Table\MonarcObjectTable;
use Monarc\Core\Model\Table\ObjectCategoryTable;

/**
 * Object Service Export
 *
 * Class ObjectExportService
 * @package Monarc\Core\Service
 */
class ObjectExportService extends AbstractService
{
    protected $assetExportService;
    protected $objectObjectService;
    protected $categoryTable;
    protected $assetService;
    protected $anrObjectCategoryTable;
    protected $rolfTagTable;
    protected $rolfRiskTable;
    protected $measureTable;
    /** @var  ConfigService */
    protected $configService;

    /**
     * Generates an array to export into a filename
     * @param int $id The object to export
     * @param bool $withEval
     * @param string $filename Reference to the string holding the filename
     * @return array The data
     * @throws Exception If the object is erroneous
     */
    public function generateExportArray($id, $anr = null, $withEval = false, &$filename = "")
    {
        if (empty($id)) {
            throw new Exception('Object to export is required', 412);
        }
        try {
            $entity = $this->get('table')->getEntity(['uuid' => $id, 'anr' => $anr]);
        } catch (QueryException | MappingException $e) {
            $entity = $this->get('table')->getEntity($id);
        }

        if (!$entity) {
            throw new Exception('Entity `id` not found.');
        }

        $objectObj = [
            'uuid' => 'uuid',
            'mode' => 'mode',
            'scope' => 'scope',
            'name1' => 'name1',
            'name2' => 'name2',
            'name3' => 'name3',
            'name4' => 'name4',
            'label1' => 'label1',
            'label2' => 'label2',
            'label3' => 'label3',
            'label4' => 'label4',
            'disponibility' => 'disponibility',
            'position' => 'position',
        ];

        $return = [
            'type' => 'object',
            'object' => $entity->getJsonArray($objectObj),
            'version' => $this->getVersion(),
            'monarc_version' => $this->configService->getAppVersion()['appVersion'],
        ];
        $filename = preg_replace("/[^a-z0-9\._-]+/i", '', $entity->get('name' . $this->getLanguage()));

        // Recovery categories
        $categ = $entity->get('category');
        if (!empty($categ)) {
            $categObj = [
                'id' => 'id',
                'label1' => 'label1',
                'label2' => 'label2',
                'label3' => 'label3',
                'label4' => 'label4',
            ];

            while (!empty($categ)) {
                $categFormat = $categ->getJsonArray($categObj);
                if (empty($return['object']['category'])) {
                    $return['object']['category'] = $categFormat['id'];
                }
                $return['categories'][$categFormat['id']] = $categFormat;
                $return['categories'][$categFormat['id']]['parent'] = null;

                $parent = $categ->get('parent');
                if (!empty($parent)) {
                    $parentForm = $categ->get('parent')->getJsonArray(['id' => 'id']);
                    $return['categories'][$categFormat['id']]['parent'] = $parentForm['id'];
                    $categ = $parent;
                } else {
                    $categ = null;
                }
            }
        } else {
            $return['object']['category'] = null;
            $return['categories'] = null;
        }

        // Recovery asset
        $asset = $entity->get('asset');
        $return['asset'] = null;
        $return['object']['asset'] = null;
        if (!empty($asset)) {
            $asset = $asset->getJsonArray(['uuid']);
            $return['object']['asset'] = $asset['uuid'];
            $return['asset'] = $this->get('assetExportService')->generateExportArray($asset['uuid'], $anr, $withEval);
        }

        // Recovery of operational risks
        $rolfTag = $entity->get('rolfTag');
        $return['object']['rolfTag'] = null;
        if (!empty($rolfTag)) {
            $risks = $rolfTag->get('risks');
            $rolfTag = $rolfTag->getJsonArray(['id', 'code', 'label1', 'label2', 'label3', 'label4']);
            $return['object']['rolfTag'] = $rolfTag['id'];
            $return['rolfTags'][$rolfTag['id']] = $rolfTag;
            $return['rolfTags'][$rolfTag['id']]['risks'] = [];
            if (!empty($risks)) {
                foreach ($risks as $r) {
                    $risk = $r->getJsonArray(['id', 'code', 'label1', 'label2', 'label3', 'label4', 'description1', 'description2', 'description3', 'description4']);
                    $risk['measures'] = array();
                    foreach ($r->measures as $m) {
                        $risk['measures'][] = $m->getUuid();
                    }
                    $return['rolfTags'][$rolfTag['id']]['risks'][$risk['id']] = $risk['id'];
                    $return['rolfRisks'][$risk['id']] = $risk;
                }
            }
        }

        // Recovery children(s)
        $children = array_reverse($this->get('objectObjectService')->getChildren(
            $entity->getUuid(),
            is_null($entity->get('anr'))?null:$entity->get('anr')->get('id')
        )); // Le tri de cette fonction est "position DESC"
        $return['children'] = null;
        if (!empty($children)) {
            $return['children'] = [];
            $place = 1;
            foreach ($children as $child) {
                $return['children'][$child->getChild()->getUuid()] = $this->generateExportArray(
                    $child->getChild()->getUuid(),
                    $anr,
                    $withEval
                );
                $return['children'][$child->getChild()->getUuid()]['object']['position'] = $place;
                $place ++;
            }
        }

        return $return;
    }
}
