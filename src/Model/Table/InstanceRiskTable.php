<?php
/**
 * @link      https://github.com/monarc-project for the canonical source repository
 * @copyright Copyright (c) 2016-2019  SMILE GIE Securitymadein.lu - Licensed under GNU Affero GPL v3
 * @license   MONARC is licensed under GNU Affero General Public License version 3
 */

namespace Monarc\Core\Model\Table;

use Monarc\Core\Model\Entity\AbstractEntity;

/**
 * Class InstanceRiskTable
 * @package Monarc\Core\Model\Table
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

    protected function getContextLanguage($anrId, $context = AbstractEntity::BACK_OFFICE)
    {
        if($context == AbstractEntity::BACK_OFFICE){
            $user = $this->getConnectedUser();
            $l = $user->getLanguage();
        }else{
            $anr = new \Monarc\FrontOffice\Model\Entity\Anr();
            $anr->setDbAdapter($this->getDb());
            $anr->set('id', $anrId);
            $anr = $this->getDb()->fetch($anr);
            if (!$anr) {
                throw new \Monarc\Core\Exception\Exception('Entity does not exist', 412);
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
    public function getCsvRisks($anrId, $instanceId = null, $params, $translate, $context = AbstractEntity::BACK_OFFICE)
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
                  elseif ($k == 'c_risk' && $risk[c_risk_enabled] == '0') {
                    $array_values[] = null;
                  }
                  elseif ($k == 'i_risk' && $risk[i_risk_enabled] == '0') {
                    $array_values[] = null;
                  }
                  elseif ($k == 'd_risk' && $risk[d_risk_enabled] == '0') {
                    $array_values[] = null;
                  }
                  elseif ($risk[$k] == '-1'){
                    $array_values[] = null;
                  }
                  else {
                    $array_values[] = $risk[$k];
                  }
                }
                $output .= '"';
                $search = ['"',"\n"];
                $replace = ["'",' '];
                $output .= implode('","', str_replace($search, $replace, $array_values));
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
     * @throws \Monarc\Core\Exception\Exception
     */
    public function getFilteredInstancesRisks($anrId, $instanceId = null, $params = [], $context = AbstractEntity::BACK_OFFICE)
    {
        $params['order'] = isset($params['order']) ? $params['order'] : 'maxRisk';

        if (!empty($instanceId)) {
            if($context == AbstractEntity::BACK_OFFICE){
                $instance = new \Monarc\Core\Model\Entity\Instance();
            }else{
                $instance = new \Monarc\FrontOffice\Model\Entity\Instance();
            }
            $instance->setDbAdapter($this->getDb());
            $instance->set('id', $instanceId);
            $instance = $this->getDb()->fetch($instance);
            if (!$instance) {
                throw new \Monarc\Core\Exception\Exception('Entity does not exist', 412);
            }
            if ($instance->get('anr')->get('id') != $anrId) {
                throw new \Monarc\Core\Exception\Exception('Anr ids differents', 412);
            }
        }
        $l = $this->getContextLanguage($anrId, $context);
        $arraySelect = [
            'o.uuid as oid',
            'ir.id as id',
            'i.id as instance',
            'a.uuid as amv',
            'ass.uuid as asset',
            'ass.label' . $l . ' as assetLabel' . $l . '',
            'ass.description' . $l . ' as assetDescription' . $l . '',
            't.uuid as threat',
            't.code as threatCode',
            't.label' . $l . ' as threatLabel' . $l . '',
            't.description' . $l . ' as threatDescription' . $l . '',
            'ir.threat_rate as threatRate',
            'v.uuid as vulnerability',
            'v.code as vulnCode',
            'v.label' . $l . ' as vulnLabel' . $l . '',
            'v.description' . $l . ' as vulnDescription' . $l . '',
            'ir.vulnerability_rate as vulnerabilityRate',
            'ir.`specific` as `specific`',
            'ir.reduction_amount as reductionAmount',
            'i.c as c_impact',
            'ir.risk_c as c_risk',
            't.c as c_risk_enabled',
            'i.i as i_impact',
            'ir.risk_i as i_risk',
            't.i as i_risk_enabled',
            'i.d as d_impact',
            'ir.risk_d as d_risk',
            't.a as d_risk_enabled',
            'ir.cache_targeted_risk as target_risk',
            'ir.cache_max_risk as max_risk',
            'ir.comment as comment',
            'o.scope as scope',
            'ir.kind_of_measure as kindOfMeasure',
            'IF(ir.kind_of_measure IS NULL OR ir.kind_of_measure = ' . \Monarc\Core\Model\Entity\InstanceRiskSuperClass::KIND_NOT_TREATED . ', false, true) as t',
            'ir.threat_id as tid',
            'ir.vulnerability_id as vid',
            'i.name' . $l . ' as instanceName' . $l . '',
        ];

        //TO DO : if we want to keep the specific models, make an if on it
        if($context == AbstractEntity::BACK_OFFICE){
          $sql = "
              SELECT      " . implode(',', $arraySelect) . "
              FROM        instances_risks AS ir
              INNER JOIN  instances i
              ON          ir.instance_id = i.id
              LEFT JOIN   amvs AS a
              ON          ir.amv_id = a.uuid
              INNER JOIN  threats AS t
              ON          ir.threat_id = t.uuid
              INNER JOIN  vulnerabilities AS v
              ON          ir.vulnerability_id = v.uuid
              LEFT JOIN   assets AS ass
              ON          ir.asset_id = ass.uuid
              INNER JOIN  objects AS o
              ON          i.object_id = o.uuid
              WHERE       ir.cache_max_risk >= -1";
        }else{
        $sql = "
            SELECT      " . implode(',', $arraySelect) . "
            FROM        instances_risks AS ir
            INNER JOIN  instances i
            ON          ir.instance_id = i.id
            LEFT JOIN   amvs AS a
            ON          ir.amv_id = a.uuid
            and         ir.anr_id = a.anr_id
            INNER JOIN  threats AS t
            ON          ir.threat_id = t.uuid
            and         ir.anr_id = t.anr_id
            INNER JOIN  vulnerabilities AS v
            ON          ir.vulnerability_id = v.uuid
            and         ir.anr_id = v.anr_id
            LEFT JOIN   assets AS ass
            ON          ir.asset_id = ass.uuid
            and         ir.anr_id = ass.anr_id
            INNER JOIN  objects AS o
            ON          i.object_id = o.uuid
            and         i.anr_id = o.anr_id
            WHERE       ir.cache_max_risk >= -1
            AND         ir.anr_id = :anrid ";
          }
        $queryParams = [
            ':anrid' => $anrId,
        ];
        $typeParams = [];
        // Find instance(s) id
        if (empty($instance)) {
            // On prend toutes les instances, on est sur l'anr
        } elseif ($instance->get('asset') && $instance->get('asset')->get('type') == \Monarc\Core\Model\Entity\AssetSuperClass::TYPE_PRIMARY) {
            $instanceIds = [];
            $instanceIds[$instance->get('id')] = $instance->get('id');

            if ($context == AbstractEntity::BACK_OFFICE) {
                // TODO: this woun't work for BackOffice, we need to refactor this calls and inject the object.
                $instanceTable = new InstanceTable($this->getDb());
            } else {
                $instanceTable = new \Monarc\FrontOffice\Model\Table\InstanceTable($this->getDb());
            }

            $instanceTable->initTree($instance);
            $temp = $instance->parameters['children'] ?? [];
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

        // FILTER: amvs ==
        if (isset($params['amvs'])) {
          if (!is_array($params['amvs'])) {
            $params['amvs'] = explode(',', substr($params['amvs'],1,-1));
          }
          $sql .= " AND a.uuid IN (:amvIds) ";
          $queryParams[':amvIds'] = $params['amvs'];
          $typeParams[':amvIds'] = \Doctrine\DBAL\Connection::PARAM_INT_ARRAY;
        }
        // FILTER: kind_of_measure ==
        if (isset($params['kindOfMeasure'])) {
            if ($params['kindOfMeasure'] == \Monarc\Core\Model\Entity\InstanceRiskSuperClass::KIND_NOT_TREATED) {
                $sql .= " AND (ir.kind_of_measure IS NULL OR ir.kind_of_measure = :kom) ";
                $queryParams[':kom'] = \Monarc\Core\Model\Entity\InstanceRiskSuperClass::KIND_NOT_TREATED;
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
                'i.name' . $l . '',
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
                $sql .= " i.name$l ";
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
            $sql .= " , i.name$l ASC ";
        }
        $sql .= " , t.code ASC , v.code ASC ";

        $res = $this->getDb()->getEntityManager()->getConnection()
            ->fetchAll($sql, $queryParams, $typeParams);
        $lst = [];
        foreach($res as $r){
            // GROUP BY if scope = GLOBAL
            if($r['scope'] == \Monarc\Core\Model\Entity\ObjectSuperClass::SCOPE_GLOBAL){
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
