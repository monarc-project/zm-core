<?php
namespace MonarcCore\Service;

use MonarcCore\Model\Table\ModelTable;
use MonarcCore\Model\Entity\Model;

/**
 * Model Service
 *
 * Class ModelService
 * @package MonarcCore\Service
 */
class ModelService extends AbstractService
{
    protected $filterColumns = array(
        'label1', 'label2', 'label3', 'label4',
        'description1', 'description2', 'description3', 'description4',
    );
}