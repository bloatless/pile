<?php

declare(strict_types=1);

namespace Bloatless\Pile\Domains;

use Bloatless\Endocore\Components\QueryBuilder\QueryBuilder\SelectQueryBuilder;

class LogsDomain extends DatabaseDomain
{
    /**
     * @var array $validLevels
     */
    protected $validLevels = [
        100 => 'debug',
        200 => 'info',
        250 => 'notice',
        300 => 'warning',
        400 => 'error',
        500 => 'critical',
        550 => 'alert',
        600 => 'emergency',
    ];

    /**
     * Validates if provided log-data is valid.
     *
     * @param array $data
     * @return bool
     */
    public function validateLogData(array $data): bool
    {
        // check if data is of type log
        if (empty($data['data']['type']) || $data['data']['type'] !== 'log') {
            return false;
        }

        // check if attributes are not empty
        if (empty($data['data']['attributes'])) {
            return false;
        }

        $attributes = $data['data']['attributes'];

        // check if "source" field is provided
        if (empty($attributes['source'])) {
            return false;
        }

        // check if "message" field is provided
        if (empty($attributes['message'])) {
            return false;
        }

        // check if "level" is provided an valid
        $attributes['level'] = (int) $attributes['level'];
        if (!in_array($attributes['level'], array_keys($this->validLevels))) {
            return false;
        }

        // check if "context" is of type array
        if (!empty($attributes['context']) && !is_array($attributes['context'])) {
            return false;
        }

        // check if "channel" is of type string
        if (!empty($attributes['channel']) && !is_string($attributes['channel'])) {
            return false;
        }

        // check if "extra" is of type array
        if (!empty($attributes['extra']) && !is_array($attributes['extra'])) {
            return false;
        }

        // check if datetime is valid
        if (!empty($attributes['datetime'])
            && \DateTime::createFromFormat('Y-m-d H:i:s', $attributes['datetime']) === false) {
            return false;
        }

        return true;
    }

    /**
     * Takes raw log data from requests and does some cleanup and structuring for further usage.
     *
     * @param array $logData
     * @return array
     */
    public function preprocessLogData(array $logData): array
    {
        // we only need the attributes (other data is meta stuff)
        $attributes = $logData['data']['attributes'];

        // trim sting type fields
        $attributes['source'] = trim($attributes['source']);
        $attributes['message'] = trim($attributes['message']);
        if (isset($attributes['channel'])) {
            $attributes['channel'] = trim($attributes['channel']);
        }

        // set default values
        $attributes['level_name'] = $this->validLevels[$attributes['level']];
        if (!isset($attributes['datetime'])) {
            $attributes['datetime'] = strftime('%Y-%m-%d %H:%M:%S');
        }

        // json encode array fields
        if (isset($attributes['context'])) {
            $attributes['context'] = json_encode($attributes['context']);
        }
        if (isset($attributes['extra'])) {
            $attributes['extra'] = json_encode($attributes['extra']);
        }

        // adjust field names to table columns
        $attributes['created_at'] = $attributes['datetime'];
        unset($attributes['datetime']);

        return $attributes;
    }

    /**
     * Stores log data item into database.
     *
     * @param array $logData
     * @return int
     * @throws \Bloatless\Endocore\Components\QueryBuilder\Exception\DatabaseException
     */
    public function storeLogData(array $logData): int
    {
        return $this->db->makeInsert()->into('logs')->row($logData);
    }

    /**
     * Fetches data from the logs table.
     *
     * @param array $cols
     * @param array $filters
     * @param int $limit
     * @param int $offset
     * @param string $orderBy
     * @param string $orderDirection
     * @return array
     * @throws \Bloatless\Endocore\Components\QueryBuilder\Exception\DatabaseException
     */
    public function getLogData(
        array $cols = ['*'],
        array $filters = [],
        int $limit = 0,
        int $offset = 0,
        string $orderBy = 'log_id',
        string $orderDirection = 'desc'
    ): array {
        $builder = $this->db->makeSelect()->from('logs');
        $builder = $this->applyFilters($builder, $filters);
        $logs = $builder
            ->orderBy($orderBy, $orderDirection)
            ->limit($limit)
            ->offset($offset)
            ->cols($cols)
            ->get();

        if (empty($logs)) {
            return $logs;
        }

        foreach ($logs as $i => $log) {
            if (!empty($log->context)) {
                $logs[$i]->context = json_decode($log->context, true);
            }
            if (!empty($log->extra)) {
                $logs[$i]->extra = json_decode($log->extra, true);
            }
        }

        return $logs;
    }

    /**
     * Fetches a distinct list of error-levels from the logs table.
     *
     * @return array
     * @throws \Bloatless\Endocore\Components\QueryBuilder\Exception\DatabaseException
     */
    public function getErrorLevelList(): array
    {
        $levels = $this->db->makeSelect()
            ->from('logs')
            ->distinct()
            ->cols(['level', 'level_name'])
            ->pluck('level_name', 'level');

        return $levels;
    }

    /**
     * Fetches a distinct list of sources from the logs table.
     *
     * @return array
     * @throws \Bloatless\Endocore\Components\QueryBuilder\Exception\DatabaseException
     */
    public function getSourcesList(): array
    {
        $levels = $this->db->makeSelect()
            ->from('logs')
            ->distinct()
            ->cols(['source'])
            ->pluck('source');

        return $levels;
    }

    /**
     * Counts total entries in logs table.
     *
     * @param array $filters
     * @return int
     * @throws \Bloatless\Endocore\Components\QueryBuilder\Exception\DatabaseException
     */
    public function getLogsTotal(array $filters = []): int
    {
        $builder = $this->db->makeSelect()->from('logs');
        $builder = $this->applyFilters($builder, $filters);

        return $builder->count();
    }

    /**
     * Applies filters to select-query.
     *
     * @param SelectQueryBuilder $builder
     * @param array $filters
     * @return SelectQueryBuilder
     */
    protected function applyFilters(SelectQueryBuilder $builder, array $filters): SelectQueryBuilder
    {
        if (!empty($filters['source'])) {
            $builder->whereIn('source', $filters['source']);
        }
        if (!empty($filters['level'])) {
            $builder->whereIn('level', $filters['level']);
        }

        return $builder;
    }
}
