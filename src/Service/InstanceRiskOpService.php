<?php declare(strict_types=1);
/**
 * @link      https://github.com/monarc-project for the canonical source repository
 * @copyright Copyright (c) 2016-2020 SMILE GIE Securitymadein.lu - Licensed under GNU Affero GPL v3
 * @license   MONARC is licensed under GNU Affero General Public License version 3
 */

namespace Monarc\Core\Service;

use Doctrine\ORM\OptimisticLockException;
use Doctrine\ORM\ORMException;
use Monarc\Core\Exception\Exception;
use Monarc\Core\Model\Entity\Asset;
use Monarc\Core\Model\Entity\InstanceRiskOp;
use Monarc\Core\Model\Entity\InstanceRiskOpSuperClass;
use Monarc\Core\Model\Entity\InstanceSuperClass;
use Monarc\Core\Model\Entity\ObjectSuperClass;
use Monarc\Core\Model\Entity\OperationalInstanceRiskScale;
use Monarc\Core\Model\Entity\OperationalInstanceRiskScaleSuperClass;
use Monarc\Core\Model\Entity\OperationalRiskScale;
use Monarc\Core\Model\Entity\OperationalRiskScaleSuperClass;
use Monarc\Core\Model\Entity\OperationalRiskScaleType;
use Monarc\Core\Model\Entity\RolfRiskSuperClass;
use Monarc\Core\Model\Entity\UserSuperClass;
use Monarc\Core\Model\Table\AnrTable;
use Monarc\Core\Model\Table\InstanceRiskOpTable;
use Monarc\Core\Model\Table\InstanceTable;
use Monarc\Core\Model\Table\OperationalInstanceRiskScaleTable;
use Monarc\Core\Model\Table\OperationalRiskScaleTable;
use Monarc\Core\Model\Table\RolfTagTable;
use Monarc\Core\Model\Table\TranslationTable;

class InstanceRiskOpService
{
    protected AnrTable $anrTable;

    protected InstanceTable $instanceTable;

    protected InstanceRiskOpTable $instanceRiskOpTable;

    protected RolfTagTable $rolfTagTable;

    protected UserSuperClass $connectedUser;

    protected OperationalInstanceRiskScaleTable $operationalInstanceRiskScaleTable;

    protected TranslationTable $translationTable;

    protected TranslateService $translateService;

    protected OperationalRiskScaleTable $operationalRiskScaleTable;

    protected ConfigService $configService;

    public function __construct(
        AnrTable $anrTable,
        InstanceTable $instanceTable,
        InstanceRiskOpTable $instanceRiskOpTable,
        OperationalInstanceRiskScaleTable $operationalInstanceRiskScaleTable,
        RolfTagTable $rolfTagTable,
        ConnectedUserService $connectedUserService,
        TranslationTable $translationTable,
        TranslateService $translateService,
        OperationalRiskScaleTable $operationalRiskScaleTable,
        ConfigService $configService
    ) {
        $this->anrTable = $anrTable;
        $this->instanceTable = $instanceTable;
        $this->instanceRiskOpTable = $instanceRiskOpTable;
        $this->operationalInstanceRiskScaleTable = $operationalInstanceRiskScaleTable;
        $this->rolfTagTable = $rolfTagTable;
        $this->connectedUser = $connectedUserService->getConnectedUser();
        $this->translationTable = $translationTable;
        $this->translateService = $translateService;
        $this->operationalRiskScaleTable = $operationalRiskScaleTable;
        $this->configService = $configService;
    }

