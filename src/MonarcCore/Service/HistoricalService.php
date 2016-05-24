<?php
namespace MonarcCore\Service;

/**
 * Historical Service
 *
 * Class HistoricalService
 * @package MonarcCore\Service
 */
class HistoricalService extends AbstractService
{
    protected $filterColumns = array(
        'type', 'action',
        'label1', 'label2', 'label3', 'label4',
        'author'
    );
}