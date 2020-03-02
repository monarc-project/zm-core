<?php

namespace Monarc\Core\Log;

use Laminas\Log\Logger;
use Doctrine\DBAL\Logging\DebugStack;

class SqlLogger extends DebugStack
{
    protected $logger;

    /**
     * If Debug Stack is enabled (log queries) or not.
     *
     * @var boolean
     */
    public $enabled = false;

    public function __construct(Logger $logger)
    {
        $this->logger = $logger;
    }

    public function stopQuery()
    {
        parent::stopQuery();
    }

    public function startQuery($sql, array $params = null, array $types = null)
    {
        if($this->enabled){
            parent::startQuery($sql, $params, $types);

            $q = $this->queries[$this->currentQuery];
            $this->logger->debug(print_r($q,true));
        }
    }
}
