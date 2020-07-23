<?php
/**
 * @link      https://github.com/monarc-project for the canonical source repository
 * @copyright Copyright (c) 2016-2020 SMILE GIE Securitymadein.lu - Licensed under GNU Affero GPL v3
 * @license   MONARC is licensed under GNU Affero General Public License version 3
 */

namespace Monarc\Core\Model\Table;

use Doctrine\DBAL\Connection;
use Doctrine\ORM\EntityNotFoundException;
use Doctrine\ORM\OptimisticLockException;
use Doctrine\ORM\ORMException;
use Monarc\Core\Exception\Exception;
use Monarc\Core\Model\Db;
use Monarc\Core\Model\Entity\AbstractEntity;
use Monarc\Core\Model\Entity\AssetSuperClass;
use Monarc\Core\Model\Entity\Instance;
use Monarc\Core\Model\Entity\InstanceRisk;
use Monarc\Core\Model\Entity\InstanceRiskSuperClass;
use Monarc\Core\Model\Entity\InstanceSuperClass;
use Monarc\Core\Model\Entity\ObjectSuperClass;
use Monarc\Core\Service\ConnectedUserService;
use Monarc\Core\Service\TranslateService;

/**
 * Class InstanceRiskTable
 * @package Monarc\Core\Model\Table
 */
class InstanceRiskTable extends AbstractEntityTable
{
    public function __construct(Db $dbService, ConnectedUserService $connectedUserService)
    {
        parent::__construct($dbService, InstanceRisk::class, $connectedUserService);
    }

    /**
     * TODO: remove the method and pass the value from the calling service.
     */
    protected function getContextLanguage($anrId, $context = AbstractEntity::BACK_OFFICE)
    {
        if ($context === AbstractEntity::BACK_OFFICE) {
            return $this->getConnectedUser()->getLanguage();
        }

        // TODO: perform lang detection to the FO side and pass here IF NEEDED (imho not needed).
        $anr = new \Monarc\FrontOffice\Model\Entity\Anr();
        $anr->setDbAdapter($this->getDb());
        $anr->set('id', $anrId);
        $anr = $this->getDb()->fetch($anr);
        if (!$anr) {
            throw new Exception('Entity does not exist', 412);
        }

        return $anr->get('language');
    }

