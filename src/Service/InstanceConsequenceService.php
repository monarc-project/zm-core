<?php
/**
 * @link      https://github.com/monarc-project for the canonical source repository
 * @copyright Copyright (c) 2016-2020 SMILE GIE Securitymadein.lu - Licensed under GNU Affero GPL v3
 * @license   MONARC is licensed under GNU Affero General Public License version 3
 */

namespace Monarc\Core\Service;

use Doctrine\ORM\Mapping\MappingException;
use Monarc\Core\Model\Entity\InstanceConsequence;
use Monarc\Core\Model\Entity\InstanceConsequenceSuperClass;
use Monarc\Core\Model\Entity\InstanceSuperClass;
use Monarc\Core\Model\Table\InstanceConsequenceTable;
use Monarc\Core\Model\Table\InstanceTable;
use Laminas\EventManager\EventManager;
use Doctrine\ORM\Query\QueryException;
use Laminas\EventManager\SharedEventManager;
use Monarc\Core\Model\Table\ScaleCommentTable;

/**
 * Instance Consequence Service
 *
 * Class InstanceConsequenceService
 * @package Monarc\Core\Service
 */
class InstanceConsequenceService extends AbstractService
{
    protected $dependencies = ['anr', 'instance', 'object', 'scaleImpactType'];
    protected $anrTable;
    protected $instanceTable;
    protected $scaleTable;
    protected $scaleImpactTypeTable;
    protected $forbiddenFields = ['anr', 'instance', 'object', 'scaleImpactType'];

    /** @var ScaleCommentTable */
    protected $scaleCommentTable;

    /** @var SharedEventManager */
    private $sharedManager;

    public function getConsequences(InstanceSuperClass $instance, bool $includeScaleComments = false): array
    {
        /** @var InstanceConsequenceTable $instanceConsequenceTable */
        $instanceConsequenceTable = $this->get('table');
        $instanceConsequences = $instanceConsequenceTable->findByInstance($instance);
        /** @var ScaleCommentTable $scaleCommentTable */
        $scaleCommentTable = $this->get('scaleCommentTable');

        $languageNumber = $instance->getAnr()->getLanguage();

        $consequences = [];
        foreach ($instanceConsequences as $instanceConsequence) {
            $scaleImpactType = $instanceConsequence->getScaleImpactType();
            if (!$scaleImpactType->isHidden()) {
                $consequences[] = [
                    'id' => $instanceConsequence->getId(),
                    'scaleImpactTypeId' => $scaleImpactType->getId(),
                    'scaleImpactType' => $scaleImpactType->getType(),
                    'scaleImpactTypeDescription1' => $scaleImpactType->getLabel(1),
                    'scaleImpactTypeDescription2' => $scaleImpactType->getLabel(2),
                    'scaleImpactTypeDescription3' => $scaleImpactType->getLabel(3),
                    'scaleImpactTypeDescription4' => $scaleImpactType->getLabel(4),
                    'c_risk' => $instanceConsequence->getConfidentiality(),
                    'i_risk' => $instanceConsequence->getIntegrity(),
                    'd_risk' => $instanceConsequence->getAvailability(),
                    'isHidden' => $instanceConsequence->isHidden(),
                    'locallyTouched' => $instanceConsequence->getLocallyTouched(),
                ];

                if ($includeScaleComments) {
                    $scalesComments = $scaleCommentTable->findByAnrAndScaleImpactType(
                        $instance->getAnr(),
                        $scaleImpactType
                    );

                    $comments = [];
                    foreach ($scalesComments as $scaleComment) {
                        $comments[$scaleComment->getScaleValue()] = $scaleComment->getComment($languageNumber);
                    }

                    $consequences[array_key_last($consequences)]['comments'] = $comments;
                }
            }
        }

        return $consequences;
    }