    public function createInstanceRisksOp(InstanceSuperClass $instance, ObjectSuperClass $object): void
    {
        if ($object->getAsset() === null
            || $object->getRolfTag() === null
            || $object->getAsset()->getType() !== Asset::TYPE_PRIMARY
        ) {
            return;
        }

        $otherInstance = $this->instanceTable->findOneByAnrAndObjectExcludeInstance(
            $instance->getAnr(),
            $object,
            $instance
        );

        if ($otherInstance !== null && $object->isScopeGlobal()) {
            foreach ($this->instanceRiskOpTable->findByInstance($otherInstance) as $instanceRiskOp) {
                $newInstanceRiskOp = (clone $instanceRiskOp)
                    ->setAnr($instance->getAnr())
                    ->setInstance($instance)
                    ->setCreator($this->connectedUser->getFirstname() . ' ' . $this->connectedUser->getLastname());
                $this->instanceRiskOpTable->saveEntity($newInstanceRiskOp, false);

                $operationalInstanceRiskScales = $this->operationalInstanceRiskScaleTable->findByInstanceRiskOp(
                    $instanceRiskOp
                );
                foreach ($operationalInstanceRiskScales as $operationalInstanceRiskScale) {
                    $operationalInstanceRiskScaleClone = (clone $operationalInstanceRiskScale)
                        ->setCreator($this->connectedUser->getEmail());
                    $this->operationalInstanceRiskScaleTable->save($operationalInstanceRiskScaleClone, false);
                }
            }
        } else {
            $rolfTag = $this->rolfTagTable->findById($object->getRolfTag()->getId());
            foreach ($rolfTag->getRisks() as $rolfRisk) {
                $instanceRiskOp = $this->createInstanceRiskOpObjectFromInstanceObjectAndRolfRisk(
                    $instance,
                    $object,
                    $rolfRisk
                );

                $this->instanceRiskOpTable->saveEntity($instanceRiskOp, false);

                $operationalRiskScales = $this->operationalRiskScaleTable->findByAnrAndType(
                    $instance->getAnr(),
                    OperationalRiskScale::TYPE_IMPACT
                );
                foreach ($operationalRiskScales as $operationalRiskScale) {
                    $operationalInstanceRiskScale = $this->createOperationalInstanceRiskScaleObject(
                        $instanceRiskOp,
                        $operationalRiskScale,
                    );

                    $this->operationalInstanceRiskScaleTable->save($operationalInstanceRiskScale, false);
                }
            }
        }

        $this->instanceRiskOpTable->getDb()->flush();
    }

    /**
     * @throws ORMException
     * @throws OptimisticLockException
     */
    public function deleteOperationalRisks(InstanceSuperClass $instance): void
    {
        $operationalRisks = $this->instanceRiskOpTable->findByInstance($instance);
        foreach ($operationalRisks as $operationalRisk) {
            $this->instanceRiskOpTable->deleteEntity($operationalRisk, false);
        }
        $this->instanceRiskOpTable->getDb()->flush();
    }

