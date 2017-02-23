<?php
/**
 * @link      https://github.com/CASES-LU for the canonical source repository
 * @copyright Copyright (c) Cases is a registered trademark of SECURITYMADEIN.LU
 * @license   MyCases is licensed under the GNU Affero GPL v3 - See license.txt for more information
 */

namespace MonarcCore\Service;

/**
 * Asset Service Export
 *
 * Class ObjectExportService
 * @package MonarcCore\Service
 */
class AssetExportService extends AbstractService
{
    protected $amvService;

    /**
     * Generates the array to be exported into a file
     * @param int $id The asset ID
     * @param string $filename The filename to put into
     * @return array The exported data
     * @throws \Exception
     */
    public function generateExportArray($id, &$filename = "")
    {
        if (empty($id)) {
            throw new \Exception('Asset to export is required', 412);
        }

        $entity = $this->get('table')->getEntity($id);
        if (empty($entity)) {
            throw new \Exception('Asset not found', 412);
        }

        $filename = preg_replace("/[^a-z0-9\._-]+/i", '', $entity->get('code'));

        $assetObj = [
            'id' => 'id',
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

        $amvResults = $amvTable->getRepository()
            ->createQueryBuilder('t')
            ->where("t.asset = :asset")
            ->setParameter(':asset', $entity->get('id'));
        $anrId = $entity->get('anr');
        if (empty($anrId)) {
            $amvResults = $amvResults->andWhere('t.anr IS NULL');
        } else {
            $anrId = $anrId->get('id');
            $amvResults = $amvResults->andWhere('t.anr = :anr')->setParameter(':anr', $anrId);
        }
        $amvResults = $amvResults->getQuery()->getResult();

        foreach ($amvResults as $amv) {
            list(
                $return['amvs'][$amv->get('id')],
                $threats,
                $vulns,
                $themes,
                $measures) = $amvService->generateExportArray($amv);
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
        }

        return $return;
    }
}