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
    protected $forbiddenFields = ['anr'];

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
        $this->filterPatchFields($data);

        parent::patch($id, $data);
    }
}