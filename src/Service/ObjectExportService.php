<?php
/**
 * @link      https://github.com/monarc-project for the canonical source repository
 * @copyright Copyright (c) 2016-2020 SMILE GIE Securitymadein.lu - Licensed under GNU Affero GPL v3
 * @license   MONARC is licensed under GNU Affero General Public License version 3
 */

namespace Monarc\Core\Service;

use Doctrine\ORM\EntityNotFoundException;
use Doctrine\ORM\NonUniqueResultException;
use Monarc\Core\Exception\Exception;
use Monarc\Core\Model\Table\MonarcObjectTable;

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
    protected $assetService;

    /** @var  ConfigService */
    protected $configService;

    /**
     * NOTE: this method is not used on the FO side, only for BO.
     *
     * @throws EntityNotFoundException
     * @throws Exception
     * @throws NonUniqueResultException
     */
    public function generateExportArray(string $uuid, $anr = null, $withEval = false): array
    {
        if (empty($uuid)) {
            throw new Exception('Object to export is required', 412);
        }

        /** @var MonarcObjectTable $monarcObjectTable */
        $monarcObjectTable = $this->get('table');
        $monarcObject = $monarcObjectTable->findByUuid($uuid);

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
            'object' => $monarcObject->getJsonArray($objectObj),
            'version' => $this->getVersion(),
            'monarc_version' => $this->configService->getAppVersion()['appVersion'],
        ];

        // Recovery categories
        $categ = $monarcObject->get('category');
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
        $asset = $monarcObject->get('asset');
        $return['asset'] = null;
        $return['object']['asset'] = null;
        if (!empty($asset)) {
            $asset = $asset->getJsonArray(['uuid']);
            $return['object']['asset'] = $asset['uuid'];
            $return['asset'] = $this->get('assetExportService')->generateExportArray($asset['uuid'], null, $withEval);
        }

        // Recovery of operational risks
        $rolfTag = $monarcObject->get('rolfTag');
        $return['object']['rolfTag'] = null;
        if (!empty($rolfTag)) {
            $risks = $rolfTag->get('risks');
            $rolfTag = $rolfTag->getJsonArray(['id', 'code', 'label1', 'label2', 'label3', 'label4']);
            $return['object']['rolfTag'] = $rolfTag['id'];
            $return['rolfTags'][$rolfTag['id']] = $rolfTag;
            $return['rolfTags'][$rolfTag['id']]['risks'] = [];
            if (!empty($risks)) {
                $measuresObj = [
                    'uuid' => 'uuid',
                    'category' => 'category',
                    'referential' => 'referential',
                    'code' => 'code',
                    'label1' => 'label1',
                    'label2' => 'label2',
                    'label3' => 'label3',
                    'label4' => 'label4',
                ];
                $soacategoriesObj = [
                    'id' => 'id',
                    'status' => 'status',
                    'label1' => 'label1',
                    'label2' => 'label2',
                    'label3' => 'label3',
                    'label4' => 'label4',
                ];
                $referentialObj = [
                    'uuid' => 'uuid',
                    'label1' => 'label1',
                    'label2' => 'label2',
                    'label3' => 'label3',
                    'label4' => 'label4',
                ];

                foreach ($risks as $r) {
                    $risk = $r->getJsonArray([
                        'id',
                        'code',
                        'label1',
                        'label2',
                        'label3',
                        'label4',
                        'description1',
                        'description2',
                        'description3',
                        'description4',
                    ]);
                    $risk['measures'] = [];
                    foreach ($r->measures as $measure) {
                        $newMeasure = $measure->getJsonArray($measuresObj);
                        $newMeasure['category'] = $measure->getCategory()
                            ? $measure->getCategory()->getJsonArray($soacategoriesObj)
                            : '';
                        $newMeasure['referential'] = $measure->getReferential()->getJsonArray($referentialObj);
                        $risk['measures'][] = $newMeasure;
                    }
                    $return['rolfTags'][$rolfTag['id']]['risks'][$risk['id']] = $risk['id'];
                    $return['rolfRisks'][$risk['id']] = $risk;
                }
            }
        }

        // Recovery children(s)
        $children = array_reverse($this->get('objectObjectService')->getChildren(
            $monarcObject->getUuid(),
            null
        )); // Le tri de cette fonction est "position DESC"
        $return['children'] = [];
        if (!empty($children)) {
            $place = 1;
            foreach ($children as $child) {
                $return['children'][$child->getChild()->getUuid()] = $this->generateExportArray(
                    $child->getChild()->getUuid(),
                    null,
                    $withEval
                );
                $return['children'][$child->getChild()->getUuid()]['object']['position'] = $place;
                $place++;
            }
        }

        return $return;
    }

    /**
     * NOTE: this method is not used on the FO side, only for BO.
     *
     * @throws EntityNotFoundException
     * @throws NonUniqueResultException
     */
    public function generateExportMospArray(string $uuid): array
    {
        $language = $this->getLanguage();
        $languageCode = $this->configService->getLanguageCodes()[$language];

        /** @var MonarcObjectTable $monarcObjectTable */
        $monarcObjectTable = $this->get('table');
        $monarcObject = $monarcObjectTable->findByUuid($uuid);

        $objectObj = [
            'uuid' => 'uuid',
            'scope' => 'scope',
            'name' => 'name' . $language,
            'label' => 'label' . $language,
        ];

        $return = [
            'object' => $monarcObject->getJsonArray($objectObj),
        ];

        $return['object']['name'] = $return['object']['name' . $language];
        $return['object']['label'] = $return['object']['label' . $language];
        $return['object']['scope'] = $return['object']['scope'] == 1 ? 'local' : 'global';
        $return['object']['language'] = $languageCode;
        $return['object']['version'] = 1;
        unset($return['object']['name' . $language]);
        unset($return['object']['label' . $language]);

        // Recovery asset
        $asset = $monarcObject->get('asset');
        $return['asset'] = null;
        if (!empty($asset)) {
            $asset = $asset->getJsonArray(['uuid']);
            $return['asset'] = $this->get('assetExportService')
                ->generateExportMospArray($asset['uuid'], null, $languageCode);
        }

        // Recovery of operational risks
        $return['rolfRisks'] = [];
        $return['rolfTags'] = [];
        $rolfTag = $monarcObject->get('rolfTag');
        if (!empty($rolfTag)) {
            $risks = $rolfTag->get('risks');
            $rolfTag = $rolfTag->getJsonArray(['code', 'label' . $language]);
            $rolfTag['label'] = $rolfTag['label' . $language];
            unset($rolfTag['label' . $language]);
            $return['rolfTags'][] = $rolfTag;
            if (!empty($risks)) {
                foreach ($risks as $r) {
                    $risk = $r->getJsonArray(['code', 'label' . $language, 'description' . $language]);
                    $risk['label'] = $risk['label' . $language];
                    $risk['description'] = $risk['description' . $language] ?? '';
                    unset($risk['label' . $language]);
                    unset($risk['description' . $language]);

                    $risk['measures'] = [];
                    foreach ($r->getMeasures() as $measure) {
                        $risk['measures'][] = [
                            'uuid' => $measure->getUuid(),
                            'code' => $measure->getCode(),
                            'label' => $measure->getLabel($language),
                            'category' => $measure->getCategory()->getLabel($language),
                            'referential' => $measure->getReferential()->getUuid(),
                            'referential_label' => $measure->getReferential()->getLabel($language),
                        ];
                    }
                    $return['rolfRisks'][] = $risk;
                }
            }
        }

        // Recover children
        $children = array_reverse($this->get('objectObjectService')->getChildren(
            $monarcObject->getUuid(),
            is_null($monarcObject->get('anr')) ? null : $monarcObject->get('anr')->get('id')
        )); // Le tri de cette fonction est "position DESC"
        $return['children'] = [];
        if (!empty($children)) {
            foreach ($children as $child) {
                $return['children'][] = $this->generateExportMospArray($child->getChild()->getUuid());
            }
        }

        return ['object' => $return];
    }

    /**
     * @throws EntityNotFoundException
     * @throws NonUniqueResultException
     */
    public function generateExportFileName(string $uuid, bool $isForMosp = false): string
    {
        /** @var MonarcObjectTable $monarcObjectTable */
        $monarcObjectTable = $this->get('table');
        $monarcObject = $monarcObjectTable->findByUuid($uuid);

        return preg_replace(
            "/[^a-z0-9\._-]+/i",
            '',
            $monarcObject->getName($this->getLanguage()) . $isForMosp ? '_MOSP' : ''
        );
    }
}
