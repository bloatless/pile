<?php

declare(strict_types=1);

namespace Bloatless\Pile\Domains;

use Bloatless\Endocore\Components\Logger\LoggerInterface;
use Bloatless\Endocore\Components\QueryBuilder\Factory as QueryBuilderFactory;

abstract class DatabaseDomain
{
    /**
     * @var array $config
     */
    protected $config;

    /**
     * @var LoggerInterface $logger
     */
    protected $logger;

    /**
     * @var QueryBuilderFactory $db
     */
    protected $db;

    public function __construct(array $config, LoggerInterface $logger)
    {
        $this->config = $config;
        $this->logger = $logger;
        $this->db = new QueryBuilderFactory($config['db']);
    }
}
