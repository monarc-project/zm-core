<?php
namespace MonarcCore\Service;

use Doctrine\ORM\EntityRepository;
use DoctrineModule\Persistence\ObjectManagerAwareInterface;
use DoctrineModule\Persistence\ProvidesObjectManager;
use MonarcCore\Model\Entity\Theme;

/**
 * Theme Service
 *
 * Class ThemeService
 * @package MonarcCore\Service
 */
class ThemeService extends AbstractService implements ObjectManagerAwareInterface
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
            $this->repository = $this->objectManager->getRepository(Theme::class);
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

        return count($this->getRepository()->findBy($filter));
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
        $columns = array(
            'label1', 'label2', 'label3', 'label4'
        );

        $filter = $this->parseFrontendFilter($filter, $columns);

        $order = $this->parseFrontendOrder($order);

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

        $themeEntity = new Theme();
        $themeEntity->exchangeArray($data);

        return $this->save($themeEntity);
    }
}