<?php
namespace MonarcCore\Service;

/**
 * Scale Service
 *
 * Class ScaleService
 * @package MonarcCore\Service
 */
class ScaleService extends AbstractService
{
    protected $anrTable;
    protected $scaleTypeService;
    protected $dependencies = ['anr'];
    protected $types = [
      1 => 'TYPE_IMPACT',
      2 => 'TYPE_THREAT',
      3 => 'TYPE_VUL',
    ];

    /**
     * @return array
     */
    public function getTypes()
    {
        return $this->types;
    }

    /**
     * Get List
     *
     * @param int $page
     * @param int $limit
     * @param null $order
     * @param null $filter
     * @return mixed
     */
    public function getList($page = 1, $limit = 25, $order = null, $filter = null){

        $scales = parent::getList($page, $limit, $order, $filter);

        $types = $this->getTypes();

        foreach ($scales as $key => $scale) {
            $scales[$key]['type'] = $types[$scale['type']];
        }

        return $scales;
    }

    /**
     * Create
     *
     * @param $data
     * @throws \Exception
     */
    public function create($data) {

        //scale
        $entity = $this->get('entity');
        $entity->exchangeArray($data);
        $entity->setId(null);

        $dependencies =  (property_exists($this, 'dependencies')) ? $this->dependencies : [];
        $this->setDependencies($entity, $dependencies);

        $scaleId = $this->get('table')->save($entity);

        //scale type
        if ($entity->type == 1) {
            $scaleTypes = [
                [
                    'anr' => $data['anr'], 'scale' => $scaleId, 'type' => 1, 'isSys' => 1, 'isHidden' => 0,
                    'implicitPosition' => 1, 'label1' => 'Confidentialité', 'label2' => '', 'label3' => '', 'label4' => '',
                ],
                [
                    'anr' => $data['anr'], 'scale' => $scaleId, 'type' => 2, 'isSys' => 1, 'isHidden' => 0,
                    'implicitPosition' => 1, 'label1' => 'Intégrité', 'label2' => '', 'label3' => '', 'label4' => '',
                ],
                [
                    'anr' => $data['anr'], 'scale' => $scaleId, 'type' => 3, 'isSys' => 1, 'isHidden' => 0,
                    'implicitPosition' => 1, 'label1' => 'Disponibilité', 'label2' => '', 'label3' => '', 'label4' => '',
                ],
                [
                    'anr' => $data['anr'], 'scale' => $scaleId, 'type' => 4, 'isSys' => 1, 'isHidden' => 0,
                    'implicitPosition' => 1, 'label1' => 'Réputation', 'label2' => '', 'label3' => '', 'label4' => '',
                ],
                [
                    'anr' => $data['anr'], 'scale' => $scaleId, 'type' => 5, 'isSys' => 1, 'isHidden' => 0,
                    'implicitPosition' => 1, 'label1' => 'Opérationnel', 'label2' => '', 'label3' => '', 'label4' => '',
                ],
                [
                    'anr' => $data['anr'], 'scale' => $scaleId, 'type' => 6, 'isSys' => 1, 'isHidden' => 0,
                    'implicitPosition' => 1, 'label1' => 'Légal', 'label2' => '', 'label3' => '', 'label4' => '',
                ],
                [
                    'anr' => $data['anr'], 'scale' => $scaleId, 'type' => 7, 'isSys' => 1, 'isHidden' => 0,
                    'implicitPosition' => 1, 'label1' => 'Financier', 'label2' => '', 'label3' => '', 'label4' => '',
                ],
                [
                    'anr' => $data['anr'], 'scale' => $scaleId, 'type' => 8, 'isSys' => 1, 'isHidden' => 0,
                    'implicitPosition' => 1, 'label1' => 'Personne', 'label2' => '', 'label3' => '', 'label4' => '',
                ]
            ];
            foreach ($scaleTypes as $scaleType) {
                $this->get('scaleTypeService')->create($scaleType);
            }
        }

        return $scaleId;
    }
}