<?php
namespace MonarcCore\Service;

/**
 * Instance Risk Service
 *
 * Class InstanceRiskService
 * @package MonarcCore\Service
 */
class InstanceRiskService extends AbstractService
{
    protected $dependencies = ['anr', 'amv', 'asset', 'instance', 'threat', 'vulnerability'];

    protected $anrTable;
    protected $amvTable;
    protected $assetTable;
    protected $instanceTable;
    protected $threatTable;
    protected $vulnerabilityTable;
}