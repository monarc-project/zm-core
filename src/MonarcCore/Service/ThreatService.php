<?php
namespace MonarcCore\Service;

use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\ORM\EntityRepository;
use DoctrineModule\Persistence\ObjectManagerAwareInterface;
use DoctrineModule\Persistence\ProvidesObjectManager;
use MonarcCore\Model\Entity\Threat;

/**
 * Threat Service
 *
 * Class ThreatService
 * @package MonarcCore\Service
 */
class ThreatService extends AbstractService implements ObjectManagerAwareInterface
{
    use ProvidesObjectManager;

    protected $modelService;

    protected $themeService;

    protected $repository;

    /**
     * @return mixed
     */
    public function getModelService()
    {
        return $this->modelService;
    }

    /**
     * @param mixed $modelService
     * @return AssetService
     */
    public function setModelService($modelService)
    {
        $this->modelService = $modelService;
        return $this;
    }

    /**
     * @return mixed
     */
    public function getThemeService()
    {
        return $this->themeService;
    }

    /**
     * @param mixed $themeService
     * @return ThreatService
     */
    public function setThemeService($themeService)
    {
        $this->themeService = $themeService;
        return $this;
    }

    /**
     * @return EntityRepository
     */
    public function getRepository()
    {
        if(!$this->repository) {
            $this->repository = $this->objectManager->getRepository(Threat::class);
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

        $assetEntity = new Asset();
        $assetEntity = $this->addModel($assetEntity, $data);
        $assetEntity->exchangeArray($data);

        return $this->save($assetEntity);
    }

    /**
     * Update Entity
     *
     * @param $id
     * @param $data
     */
    public function update($id, $data) {

        $assetEntity = $this->getEntity($id);
        $assetEntity->setModels(new ArrayCollection());
        $assetEntity = $this->addModel($assetEntity, $data);
        $assetEntity->exchangeArray($data);

        $connectedUser = trim($this->getConnectedUser()['firstname'] . " " . $this->getConnectedUser()['lastname']);

        $assetEntity->set('updater', $connectedUser);
        $assetEntity->set('updatedAt',new \DateTime());

        $this->objectManager->persist($assetEntity);
        $this->objectManager->flush();
    }

    /**
     * Delete
     *
     * @param $id
     */
    public function delete($id) {
        $entity = $this->getEntity($id);

        $this->objectManager->remove($entity);
        $this->objectManager->flush();
    }
}