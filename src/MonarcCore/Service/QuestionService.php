<?php
/**
 * @link      https://github.com/CASES-LU for the canonical source repository
 * @copyright Copyright (c) Cases is a registered trademark of SECURITYMADEIN.LU
 * @license   MyCases is licensed under the GNU Affero GPL v3 - See license.txt for more information
 */

namespace MonarcCore\Service;

/**
 * Question Service
 *
 * Class QuestionService
 * @package MonarcCore\Service
 */
class QuestionService extends AbstractService
{
    protected $choiceTable;
    protected $anrTable;
    protected $userAnrTable;
    protected $dependencies = ['anr'];

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
        if (empty($order)) {
            $order = 'position';
        }

        $data = $this->get('table')->fetchAllFiltered(
            array_keys($this->get('entity')->getJsonArray()),
            $page,
            $limit,
            $this->parseFrontendOrder($order),
            $this->parseFrontendFilter($filter, $this->filterColumns),
            $filterAnd
        );

        // Append choices
        foreach ($data as &$d) {
            if ($d['type'] == 2) {
                $d['choices'] = $this->get('choiceTable')->fetchAllFiltered([], 1, 0, null, null, ['question' => $d['id']]);

                if (!empty($filterAnd['anr']) && !$d['multichoice']) {
                    $c = $this->get('choiceTable')->getClass();
                    $empty = new $c();
                    array_unshift($d['choices'], $empty->getJsonArray());
                }
            }
        }

        return $data;
    }

    /**
     * Create
     *
     * @param $data
     * @param bool $last
     * @return mixed
     */
    public function create($data, $last = true)
    {
        $dependencies = (property_exists($this, 'dependencies')) ? $this->dependencies : [];

        $entity = $this->get('entity');
        $entity->setDbAdapter($this->get('table')->getDb());

        if (!empty($data['anr'])) {
            $data['mode'] = 1;
            $data['implicitPosition'] = \MonarcCore\Model\Entity\AbstractEntity::IMP_POS_END;
            $data['type'] = 1; // on force en textarea uniquement
            $data['multichoice'] = 0;
            unset($data['position']);
        }

        $entity->exchangeArray($data);
        $this->setDependencies($entity, $dependencies);

        return $this->get('table')->save($entity);
    }

    /**
     * Update
     *
     * @param $id
     * @param $data
     * @return mixed
     * @throws \MonarcCore\Exception\Exception
     */
    public function update($id, $data)
    {
        $entity = $this->get('table')->getEntity($id);
        $entity->setDbAdapter($this->get('table')->getDb());

        if (!empty($data['anr'])) {
            if ($data['anr'] == $entity->get('anr')->get('id')) {
                if ($entity->get('mode')) {
                    unset(
                        $data['type'],
                        $data['position'],
                        $data['multichoice']
                    );
                } else {
                    // on ne met pas à jour la question
                    unset(
                        $data['label1'],
                        $data['label2'],
                        $data['label3'],
                        $data['label4']
                    );
                }
                unset($data['mode']);
            } else {
                throw new \MonarcCore\Exception\Exception('Anr ids diffence', 412);
            }
        }

        $entity->exchangeArray($data);

        $dependencies = (property_exists($this, 'dependencies')) ? $this->dependencies : [];
        $this->setDependencies($entity, $dependencies);

        return $this->get('table')->save($entity);
    }

    /**
     * Delete
     *
     * @param $id
     * @throws \MonarcCore\Exception\Exception
     */
    public function delete($id)
    {
        $entity = $this->getEntity($id);

        if (!empty($entity['anr']) && isset($entity['mode']) && !$entity['mode']) {
            throw new \MonarcCore\Exception\Exception('Delete question is not possible', 412);
        }

        $this->get('table')->delete($id);
    }
}