    public function getOperationalRisks(int $anrId, int $instanceId = null, array $params = [])
    {
        $instancesInfos = [];
        if ($instanceId === null) {
            $instances = $this->instanceTable->findByAnrId($anrId);
        } else {
            $instance = $this->instanceTable->findById($instanceId);
            $this->instanceTable->initTree($instance);
            $instances = $this->extractInstanceAndChildInstances($instance);
        }
        foreach ($instances as $instance) {
            if ($instance->getAsset()->getType() === Asset::TYPE_PRIMARY) {
                $instancesInfos[$instance->getId()] = [
                    'id' => $instance->getId(),
                    'scope' => $instance->getObject()->getScope(),
                    'name1' => $instance->getName1(),
                    'name2' => $instance->getName2(),
                    'name3' => $instance->getName3(),
                    'name4' => $instance->getName4()
                ];
            }
        }

        $instancesRisksOp = $this->instanceRiskOpTable->getInstancesRisksOp(
            $anrId,
            array_keys($instancesInfos),
            $params
        );
        $operationalRisksScalesTranslations = [];
        if (!empty($instancesRisksOp)) {
            $anr = current($instancesRisksOp)->getAnr();
            $operationalRisksScalesTranslations = $this->translationTable->findByAnrTypesAndLanguageIndexedByKey(
                $anr,
                [OperationalRiskScaleType::TRANSLATION_TYPE_NAME],
                strtolower($this->configService->getLanguageCodes()[$anr->getLanguage()])
            );
        }
        $result = [];
        foreach ($instancesRisksOp as $instanceRiskOp) {
            $operationalInstanceRiskScales = $instanceRiskOp->getOperationalInstanceRiskScales();
            $scalesData = [];
            foreach ($operationalInstanceRiskScales as $operationalInstanceRiskScale) {
                $riskScale = $operationalInstanceRiskScale->getOperationalRiskScaleType();
                $scalesData[$operationalInstanceRiskScale->getOperationalRiskScale()->getId()] = [
                    'instanceRiskScaleId' => $operationalInstanceRiskScale->getId(),
                    'label' => $operationalRisksScalesTranslations[$riskScale->getLabelTranslationKey()]->getValue(),
                    'netValue' => $operationalInstanceRiskScale->getNetValue(),
                    'brutValue' => $operationalInstanceRiskScale->getBrutValue(),
                    'targetValue' => $operationalInstanceRiskScale->getTargetedValue(),
                    'isHidden' => $riskScale->isHidden(),
                ];
            }

            $result[] = [
                'id' => $instanceRiskOp->getId(),
                'instanceInfos' => $instancesInfos[$instanceRiskOp->getInstance()->getId()] ?? [],
                'label1' => $instanceRiskOp->getRiskCacheLabel(1),
                'label2' => $instanceRiskOp->getRiskCacheLabel(2),
                'label3' => $instanceRiskOp->getRiskCacheLabel(3),
                'label4' => $instanceRiskOp->getRiskCacheLabel(4),

                'description1' => $instanceRiskOp->getRiskCacheDescription(1),
                'description2' => $instanceRiskOp->getRiskCacheDescription(2),
                'description3' => $instanceRiskOp->getRiskCacheDescription(3),
                'description4' => $instanceRiskOp->getRiskCacheDescription(4),

                'cacheNetRisk' => $instanceRiskOp->getCacheNetRisk(),
                'cacheBrutRisk' => $instanceRiskOp->getCacheBrutRisk(),
                'cacheTargetedRisk' => $instanceRiskOp->getCacheTargetedRisk(),
                'scales' => $scalesData,

                'kindOfMeasure' => $instanceRiskOp->getKindOfMeasure(),
                'comment' => $instanceRiskOp->getComment(),
                't' => $instanceRiskOp->getKindOfMeasure() === InstanceRiskOp::KIND_NOT_TREATED,

                'context' => $instanceRiskOp->getContext(),
                'owner' => $instanceRiskOp->getOwner() ? $instanceRiskOp->getOwner()->getName() : '',
            ];
        }

        return $result;
    }

