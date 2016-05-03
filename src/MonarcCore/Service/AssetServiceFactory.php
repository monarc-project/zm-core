<?php
namespace MonarcCore\Service;

class AssetServiceFactory extends AbstractServiceFactory
{
    protected $ressources = array(
        'assetTable'=> '\MonarcCore\Model\Table\AssetTable',
    );
}
