<?php
namespace MonarcCore\Service;

/**
 * Object Risk Service
 *
 * Class ObjectRiskService
 * @package MonarcCore\Service
 */
class ObjectRiskService extends AbstractService
{
    protected $objectTable;
    protected $amvTable;
    protected $assetTable;
    protected $threatTable;
    protected $vulnerabilityTable;

    protected $dependencies = ['object', 'amv', 'asset', 'threat', 'vulnerability'];
}