    // TODO: update the method.
    public function getOperationalRisksInCsv(int $anrId, int $instance = null, array $params = [])
    {
        $risks = $this->getOperationalRisks($anrId, $instance, $params);
        $anr = $this->anrTable->findById($anrId);
        $lang = $anr->getLanguage();

        $output = '';

        // TODO: finish the refactoring!

        if (count($risks) > 0) {
            $fields_1 = [
                'instanceInfos' => $this->translateService->translate('Asset', $lang),
                'label'. $lang => $this->translateService->translate('Risk description', $lang),
            ];
            if ($anr->getShowRolfBrut() === 1) {
                $fields_2 = [
                    'brutProb' =>  $this->translateService->translate('Prob.', $lang)
                        . "(" . $this->translateService->translate('Inherent risk', $lang) . ")",
                    'brutR' => 'R' . " (" . $this->translateService->translate('Inherent risk', $lang) . ")",
                    'brutO' => 'O' . " (" . $this->translateService->translate('Inherent risk', $lang) . ")",
                    'brutL' => 'L' . " (" . $this->translateService->translate('Inherent risk', $lang) . ")",
                    'brutF' => 'F' . " (" . $this->translateService->translate('Inherent risk', $lang) . ")",
                    'brutP' => 'P' . " (" . $this->translateService->translate('Inherent risk', $lang) . ")",
                    'cacheBrutRisk' => $this->translateService->translate('Current risk', $lang)
                        . " (" . $this->translateService->translate('Inherent risk', $lang) . ")",
                ];
            }
            else {
                $fields_2 = [];
            }
            $fields_3 = [
                'netProb' => $this->translateService->translate('Prob.', $lang)
                    . "(" . $this->translateService->translate('Net risk', $lang) . ")",
                'netR' => 'R' . " (" . $this->translateService->translate('Net risk', $lang) . ")",
                'netO' => 'O' . " (" . $this->translateService->translate('Net risk', $lang) . ")",
                'netL' => 'L' . " (" . $this->translateService->translate('Net risk', $lang) . ")",
                'netF' => 'F' . " (" . $this->translateService->translate('Net risk', $lang) . ")",
                'netP' => 'P' . " (" . $this->translateService->translate('Net risk', $lang) . ")",
                'cacheNetRisk' => $this->translateService->translate('Current risk', $lang) . " ("
                    . $this->translateService->translate('Net risk', $lang) . ")",
                'comment' => $this->translateService->translate('Existing controls', $lang),
                'kindOfMeasure' => $this->translateService->translate('Treatment', $lang),
                'cacheTargetedRisk' => $this->translateService->translate('Residual risk', $lang),
            ];
            $fields = $fields_1 + $fields_2 + $fields_3;

            // Fill in the headers
            $output .= implode(',', array_values($fields)) . "\n";
            foreach ($risks as $risk) {
                foreach ($fields as $k => $v) {
                    if ($k == 'kindOfMeasure'){
                        switch ($risk[$k]) {
                            case 1:
                                $fieldsValues[] = 'Reduction';
                                break;
                            case 2:
                                $fieldsValues[] = 'Denied';
                                break;
                            case 3:
                                $fieldsValues[] = 'Accepted';
                                break;
                            default:
                                $fieldsValues[] = 'Not treated';
                        }
                    }
                    elseif ($k == 'instanceInfos') {
                        $fieldsValues[] = $risk[$k]['name' . $lang];
                    }
                    elseif ($risk[$k] == '-1'){
                        $fieldsValues[] = null;
                    }
                    else {
                        $fieldsValues[] = $risk[$k];
                    }
                }
                $output .= '"';
                $search = ['"',"\n"];
                $replace = ["'",' '];
                $output .= implode('","', str_replace($search, $replace, $fieldsValues));
                $output .= "\"\r\n";
                $fieldsValues = null;
            }
        }

        return $output;
    }

    // TODO: adjust the code!
    // TODO: check if it is used ?
    public function patch($id, $data)
    {
        $this->instanceRiskOpTable->findById($id);

        $toFilter = [
            'brutProb',
            'brutR',
            'brutO',
            'brutL',
            'brutF',
            'brutP',
            'netProb',
            'netR',
            'netO',
            'netL',
            'netF',
            'netP'
        ];

        // CLean up the values to avoid empty values or dashes
        foreach ($toFilter as $k) {
            if (isset($data[$k])) {
                $data[$k] = trim($data[$k]);
                if (empty($data[$k]) || $data[$k] == '-' || $data[$k] == -1) {
                    $data[$k] = -1;
                }
            }
        }

        // Filter out fields we don't want to update
        $this->filterPatchFields($data);

//        return parent::patch($id, $data);
    }

