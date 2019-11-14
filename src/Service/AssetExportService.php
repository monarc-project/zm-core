<?php
/**
 * @link      https://github.com/monarc-project for the canonical source repository
 * @copyright Copyright (c) 2016-2019  SMILE GIE Securitymadein.lu - Licensed under GNU Affero GPL v3
 * @license   MONARC is licensed under GNU Affero General Public License version 3
 */

namespace Monarc\Core\Service;

use Doctrine\ORM\Query\QueryException;
use Doctrine\ORM\Query\MappingException;
use Doctrine\DBAL\Exception\DriverException;

/**
 * Asset Service Export
 *
 * Class ObjectExportService
 * @package Monarc\Core\Service
 */
class AssetExportService extends AbstractService
{
    protected $amvService;

    /**
     * Generates the array to be exported into a file
     * @param int $id The asset ID
     * @param string $filename The filename to put into
     * @return array The exported data
     * @throws \Monarc\Core\Exception\Exception
     */
    public function generateExportArray($id, $anr = null,&$filename = "")
    {
        if (empty($id)) {
            throw new \Monarc\Core\Exception\Exception('Asset to export is required', 412);
        }

        try{
          $entity = $this->get('table')->getEntity(['uuid' => $id, "anr" => $anr]);
        }
        catch(MappingException $e){
          $entity = $this->get('table')->getEntity($id);
        } catch (QueryException $e) {
             $entity = $this->get('table')->getEntity($id);
        }

        if (empty($entity)) {
            throw new \Monarc\Core\Exception\Exception('Asset not found', 412);
        }

        $filename = preg_replace("/[^a-z0-9\._-]+/i", '', $entity->get('code'));

        $assetObj = [
            'uuid' => 'uuid',
            'label1' => 'label1',
            'label2' => 'label2',
            'label3' => 'label3',
            'label4' => 'label4',
            'description1' => 'description1',
            'description2' => 'description2',
            'description3' => 'description3',
            'description4' => 'description4',
            'status' => 'status',
            'mode' => 'mode',
            'type' => 'type',
            'code' => 'code',
        ];
        $return = [
            'type' => 'asset',
            'asset' => $entity->getJsonArray($assetObj),
            'version' => $this->getVersion(),
            'amvs' => [],
        ];
        $amvService = $this->get('amvService');
        $amvTable = $amvService->get('table');
        $anrId = $entity->get('anr');
        try{
          $amvResults = $amvTable->getEntityByFields(['asset' => ['uuid' => $entity->getUuid()->toString(), 'anr' => $anrId]]);
        }catch(QueryException $e){
          $amvResults = $amvTable->getEntityByFields(['asset' => $entity->getUuid()->toString(), 'anr' => $anrId]);
      } catch(MappingException $e) {
          $amvResults = $amvTable->getEntityByFields(['asset' => $entity->getUuid()->toString(), 'anr' => $anrId]);
      }catch(DriverException $e) {
          $amvResults = $amvTable->getEntityByFields(['asset' => $entity->getUuid()->toString(), 'anr' => $anrId]);
      }


        foreach ($amvResults as $amv) {
            list(
                $return['amvs'][$amv->get('uuid')->toString()],
                $threats,
                $vulns,
                $themes,
                $measures,
                $soacategories) = $amvService->generateExportArray($amv,$anrId);
            if (empty($return['threats'])) {
                $return['threats'] = $threats;
            } else {
                $return['threats'] += $threats;
            }
            if (empty($return['themes'])) {
                $return['themes'] = $themes;
            } else {
                $return['themes'] += $themes;
            }
            if (empty($return['vuls'])) {
                $return['vuls'] = $vulns;
            } else {
                $return['vuls'] += $vulns;
            }
            if (empty($return['measures'])) {
                $return['measures'] = $measures;
            } else {
                $return['measures'] += $measures;
            }
            if (empty($return['soacategories'])) {
                $return['soacategories'] = $soacategories;
            } else {
                $return['soacategories'] += $soacategories;
            }
        }

        return $return;
    }
}
