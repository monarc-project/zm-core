<?php
/**
 * @link      https://github.com/monarc-project for the canonical source repository
 * @copyright Copyright (c) 2016-2020 SMILE GIE Securitymadein.lu - Licensed under GNU Affero GPL v3
 * @license   MONARC is licensed under GNU Affero General Public License version 3
 */

namespace Monarc\Core\Service;

use Doctrine\ORM\Mapping\MappingException;
use Doctrine\ORM\Query\QueryException;
use Doctrine\DBAL\Exception\DriverException;
use Monarc\Core\Exception\Exception;
use Monarc\Core\Model\Entity\AmvSuperClass;
use Monarc\Core\Model\Entity\AnrSuperClass;

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
     *
     * @param int $id The asset ID
     * @param AnrSuperClass $anr
     * @param bool $withEval
     * @param string $filename The filename to put into
     *
     * @return array The exported data
     * @throws Exception
     */
    public function generateExportArray($id, $anr = null, $withEval = false, &$filename = '')
    {
        if (empty($id)) {
            throw new Exception('Asset to export is required', 412);
        }

        try {
            $entity = $this->get('table')->getEntity(['uuid' => $id, 'anr' => $anr]);
        } catch (MappingException | QueryException $e) {
            $entity = $this->get('table')->getEntity($id);
        }

        if (empty($entity)) {
            throw new Exception('Asset not found', 412);
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
            'threats' => [],
            'themes' => [],
            'vuls' => [],
            'measures' => [],
        ];
        $amvService = $this->get('amvService');
        $amvTable = $amvService->get('table');
        $anrId = $entity->get('anr');
        try {
            $amvResults = $amvTable->getEntityByFields(['asset' => ['uuid' => $entity->getUuid(), 'anr' => $anrId]]);
        } catch (QueryException | MappingException | DriverException $e) {
            $amvResults = $amvTable->getEntityByFields(['asset' => $entity->getUuid(), 'anr' => $anrId]);
        }

        /** @var AmvSuperClass $amv */
        foreach ($amvResults as $amv) {
            list($return['amvs'][$amv->getUuid()],
                $threats,
                $vulnerabilities,
                $themes,
                $measures) = $amvService->generateExportArray($amv, $anrId, $withEval);
            $return['threats'] += $threats;
            $return['themes'] += $themes;
            $return['vuls'] += $vulnerabilities;
            $return['measures'] += $measures;
        }

        return $return;
    }

    /**
     * Generates the array to be exported into a file
     *
     * @param int $id The asset ID
     * @param AnrSuperClass $anr
     * @param string $filename The filename to put into
     *
     * @return array The exported data
     * @throws Exception
     */
    public function generateExportMospArray($id, $anr = null, $languageCode, &$filename = '')
    {
        $language = $this->getLanguage();
        if (empty($id)) {
            throw new Exception('Asset to export is required', 412);
        }

        try {
            $entity = $this->get('table')->getEntity(['uuid' => $id, 'anr' => $anr]);
        } catch (MappingException | QueryException $e) {
            $entity = $this->get('table')->getEntity($id);
        }

        if (empty($entity)) {
            throw new Exception('Asset not found', 412);
        }

        $filename = preg_replace("/[^a-z0-9\._-]+/i", '', $entity->get('code'));

        $assetObj = [
            'uuid' => 'uuid',
            'label' => 'label' . $language,
            'description' => 'description' . $language,
            'type' => 'type',
            'code' => 'code',
        ];
        $return = [
            'asset' => $entity->getJsonArray($assetObj),
            'amvs' => [],
            'threats' => [],
            'vuls' => [],
            'measures' => [],
        ];

        $return['asset']['label'] = $return['asset']['label' . $language];
        $return['asset']['description'] = $return['asset']['description' . $language];
        $return['asset']['type'] = $return['asset']['type'] == 1 ? 'Primary' : 'Secondary';
        $return['asset']['language'] = $languageCode;
        unset($return['asset']['label' . $language]);
        unset($return['asset']['description' . $language]);

        $amvService = $this->get('amvService');
        $amvTable = $amvService->get('table');
        $anrId = $entity->get('anr');
        try {
            $amvResults = $amvTable->getEntityByFields(['asset' => ['uuid' => $entity->getUuid(), 'anr' => $anrId]]);
        } catch (QueryException | MappingException | DriverException $e) {
            $amvResults = $amvTable->getEntityByFields(['asset' => $entity->getUuid(), 'anr' => $anrId]);
        }

        /** @var AmvSuperClass $amv */
        foreach ($amvResults as $amv) {
            list($return['amvs'][$amv->getUuid()],
                $threats,
                $vulnerabilities,
                $measures) = $amvService->generateExportMospArray($amv, $anrId, $languageCode);
            $return['threats'] += $threats;
            $return['vuls'] += $vulnerabilities;
            $return['measures'] += $measures;
        }
        $return['amvs'] = array_values($return['amvs']);
        $return['threats'] = array_values($return['threats']);
        $return['vuls'] = array_values($return['vuls']);
        $return['measures'] = array_values($return['measures']);

        return $return;
    }
}
