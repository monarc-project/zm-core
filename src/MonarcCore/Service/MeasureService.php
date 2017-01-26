<?php
namespace MonarcCore\Service;

/**
 * Measure Service
 *
 * Class MeasureService
 * @package MonarcCore\Service
 */
class MeasureService extends AbstractService
{
    protected $filterColumns = ['description1', 'description2', 'description3', 'description4', 'code', 'status'];
    protected $forbiddenFields = ['anr'];

    /**
     * Create
     *
     * @param $data
     * @param bool $last
     * @return mixed
     * @throws \Exception
     */
    public function create($data, $last = true)
    {
        $class = $this->get('entity');
        $entity = new $class();
        $entity->setLanguage($this->getLanguage());
        $entity->setDbAdapter($this->get('table')->getDb());
        if (isset($data['anr']) && strlen($data['anr'])) {
            /** @var AnrTable $anrTable */
            $anrTable = $this->get('anrTable');
            $anr = $anrTable->getEntity($data['anr']);

            if (!$anr) {
                throw new \Exception('Risk analysis does not exist', 412);
            }
            $entity->setAnr($anr);
        }
        $entity->exchangeArray($data);

        $dependencies = (property_exists($this, 'dependencies')) ? $this->dependencies : [];
        $this->setDependencies($entity, $dependencies);

        return $this->get('table')->save($entity);
    }

    /**
     * Patch
     *
     * @param $id
     * @param $data
     * @return mixed
     */
    public function patch($id, $data)
    {
        //security
        $this->filterPatchFields($data);

        parent::patch($id, $data);
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
    public function getList($page = 1, $limit = 25, $order = null, $filter = null, $filterAnd = null)
    {
        $data = $this->get('table')->fetchAllFiltered(
            array_keys($this->get('entity')->getJsonArray()),
            $page,
            $limit,
            $this->parseFrontendOrder($order),
            $this->parseFrontendFilter($filter, $this->filterColumns),
            $filterAnd
        );

        if ($order == "code" || $order == "-code") {
            $desc = ($order == "-code");

            // Codes might be in xx.xx.xx format which need a numerical sorting instead of an alphabetical one
            $re = '/^([0-9]+\.)+[0-9]+$/m';
            usort($data, function ($a, $b) use ($re, $desc) {
                $a_match = (preg_match($re, $a['code']) > 0);
                $b_match = (preg_match($re, $b['code']) > 0);

                if ($a_match && $b_match) {
                    $a_values = explode('.', $a['code']);
                    $b_values = explode('.', $b['code']);

                    if (count($a_values) < count($b_values)) {
                        return $desc ? 1 : -1;
                    } else if (count($a_values) > count($b_values)) {
                        return $desc ? -1 : 1;
                    } else {
                        for ($i = 0; $i < count($a_values); ++$i) {
                            if ($a_values[$i] != $b_values[$i]) {
                                return $desc ? (intval($b_values[$i]) - intval($a_values[$i])) : (intval($a_values[$i]) - intval($b_values[$i]));
                            }
                        }

                        // If we reach here, all values are equal
                        return 0;
                    }


                } else if ($a_match && !$b_match) {
                    return $desc ? 1 : -1;
                } else if (!$a_match && $b_match) {
                    return $desc ? -1 : 1;
                } else {
                    return $desc ? strcmp($b_match, $a_match) : strcmp($a_match, $b_match);
                }
            });

        }

        return $data;
    }
}