    /**
     * Get Csv Risks
     *
     * @param $anrId
     * @param null $instanceId
     * @param $params
     * @param TranslateService $translate
     * @param int $language
     * @return string
     */
    public function getCsvRisks($anrId, $instanceId = null, $params, $translate, $context = AbstractEntity::BACK_OFFICE)
    {
        $risks = $this->getFilteredInstancesRisks($anrId, $instanceId, $params, $context);

        $language = $this->getContextLanguage($anrId, $context);

        $output = '';
        if (count($risks) > 0) {
            $fields = [
                'instanceName' . $language => $translate->translate('Asset', $language),
                'c_impact' => $translate->translate('C Impact', $language),
                'i_impact' => $translate->translate('I Impact', $language),
                'd_impact' => $translate->translate('A Impact', $language),
                'threatLabel' . $language => $translate->translate('Threat', $language),
                'threatRate' => $translate->translate('Prob.', $language),
                'vulnLabel' . $language => $translate->translate('Vulnerability', $language),
                'comment' => $translate->translate('Existing controls', $language),
                'vulnerabilityRate' => $translate->translate('Qualif.', $language),
                'c_risk' => $translate->translate('Current risk', $language). " C",
                'i_risk' => $translate->translate('Current risk', $language) . " I",
                'd_risk' => $translate->translate('Current risk', $language) . " " . $translate->translate('A', $language),
                'kindOfMeasure' => $translate->translate('Treatment', $language),
                'target_risk' => $translate->translate('Residual risk', $language),

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
                                $array_values[] = $translate->translate('Reduction', $language);
                                break;
                            case 2:
                                $array_values[] = $translate->translate('Denied', $language);
                                break;
                            case 3:
                                $array_values[] = $translate->translate('Accepted', $language);
                                break;
                            case 4:
                                $array_values[] = $translate->translate('Shared', $language);
                                break;
                            default:
                                $array_values[] = $translate->translate('Not treated', $language);
                        }
                    }
                    elseif ($k == 'c_risk' && $risk['c_risk_enabled'] == '0') {
                        $array_values[] = null;
                    }
                    elseif ($k == 'i_risk' && $risk['i_risk_enabled'] == '0') {
                        $array_values[] = null;
                    }
                    elseif ($k == 'd_risk' && $risk['d_risk_enabled'] == '0') {
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
     * @return array
     * @throws Exception
     */
    public function getFilteredInstancesRisks($anrId, $instanceId = null, $params = [], $context = AbstractEntity::BACK_OFFICE)
    {
        $params['order'] = $params['order'] ?? 'maxRisk';

        $instance = null;
        if (!empty($instanceId)) {
            if ($context == AbstractEntity::BACK_OFFICE) {
                $instance = new Instance();
            } else {
                $instance = new \Monarc\FrontOffice\Model\Entity\Instance();
            }
            $instance->setDbAdapter($this->getDb());
            $instance->set('id', $instanceId);
            $instance = $this->getDb()->fetch($instance);
            if (!$instance) {
                throw new Exception('Entity does not exist', 412);
            }
            if ($instance->get('anr')->get('id') != $anrId) {
                throw new Exception('Anr ids differents', 412);
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
            'IF(ir.kind_of_measure IS NULL OR ir.kind_of_measure = '
                . InstanceRiskSuperClass::KIND_NOT_TREATED . ', false, true) as t',
            'ir.threat_id as tid',
            'ir.vulnerability_id as vid',
            'i.name' . $l . ' as instanceName' . $l . '',
        ];

        $queryParams = [];
        if ($context === AbstractEntity::BACK_OFFICE) {
            $sql = 'SELECT ' . implode(',', $arraySelect) . '
                FROM       instances_risks AS ir
                INNER JOIN instances i
                ON         ir.instance_id = i.id
                LEFT JOIN  amvs AS a
                ON         ir.amv_id = a.uuid
                INNER JOIN threats AS t
                ON         ir.threat_id = t.uuid
                INNER JOIN vulnerabilities AS v
                ON         ir.vulnerability_id = v.uuid
                LEFT JOIN  assets AS ass
                ON         ir.asset_id = ass.uuid
                INNER JOIN objects AS o
                ON         i.object_id = o.uuid
                WHERE      ir.cache_max_risk >= -1';
        } else {
            array_push($arraySelect,'rec.recommendations');
            $sql = 'SELECT ' . implode(',', $arraySelect) . '
                FROM       instances_risks AS ir
                INNER JOIN instances i
                ON         ir.instance_id = i.id
                LEFT JOIN  amvs AS a
                ON         ir.amv_id = a.uuid
                AND        ir.anr_id = a.anr_id
                INNER JOIN threats AS t
                ON         ir.threat_id = t.uuid
                AND        ir.anr_id = t.anr_id
                INNER JOIN vulnerabilities AS v
                ON         ir.vulnerability_id = v.uuid
                AND        ir.anr_id = v.anr_id
                LEFT JOIN  assets AS ass
                ON         ir.asset_id = ass.uuid
                AND        ir.anr_id = ass.anr_id
                INNER JOIN objects AS o
                ON         i.object_id = o.uuid
                AND        i.anr_id = o.anr_id
                LEFT JOIN  (SELECT rr.instance_risk_id, rr.anr_id,
                    GROUP_CONCAT(rr.recommandation_id) AS recommendations
                    FROM   recommandations_risks AS rr
                    GROUP BY rr.instance_risk_id) AS rec
                ON         ir.id = rec.instance_risk_id
                AND        ir.anr_id = rec.anr_id
                WHERE      ir.cache_max_risk >= -1
                AND        ir.anr_id = :anrid';
            $queryParams = [
                ':anrid' => $anrId,
            ];
        }

        $typeParams = [];
        // Find instance(s) id
        if ($instance === null) {
            // On prend toutes les instances, on est sur l'anr
            if ($context === AbstractEntity::BACK_OFFICE) {
                $instanceIds = [];
                $instanceTable = new InstanceTable($this->getDb(), $this->connectedUserService);
                $instances = $instanceTable->findByAnrId($anrId);
                if (count($instances) === 0) {
                    return [];
                }
                foreach ($instances as $instance) {
                    $instanceIds[] = $instance->getId();
                }
                if (!empty($instanceIds)) {
                    $sql .= ' AND i.id IN (:ids) ';
                    $queryParams[':ids'] = $instanceIds;
                    $typeParams[':ids'] = Connection::PARAM_INT_ARRAY;
                }
            }
        } elseif ($instance->get('asset') && $instance->get('asset')->get('type') == AssetSuperClass::TYPE_PRIMARY) {
            $instanceIds = [];
            $instanceIds[$instance->get('id')] = $instance->get('id');

            /**
             * TODO: - Inject dependencies if needed, a new class should not be created inside!
             * TODO: - Remove the dependency of FO, create and move the implementation to FO!
             */
            if ($context == AbstractEntity::BACK_OFFICE) {
                $instanceTable = new InstanceTable($this->getDb(), $this->connectedUserService);
            } else {
                $instanceTable = new \Monarc\FrontOffice\Model\Table\InstanceTable($this->getDb(), $this->connectedUserService);
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

            $sql .= ' AND i.id IN (:ids) ';
            $queryParams[':ids'] = $instanceIds;
            $typeParams[':ids'] = Connection::PARAM_INT_ARRAY;
        } else {
            $sql .= ' AND i.id = :id ';
            $queryParams[':id'] = $instance->get('id');
        }

        // FILTER: amvs ==
        if (isset($params['amvs'])) {
            if (!is_array($params['amvs'])) {
                $params['amvs'] = explode(',', substr($params['amvs'], 1, -1));
            }
            $sql .= ' AND a.uuid IN (:amvIds)';
            $queryParams[':amvIds'] = $params['amvs'];
            $typeParams[':amvIds'] = Connection::PARAM_INT_ARRAY;
        }
        // FILTER: kind_of_measure ==
        if (isset($params['kindOfMeasure'])) {
            if ($params['kindOfMeasure'] == InstanceRiskSuperClass::KIND_NOT_TREATED) {
                $sql .= ' AND (ir.kind_of_measure IS NULL OR ir.kind_of_measure = :kom) ';
                $queryParams[':kom'] = InstanceRiskSuperClass::KIND_NOT_TREATED;
            } else {
                $sql .= ' AND ir.kind_of_measure = :kom ';
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
                $orFilter[] = $f . ' LIKE :' . $k;
                $queryParams[":$k"] = '%' . $params['keywords'] . '%';
            }
            $sql .= ' AND (' . implode(' OR ', $orFilter) . ')';
        }
        // FILTER: cache_max_risk (min)
        if (isset($params['thresholds']) && $params['thresholds'] > 0) {
            $sql .= ' AND ir.cache_max_risk > :min';
            $queryParams[':min'] = $params['thresholds'];
        }

        // ORDER
        $params['order_direction'] = isset($params['order_direction'])
            && strtolower(trim($params['order_direction'])) !== 'asc' ? 'DESC' : 'ASC';
        $sql .= ' ORDER BY ';
        switch ($params['order']) {
            case 'instance':
                $sql .= " i.name$l ";
                break;
            case 'auditOrder':
                $sql .= ' a.position ';
                break;
            case 'c_impact':
                $sql .= ' i.c ';
                break;
            case 'i_impact':
                $sql .= ' i.i ';
                break;
            case 'd_impact':
                $sql .= ' i.d ';
                break;
            case 'threat':
                $sql .= " t.label$l ";
                break;
            case 'vulnerability':
                $sql .= " v.label$l ";
                break;
            case 'vulnerabilityRate':
                $sql .= ' ir.vulnerability_rate ';
                break;
            case 'threatRate':
                $sql .= ' ir.threat_rate ';
                break;
            case 'targetRisk':
                $sql .= ' ir.cache_targeted_risk ';
                break;
            default:
            case 'maxRisk':
                $sql .= ' ir.cache_max_risk ';
                break;
        }
        $sql .= ' ' . $params['order_direction'] . ' ';
        if ($params['order'] != 'instance') {
            $sql .= " , i.name$l ASC ";
        }
        $sql .= ' , t.code ASC , v.code ASC ';

        $res = $this->getDb()->getEntityManager()->getConnection()->fetchAll($sql, $queryParams, $typeParams);
        $lst = [];
        foreach ($res as $r) {
            // GROUP BY if scope = GLOBAL
            if ($r['scope'] == ObjectSuperClass::SCOPE_GLOBAL) {
                $key = 'o' . $r['oid'] . '-' . $r['tid'] . '-' . $r['vid'];
                if (!isset($lst[$key]) || $lst[$key]['max_risk'] < $r['max_risk']) {
                    $lst[$key] = $r;
                }
            } else {
                $lst['r' . $r['id']] = $r;
            }
        }
        return array_values($lst);
    }

    public function findByInstanceAndInstanceRiskRelations(
        InstanceSuperClass $instance,
        InstanceRiskSuperClass $instanceRisk
    ) {
        $queryBuilder = $this->getRepository()
            ->createQueryBuilder('ir')
            ->where('ir.instance = :instance')
            ->setParameter('instance', $instance);

        if ($instanceRisk->getAmv() !== null) {
            $queryBuilder->andWhere('ir.amv = :amv')->setParameter('amv', $instanceRisk->getAmv());
        }

        $queryBuilder
            ->andWhere('ir.threat = :threat')
            ->andWhere('ir.vulnerability = :vulnerability')
            ->setParameter('threat', $instanceRisk->getThreat())
            ->setParameter('vulnerability', $instanceRisk->getVulnerability());

        if ($instanceRisk->isSpecific()) {
            $queryBuilder->andWhere('ir.specific = ' . InstanceRiskSuperClass::TYPE_SPECIFIC);
        }

        return $queryBuilder->getQuery()->getResult();
    }

    /**
     * @return InstanceRiskSuperClass[]
     */
    public function findByInstance(InstanceSuperClass $instance)
    {
        return $this->getRepository()
            ->createQueryBuilder('ir')
            ->where('ir.instance = :instance')
            ->setParameter('instance', $instance)
            ->getQuery()
            ->getResult();
    }

    /**
     * @throws EntityNotFoundException
     */
    public function findById(int $id): InstanceRiskSuperClass
    {
        /** @var InstanceRiskSuperClass|null $instanceRisk */
        $instanceRisk = $this->getRepository()->find($id);
        if ($instanceRisk === null) {
            throw EntityNotFoundException::fromClassNameAndIdentifier(\get_class($this), [$id]);
        }

        return $instanceRisk;
    }

    /**
     * @throws ORMException
     * @throws OptimisticLockException
     */
    public function deleteEntity(InstanceRiskSuperClass $instanceRisk, bool $flush = true): void
    {
        $em = $this->getDb()->getEntityManager();
        $em->remove($instanceRisk);
        if ($flush) {
            $em->flush();
        }
    }
}