    /**
     * @inheritdoc
     */
    public function patchConsequence($id, $data, $patchInstance = true, $local = true, $fromInstance = false)
    {
        $anrId = $data['anr'];

        if (count($data)) {
            /** @var InstanceConsequenceSuperClass $instanceConsequence */
            $instanceConsequence = $this->get('table')->getEntity($id);

            if (isset($data['isHidden'])) {
                if ($data['isHidden']) {
                    $data['c'] = -1;
                    $data['i'] = -1;
                    $data['d'] = -1;
                    if ($local) {
                        $data['locallyTouched'] = 1;
                    }
                } else {
                    if ($local) {
                        $data['locallyTouched'] = 0;
                    } else {
                        if ($instanceConsequence->locallyTouched) {
                            $data['isHidden'] = 1;
                        }
                    }
                }
            } elseif ($instanceConsequence->isHidden) {
                $data['c'] = -1;
                $data['i'] = -1;
                $data['d'] = -1;
            }

            $data = $this->updateConsequences($id, $data);

            $data['anr'] = $anrId;

            $this->verifyRates($anrId, $data, $this->getEntity($id));

            parent::patch($id, $data);

            $this->updateBrothersConsequences($anrId, $id);

            if ($patchInstance) {
                $this->updateInstanceImpacts($instanceConsequence, $fromInstance);
            }
        }

        return $id;
    }

    /**
     * @inheritdoc
     */
    public function update($id, $data)
    {
        if (empty($data)) {
            throw new \Monarc\Core\Exception\Exception('Data missing', 412);
        }

        $anrId = $data['anr'];
        $data = $this->updateConsequences($id, $data);
        $data['anr'] = $anrId;

        /** @var InstanceConsequenceTable $table */
        $table = $this->get('table');
        /** @var InstanceConsequence $instanceConsequence */
        $instanceConsequence = $table->getEntity($id);

        $this->filterPostFields($data, $instanceConsequence, ['anr', 'instance', 'object', 'scaleImpactType', 'ch', 'ih', 'dh']);

        $instanceConsequence->setDbAdapter($this->get('table')->getDb());
        $instanceConsequence->setLanguage($this->getLanguage());

        $instanceConsequence->exchangeArray($data);

        $dependencies = (property_exists($this, 'dependencies')) ? $this->dependencies : [];
        $this->setDependencies($instanceConsequence, $dependencies);

        $instanceConsequence->setUpdater(
            $this->getConnectedUser()->getFirstname() . ' ' . $this->getConnectedUser()->getLastname()
        );

        $id = $this->get('table')->save($instanceConsequence);

        $this->updateBrothersConsequences($anrId, $id);

        $this->updateInstanceImpacts($instanceConsequence);

        return $id;
    }

    /**
     * Update the consequences of the provided instance ID
     * @param int $id The instance ID
     * @param array $data The new values
     * @return array $data
     */
    protected function updateConsequences($id, $data)
    {
        $anrId = $data['anr'];
        unset($data['anr']);

        $this->verifyRates($anrId, $data, $this->getEntity($id));

        return $data;
    }

    /**
     * Update the consequences of the instances at the same level
     * @param int $anrId The ANR ID
     * @param int $id THe instance consequence ID
     */
    public function updateBrothersConsequences($anrId, $id)
    {
        /** @var InstanceConsequenceTable $table */
        $table = $this->get('table');
        $instanceConsequence = $table->getEntity($id);

        if ($instanceConsequence->getObject()->isScopeGlobal()) {
            /** @var InstanceTable $instanceTable */
            $instanceTable = $this->get('instanceTable');
            try {
                $brothers = $instanceTable->getEntityByFields([
                    'anr' => $anrId,
                    'object' => $instanceConsequence->getObject()->getUuid(),
                ]);
            } catch (QueryException|MappingException $e) {
                $brothers = $instanceTable->getEntityByFields([
                    'anr' => $anrId,
                    'object' => [
                        'uuid' => $instanceConsequence->getObject()->getUuid(),
                        'anr' => $anrId,
                    ]
                ]);
            }

            if (count($brothers) > 1) {
                foreach ($brothers as $brother) {

                    /** @var InstanceConsequenceTable $instanceConsequenceTable */
                    $instanceConsequenceTable = $this->get('table');
                    $brotherInstancesConsequences = $instanceConsequenceTable->getEntityByFields([
                        'anr' => $anrId,
                        'instance' => $brother->id,
                        'scaleImpactType' => $instanceConsequence->scaleImpactType->id
                    ]);

                    $i = 1;
                    $nbBrotherInstancesConsequences = count($brotherInstancesConsequences);
                    foreach ($brotherInstancesConsequences as $brotherInstanceConsequence) {
                        $brotherInstanceConsequence->isHidden = $instanceConsequence->isHidden;
                        $brotherInstanceConsequence->locallyTouched = $instanceConsequence->locallyTouched;
                        $brotherInstanceConsequence->c = $instanceConsequence->c;
                        $brotherInstanceConsequence->i = $instanceConsequence->i;
                        $brotherInstanceConsequence->d = $instanceConsequence->d;

                        $instanceConsequenceTable->save($brotherInstanceConsequence, ($i == $nbBrotherInstancesConsequences));
                        $i++;
                    }
                }
            }
        }
    }