    // TODO: adjust the code!
    public function update(int $id, array $data): array
    {
        $instanceRiskOp = $this->instanceRiskOpTable->findById($id);

        $toFilter = [
            'brutProb',
            'brutR',
            'brutO',
            'brutL',
            'brutF',
            'brutP',
            'netProb',
            'netR',
            'netO',
            'netL',
            'netF',
            'netP',
        ];
        foreach ($toFilter as $k) {
            if (isset($data[$k])) {
                $data[$k] = trim($data[$k]);
                if (!isset($data[$k]) || $data[$k] == '-' || $data[$k] == -1) {
                    $data[$k] = -1;
                }
            }
        }

        $this->verifyRates($instanceRiskOp->getAnr()->getId(), $data, $instanceRiskOp);
        $instanceRiskOp->setDbAdapter($this->get('table')->getDb());
        $instanceRiskOp->setLanguage($this->getLanguage());

        if (empty($data)) {
            throw new Exception('Data missing', 412);
        }

        $instanceRiskOp->exchangeArray($data);

        $dependencies = (property_exists($this, 'dependencies')) ? $this->dependencies : [];
        $this->setDependencies($instanceRiskOp, $dependencies);

        //Calculate risk values
        $datatype = ['brut', 'net', 'targeted'];
        $impacts = ['r', 'o', 'l', 'f', 'p'];

        foreach ($datatype as $type) {
            $max = -1;
            $prob = $type . 'Prob';
            if ($instanceRiskOp->$prob != -1) {
                foreach ($impacts as $i) {
                    $icol = $type . strtoupper($i);
                    if ($instanceRiskOp->$icol > -1 && ($instanceRiskOp->$prob * $instanceRiskOp->$icol > $max)) {
                        $max = $instanceRiskOp->$prob * $instanceRiskOp->$icol;
                    }
                }
            }

            $cache = 'cache' . ucfirst($type) . 'Risk';
            $instanceRiskOp->$cache = $max;
        }

        $instanceRiskOp->setUpdater(
            $this->getConnectedUser()->getFirstname() . ' ' . $this->getConnectedUser()->getLastname()
        );

        $this->instanceRiskOpTable->saveEntity($instanceRiskOp);

        return $instanceRiskOp->getJsonArray();
    }

    protected function createInstanceRiskOpObjectFromInstanceObjectAndRolfRisk(
        InstanceSuperClass $instance,
        ObjectSuperClass $object,
        RolfRiskSuperClass $rolfRisk
    ): InstanceRiskOpSuperClass {
        return (new InstanceRiskOp())
            ->setAnr($instance->getAnr())
            ->setInstance($instance)
            ->setObject($object)
            ->setRolfRisk($rolfRisk)
            ->setRiskCacheCode($rolfRisk->getCode())
            ->setRiskCacheLabels([
                'riskCacheLabel1' => $rolfRisk->getLabel(1),
                'riskCacheLabel2' => $rolfRisk->getLabel(2),
                'riskCacheLabel3' => $rolfRisk->getLabel(3),
                'riskCacheLabel4' => $rolfRisk->getLabel(4),
            ])
            ->setRiskCacheDescriptions([
                'riskCacheDescription1' => $rolfRisk->getDescription(1),
                'riskCacheDescription2' => $rolfRisk->getDescription(2),
                'riskCacheDescription3' => $rolfRisk->getDescription(3),
                'riskCacheDescription4' => $rolfRisk->getDescription(4),
            ]);
    }

    protected function createOperationalInstanceRiskScaleObject(
        InstanceRiskOpSuperClass $instanceRiskOp,
        OperationalRiskScaleSuperClass $operationalRiskScale
    ): OperationalInstanceRiskScaleSuperClass {
        return (new OperationalInstanceRiskScale())
            ->setAnr($instanceRiskOp->getAnr())
            ->setOperationalInstanceRisk($instanceRiskOp)
            ->setOperationalRiskScale($operationalRiskScale)
            ->setCreator($this->connectedUser->getEmail());
    }

    private function extractInstanceAndChildInstances(InstanceSuperClass $instance): array
    {
        $childInstances = [];
        foreach ($instance->getParameterValues('children') as $childInstance) {
            $childInstances = array_merge($childInstances, $this->extractInstanceAndChildInstances($childInstance));
        }

        return array_merge([$instance], $childInstances);
    }
}
