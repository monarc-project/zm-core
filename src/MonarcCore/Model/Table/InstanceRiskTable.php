<?php
/**
 * @link      https://github.com/monarc-project for the canonical source repository
 * @copyright Copyright (c) Cases is a registered trademark of SECURITYMADEIN.LU
 * @license   MyCases is licensed under the GNU Affero GPL v3 - See license.txt for more information
 */

namespace MonarcCore\Model\Table;

/**
 * Class InstanceRiskTable
 * @package MonarcCore\Model\Table
 */
class InstanceRiskTable extends AbstractEntityTable
{
    /**
     * Get Instances Risks
     *
     * @param $anrId
     * @param $instancesIds
     * @return array
     */
    public function getInstancesRisks($anrId, $instancesIds)
    {
        $qb = $this->getRepository()->createQueryBuilder('ir');

        if (empty($instancesIds)) {
            $instancesIds[] = 0;
        }

        return $qb
            ->select()
            ->where($qb->expr()->in('ir.instance', $instancesIds))
            ->andWhere('ir.anr = :anr ')
            ->setParameter(':anr', $anrId)
            ->getQuery()
            ->getResult();
    }

    protected function getContextLanguage($anrId, $context = \MonarcCore\Model\Entity\AbstractEntity::BACK_OFFICE)
    {
        if($context == \MonarcCore\Model\Entity\AbstractEntity::BACK_OFFICE){
            $user = $this->getConnectedUser();
            $l = $user['language'];
        }else{
            $anr = new \MonarcFO\Model\Entity\Anr();
            $anr->setDbAdapter($this->getDb());
            $anr->set('id', $anrId);
            $anr = $this->getDb()->fetch($anr);
            if (!$anr) {
                throw new \MonarcCore\Exception\Exception('Entity does not exist', 412);
            }
            $l = $anr->get('language');
        }
        return $l;
    }

    /**
     * Get Csv Risks
     *
     * @param $anrId
     * @param null $instanceId
     * @param $params
     * @param TranslateService $translate
     * @param string $context
     * @return string
     */
    public function getCsvRisks($anrId, $instanceId = null, $params, $translate, $context = \MonarcCore\Model\Entity\AbstractEntity::BACK_OFFICE)
    {
        $risks = $this->getFilteredInstancesRisks($anrId, $instanceId, $params, $context);

        $lang = $this->getContextLanguage($anrId,$context);

        $output = '';
        if (count($risks) > 0) {
            $fields = [
                'instanceName' . $lang => $translate->translate('Asset', $lang),
                'c_impact' => $translate->translate('C Impact', $lang),
                'i_impact' => $translate->translate('I Impact', $lang),
                'd_impact' => $translate->translate('A Impact', $lang),
                'threatLabel' . $lang => $translate->translate('Threat', $lang),
                'threatRate' => $translate->translate('Prob.', $lang),
                'vulnLabel' . $lang => $translate->translate('Vulnerability', $lang),
                'comment' => $translate->translate('Existing controls', $lang),
                'vulnerabilityRate' => $translate->translate('Qualif.', $lang),
                'c_risk' => $translate->translate('Current risk', $lang). " C",
                'i_risk' => $translate->translate('Current risk', $lang) . " I",
                'd_risk' => $translate->translate('Current risk', $lang) . " " . $translate->translate('A', $lang),
                'kindOfMeasure' => $translate->translate('Treatment', $lang),
                'target_risk' => $translate->translate('Residual risk', $lang),

            ];

            // TODO: why don't use "fputcsv" ?

            // Fill in the header
            $output .= implode(',', array_values($fields)) . "\n";

            // Fill in the lines then
            foreach ($risks as $risk) {
              foreach ($fields as $k => $v) {
                  if ($k == 'kindOfMeasure'){
                    switch ($risk[$k]) {
                      case 1:
                          $array_values[] = $translate->translate('Reduction', $lang);
                          break;
                      case 2:
                          $array_values[] = $translate->translate('Denied', $lang);;
                          break;
                      case 3:
                          $array_values[] = $translate->translate('Accepted', $lang);
                          break;
                      case 4:
                          $array_values[] = $translate->translate('Shared', $lang);
                          break;
                      default:
                        $array_values[] = $translate->translate('Not treated', $lang);
                    }
                  }
                  elseif ($risk[$k] == '-1'){
                    $array_values[] = null;
                  }
                  else {
                    $array_values[] = $risk[$k];
                  }
                }
                $output .= '"';
                $output .= implode('","', str_replace('"', '\"', $array_values));
                $output .= "\"\r\n";
                $array_values = null;
            }
        }

        return $output;
    }