    public function updateInstanceImpacts(InstanceConsequenceSuperClass $instanceConsequence, $fromInstance = false)
    {
        $class = $this->get('scaleImpactTypeTable')->getEntityClass();
        $cidTypes = $class::getScaleImpactTypesCid();

        $instanceC = [];
        $instanceI = [];
        $instanceD = [];
        /** @var InstanceConsequenceTable $table */
        $table = $this->get('table');
        /** @var InstanceConsequenceSuperClass[] $otherInstanceConsequences */
        $otherInstanceConsequences = $table->getEntityByFields([
            'instance' => $instanceConsequence->getInstance()->getId()
        ]);
        foreach ($otherInstanceConsequences as $otherInstanceConsequence) {
            if (!in_array($otherInstanceConsequence->getScaleImpactType()->type, $cidTypes)) {
                $instanceC[] = (int)$otherInstanceConsequence->get('c');
                $instanceI[] = (int)$otherInstanceConsequence->get('i');
                $instanceD[] = (int)$otherInstanceConsequence->get('d');
            }
        }

        $data = [
            'c' => max($instanceC),
            'i' => max($instanceI),
            'd' => max($instanceD),
        ];

        $parent = $instanceConsequence->getInstance()->getParent();
        foreach ($data as $k => $v) {
            $data[$k . 'h'] = ($v == -1) ? 1 : 0;
            if ($data[$k . 'h'] && !empty($parent)) { // hérité: on prend la valeur du parent
                $data[$k] = $parent->get($k);
            }
        }

        $anrId = $instanceConsequence->getAnr()->getId();
        $data['anr'] = $anrId;

        if (!$fromInstance) {
            // If parent's instance exist, create instance for child.
            $eventManager = new EventManager($this->sharedManager, ['instance']);
            $instanceId = $instanceConsequence->getInstance()->getId();
            $eventManager->trigger('patch', $this, compact(['anrId', 'instanceId', 'data']));

            return;
        }

        $instance = $instanceConsequence->getInstance()->initialize();
        $instance->exchangeArray($data);
        $this->get('instanceTable')->save($instance);
    }

    public function setSharedManager(SharedEventManager $sharedManager)
    {
        $this->sharedManager = $sharedManager;
    }

    /**
     * Patch by Scale Impact Type
     * @param int $scaleImpactTypeId The scale impact type ID
     * @param array $data The new data to set
     */
    public function patchByScaleImpactType($scaleImpactTypeId, $data)
    {
        /** @var InstanceConsequenceTable $instanceConsequenceTable */
        $instanceConsequenceTable = $this->get('table');
        /** @var InstanceConsequenceSuperClass[] $instancesConsequences */
        $instancesConsequences = $instanceConsequenceTable->getEntityByFields([
            'scaleImpactType' => $scaleImpactTypeId
        ]);

        $consequences = [];
        foreach ($instancesConsequences as $instanceConsequence) {
            $this->patchConsequence($instanceConsequence->getId(), $data, false, false);
            $consequences[] = $instanceConsequence;
        }

        foreach ($consequences as $consequence) {
            $this->updateInstanceImpacts($consequence);
        }

        unset($consequences);
    }
}
