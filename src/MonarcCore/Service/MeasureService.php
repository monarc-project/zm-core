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
    protected $filterColumns = array(
        'description1', 'description2', 'description3', 'description4',
        'code', 'status'
    );

    /**
     * Patch
     *
     * @param $id
     * @param $data
     * @return mixed
     */
    public function patch($id,$data)
    {
        //security
        $this->filterPatchFields($data, ['anr']);

        parent::patch($id, $data);
    }
}