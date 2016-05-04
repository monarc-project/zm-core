<?php
namespace MonarcCore\Service;

use Doctrine\ORM\EntityRepository;
use DoctrineModule\Persistence\ObjectManagerAwareInterface;
use DoctrineModule\Persistence\ProvidesObjectManager;
use MonarcCore\Model\Entity\Model;

/**
 * Model Service
 *
 * Class ModelService
 * @package MonarcCore\Service
 */
class ModelService extends AbstractService implements ObjectManagerAwareInterface
{
    use ProvidesObjectManager;

    /**
     * @var EntityRepository
     */
    protected $repository;

    /**
     * @return EntityRepository
     */
    public function getRepository()
    {
        if(!$this->repository) {
            $this->repository = $this->objectManager->getRepository(Model::class);
        }
        return $this->repository;
    }

    /**
     * Get Filtered Count
     *
     * @param null $filter
     * @return int
     */
    public function getFilteredCount($filter = null) {

        $filter = $this->parseFrontendFilter($filter);
        $filter['isDeleted'] = 0;

        return count($this->getRepository()->findBy(
            $filter
        ));
    }

    /**
     * Get List
     *
     * @param int $page
     * @param int $limit
     * @param null $order
     * @param null $filter
     * @return array
     */
    public function getList($page = 1, $limit = 25, $order = null, $filter = null){

        $filter = $this->parseFrontendFilter($filter);
        $filter['isDeleted'] = 0;
        
        $order = $this->parseFrontOrder($order);

        if (is_null($page)) {
            $page = 1;
        }

        return $this->getRepository()->findBy(
            $filter,
            $order,
            $limit,
            ($page - 1) * $limit
        );
    }

    /**
     * Get Entity
     *
     * @param $id
     * @return array
     */
    public function getEntity($id){

        return $this->getRepository()->find($id);
    }

    /**
     * Create
     *
     * @param $data
     * @throws \Exception
     */
    public function create($data) {

        $modelEntity = new Model();
        $modelEntity->exchangeArray($data);

        $this->objectManager->persist($modelEntity);
        $this->objectManager->flush();
    }

    /**
     * Update
     *
     * @param $id
     * @param $data
     */
    public function update($id, $data) {

        $modelEntity = $this->getEntity($id);
        $modelEntity->exchangeArray($data);

        $this->objectManager->persist($modelEntity);
        $this->objectManager->flush();
    }

    /**
     * Delete
     *
     * @param $id
     */
    public function delete($id) {

        $modelEntity = $this->getEntity($id);
        $modelEntity->setIsDeleted(1);

        $this->objectManager->persist($modelEntity);
        $this->objectManager->flush();
    }
}