    /**
     * Get Instances Risks
     *
     * @param $anrId
     * @param null $instanceId
     * @param array $params
     * @param string $context
     * @return int
     * @throws \MonarcCore\Exception\Exception
     */
    public function getFilteredInstancesRisks($anrId, $instanceId = null, $params = [], $context = \MonarcCore\Model\Entity\AbstractEntity::BACK_OFFICE)
    {
        $params['order'] = isset($params['order']) ? $params['order'] : 'maxRisk';

        if (!empty($instanceId)) {
            if($context == \MonarcCore\Model\Entity\AbstractEntity::BACK_OFFICE){
                $instance = new \MonarcCore\Model\Entity\Instance();
            }else{
                $instance = new \MonarcFO\Model\Entity\Instance();
            }
            $instance->setDbAdapter($this->getDb());
            $instance->set('id', $instanceId);
            $instance = $this->getDb()->fetch($instance);
            if (!$instance) {
                throw new \MonarcCore\Exception\Exception('Entity does not exist', 412);
            }
            if ($instance->get('anr')->get('id') != $anrId) {
                throw new \MonarcCore\Exception\Exception('Anr ids differents', 412);
            }
        }
        $l = $this->getContextLanguage($anrId,$context);
        $arraySelect = [
            'o.id as oid',
            'ir.id as id',
            'i.id as instance',
            'a.id as amv',
            'ass.id as asset',
            'ass.label_translation_id' . ' as assetLabel' . $l,
            'ass.description_translation_id' . ' as assetDescription' . $l ,
            't.id as threat',
            't.code as threatCode',
            't.label_translation_id' . ' as threatLabel' . $l . '',
            't.description_translation_id' . ' as threatDescription' . $l,
            'ir.threat_rate as threatRate',
            'v.id as vulnerability',
            'v.code as vulnCode',
            'v.label_translation_id' . ' as vulnLabel' . $l,
            'v.description_translation_id' . ' as vulnDescription' . $l,
            'ir.vulnerability_rate as vulnerabilityRate',
            'ir.`specific` as `specific`',
            'ir.reduction_amount as reductionAmount',
            'i.confidentiality as c_impact',
            'ir.risk_c as c_risk',
            't.confidentiality as c_risk_enabled',
            'i.integrity as i_impact',
            'ir.risk_i as i_risk',
            't.integrity as i_risk_enabled',
            'i.availability as d_impact',
            'ir.risk_d as d_risk',
            't.availability as d_risk_enabled',
            'ir.cache_targeted_risk as target_risk',
            'ir.cache_max_risk as max_risk',
            'ir.comment as comment',
            'CONCAT(m1.code, \' - \', m1.description_translation_id) as measure1',
            'CONCAT(m2.code, \' - \', m2.description_translation_id) as measure2',
            'CONCAT(m3.code, \' - \', m3.description_translation_id) as measure3',
            'o.scope as scope',
            'ir.kind_of_measure as kindOfMeasure',
            'IF(ir.kind_of_measure IS NULL OR ir.kind_of_measure = ' . \MonarcCore\Model\Entity\InstanceRiskSuperClass::KIND_NOT_TREATED . ', false, true) as t',
            'ir.threat_id as tid',
            'ir.vulnerability_id as vid',
            'i.name_translation_id as instanceName' . $l,
        ];

        $sql = "
            SELECT      " . implode(',', $arraySelect) . "
            FROM        instances_risks AS ir
            INNER JOIN  instances i
            ON          ir.instance_id = i.id
            LEFT JOIN   amvs AS a
            ON          ir.amv_id = a.id
            INNER JOIN  threats AS t
            ON          ir.threat_id = t.id
            INNER JOIN  vulnerabilities AS v
            ON          ir.vulnerability_id = v.id
            LEFT JOIN   assets AS ass
            ON          ir.asset_id = ass.id
            INNER JOIN  objects AS o
            ON          i.object_id = o.id
            LEFT JOIN   measures as m1
            ON          a.measure1_id = m1.id
            LEFT JOIN   measures as m2
            ON          a.measure2_id = m2.id
            LEFT JOIN   measures as m3
            ON          a.measure3_id = m3.id
            WHERE       ir.cache_max_risk >= -1
            AND         ir.anr_id = :anrid ";
        $queryParams = [
            ':anrid' => $anrId,
            //':anrid2' => $anrId,
        ];
        $typeParams = [];
        // Find instance(s) id
        if (empty($instance)) {
            // On prend toutes les instances, on est sur l'anr
        } elseif ($instance->get('asset') && $instance->get('asset')->get('type') == \MonarcCore\Model\Entity\AssetSuperClass::TYPE_PRIMARY) {
            $instanceIds = [];
            $instanceIds[$instance->get('id')] = $instance->get('id');

            if($context == \MonarcCore\Model\Entity\AbstractEntity::BACK_OFFICE){
                $instanceTable = new \MonarcCore\Model\Table\InstanceTable($this->getDb());
            }else{
                $instanceTable = new \MonarcFO\Model\Table\InstanceTable($this->getDb());
            }
            $instanceTable->setConnectedUser($this->getConnectedUser());

            $instanceTable->initTree($instance);
            $temp = isset($instance->parameters['children']) ? $instance->parameters['children'] : [];
            while (!empty($temp)) {
                $sub = array_shift($temp);
                $instanceIds[$sub->get('id')] = $sub->get('id');
                if (!empty($sub->parameters['children'])) {
                    foreach ($sub->parameters['children'] as $subsub) {
                        array_unshift($temp, $subsub);
                    }
                }
            }

            $sql .= " AND i.id IN (:ids) ";
            $queryParams[':ids'] = $instanceIds;
            $typeParams[':ids'] = \Doctrine\DBAL\Connection::PARAM_INT_ARRAY;
        } else {
            $sql .= " AND i.id = :id ";
            $queryParams[':id'] = $instance->get('id');
        }

        // FILTER: kind_of_measure ==
        if (isset($params['kindOfMeasure'])) {
            if ($params['kindOfMeasure'] == \MonarcCore\Model\Entity\InstanceRiskSuperClass::KIND_NOT_TREATED) {
                $sql .= " AND (ir.kind_of_measure IS NULL OR ir.kind_of_measure = :kom) ";
                $queryParams[':kom'] = \MonarcCore\Model\Entity\InstanceRiskSuperClass::KIND_NOT_TREATED;
            } else {
                $sql .= " AND ir.kind_of_measure = :kom ";
                $queryParams[':kom'] = $params['kindOfMeasure'];
            }
        }
        // FILTER: Keywords
        if (!empty($params['keywords'])) {
            $filters = [
                'ass.label' . $l . '',
                //'amv.label'.$l.'',
                't.label' . $l . '',
                'v.label' . $l . '',
                'm1.code',
                'm1.description_translation_id',
                'm2.code',
                'm2.description_translation_id',
                'm3.code',
                'm3.description_translation_id',
                'i.name_translation_id',
                'ir.comment',
            ];
            $orFilter = [];
            foreach ($filters as $f) {
                $k = str_replace('.', '', $f);
                $orFilter[] = $f . " LIKE :" . $k;
                $queryParams[":$k"] = '%' . $params['keywords'] . '%';
            }
            $sql .= " AND (" . implode(' OR ', $orFilter) . ") ";
        }
        // FILTER: cache_max_risk (min)
        if (isset($params['thresholds']) && $params['thresholds'] > 0) {
            $sql .= " AND ir.cache_max_risk > :min ";
            $queryParams[':min'] = $params['thresholds'];
        }

        // ORDER
        $params['order_direction'] = isset($params['order_direction']) && strtolower(trim($params['order_direction'])) != 'asc' ? 'DESC' : 'ASC';
        $sql .= " ORDER BY ";
        switch ($params['order']) {
            case 'instance':
                $sql .= " i.name_translation_id ";
                break;
            case 'auditOrder':
                $sql .= " a.position ";
                break;
            case 'c_impact':
                $sql .= " i.c ";
                break;
            case 'i_impact':
                $sql .= " i.i ";
                break;
            case 'd_impact':
                $sql .= " i.d ";
                break;
            case 'threat':
                $sql .= " t.label$l ";
                break;
            case 'vulnerability':
                $sql .= " v.label$l ";
                break;
            case 'vulnerabilityRate':
                $sql .= " ir.vulnerability_rate ";
                break;
            case 'threatRate':
                $sql .= " ir.threat_rate ";
                break;
            case 'targetRisk':
                $sql .= " ir.cache_targeted_risk ";
                break;
            default:
            case 'maxRisk':
                $sql .= " ir.cache_max_risk ";
                break;
        }
        $sql .= " " . $params['order_direction'] . " ";
        if ($params['order'] != 'instance') {
            $sql .= " , i.name_translation_id ASC ";
        }
        $sql .= " , t.code ASC , v.code ASC ";

        $res = $this->getDb()->getEntityManager()->getConnection()
            ->fetchAll($sql, $queryParams, $typeParams);
        $lst = [];
        foreach($res as $r){
            // GROUP BY if scope = GLOBAL
            if($r['scope'] == \MonarcCore\Model\Entity\ObjectSuperClass::SCOPE_GLOBAL){
                $key = 'o'.$r['oid'].'-'.$r['tid'].'-'.$r['vid'];
                if(!isset($lst[$key]) || $lst[$key]['max_risk'] < $r['max_risk']){
                    $lst[$key] = $r;
                }
            }else{
                $lst['r'.$r['id']] = $r;
            }
        }
        return array_values($lst);
    }
}
