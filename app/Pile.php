<?php

declare(strict_types=1);

namespace Bloatless\Pile;

use Bloatless\Pile\Exceptions\DatabaseException;
use Bloatless\Pile\Exceptions\HttpBadRequestException;
use Bloatless\Pile\Exceptions\HttpMethodNotAllowedException;
use Bloatless\Pile\Exceptions\HttpNotFoundException;
use Bloatless\Pile\Exceptions\HttpUnauthorizedException;
use Bloatless\Pile\Exceptions\PileException;

class Pile
{
    protected int $logsPerPage = 50;

    protected string $pathViews = '';

    protected array $dbConfig = [];

    protected array $authConfig = [];

    private array $config;

    private string $contentType = self::CONTENT_TYPE_HTML;

    private \PDO $pdo;

    private array $validLevels = [
        100 => 'debug',
        200 => 'info',
        250 => 'notice',
        300 => 'warning',
        400 => 'error',
        500 => 'critical',
        550 => 'alert',
        600 => 'emergency',
    ];

    private const string CONTENT_TYPE_HTML = 'html';
    private const string CONTENT_TYPE_JSON = 'json';

    public function __construct(array $config)
    {
        $this->config = $config;
    }

    // -----------------------
    // Main Logic
    // -----------------------

    public function __invoke($request, $server): void
    {
        // @todo Remove composer (?)
        // @todo adjust config.sample
        // @todo cleanup

        try {
            $this->initConfiguration();
            $requestMethod = $server['REQUEST_METHOD'];
            $requestUri = $server['REQUEST_URI'] ?? '';
            $action = $this->route($requestUri, $requestMethod);
            $this->dispatch($action, $request, $server);
        } catch (\Exception | \Throwable $e) {
            $this->sendErrorResponse($e);
        }
    }

    protected function initConfiguration(): void
    {
        // validate database configuration
        $dbConfig = $this->config['db'] ?? null;
        if (empty($dbConfig) || !is_array($dbConfig)) {
            throw new PileException('Error: Database configuration missing. Check config file.');
        }

        if (
            empty($dbConfig['dsn'])
            || !array_key_exists('username', $dbConfig)
            || !array_key_exists('password', $dbConfig)
        ) {
            throw new PileException('Error: Invalid database configuration. Check config file.');
        }
        $this->dbConfig = $dbConfig;

        // validate auth configuration
        $authConfig = $this->config['auth'] ?? null;
        if (empty($authConfig) || !is_array($authConfig)) {
            throw new PileException('Error: Auth configuration missing. Check config file.');
        }

        if (!array_key_exists('api_keys', $authConfig) || !array_key_exists('users', $authConfig)) {
            throw new PileException('Error: Auth configuration is invalid. Check config file.');
        }
        $this->authConfig = $authConfig;

        // validate views path
        $pathViews = $this->config['path_views'] ?? null;
        $pathViews = $pathViews !== '/' ? rtrim($pathViews, '/') : '/';
        if (empty($pathViews)) {
            throw new PileException('Error: Path to views missing in config. Check config file.');
        }
        if (!file_exists($pathViews) || !is_dir($pathViews)) {
            throw new PileException('Error: Paths to views is invalid. Check config file.');
        }
        $this->pathViews = $pathViews;

        // validate "logs per page" value
        if (array_key_exists('logs_per_page', $this->config)) {
            $logsPerPage = (int) $this->config['logs_per_page'];
            $this->logsPerPage = $logsPerPage ?: 50;
        }
    }

    protected function route(string $requestUri, string $requestMethod): string
    {
        $requestUri = parse_url($requestUri, PHP_URL_PATH);

        switch ($requestUri) {
            case '/':
                if ($requestMethod !== 'GET') {
                    throw new HttpMethodNotAllowedException();
                }

                return 'showLogs';
            case '/api/v1/log':
                if ($requestMethod !== 'POST') {
                    throw new HttpMethodNotAllowedException();
                }

                return 'storeLog';
            default:
                throw new HttpNotFoundException();
        }
    }

    protected function dispatch(string $action, array $request, array $server): void
    {
        switch ($action) {
            case 'showLogs':
                $this->contentType = self::CONTENT_TYPE_HTML;
                if ($this->webRequestIsAuthorized($server) === false) {
                    $this->sendRequestAuthorizationResponse();
                }

                $this->handleShowLogsRequest($request, $server);
                break;
            case 'storeLog':
                $this->contentType = self::CONTENT_TYPE_JSON;
                if ($this->apiRequestIsAuthorized($server) === false) {
                    throw new HttpUnauthorizedException();
                }

                $this->handleStoreLogRequest();
                break;
            default:
                throw new PileException('Error: Unknown Action.');
        }
    }

    // -----------------------
    // Pile Handlers
    // -----------------------

    protected function handleShowLogsRequest(array $request, array $server): void
    {
        // collect data from request
        $urlPath = (string) parse_url($server['REQUEST_URI'], PHP_URL_PATH);
        $filters = $this->getFiltersFromRequest($request);
        $page = $this->getPageFromRequest($request);
        $offset = (($page - 1) * $this->logsPerPage);

        // connect to database
        $this->establishDbConnection($this->dbConfig);

        // validate input data
        $sources = $this->getSourcesList();
        $this->validateFilters($filters, $sources);

        // collect additional data required to generate response
        $logsTotal = $this->getLogsTotal($filters);
        $logs = $this->getLogs($filters, $this->logsPerPage, $offset);
        $levels = $this->getErrorLevelList();

        // prepare data for view
        $pagination = $this->getPagination($urlPath, $logsTotal, $this->logsPerPage, $page, $filters);

        // render view
        $html = $this->renderTemplate('logs.phtml', [
            'filters' => $filters,
            'logs' => $logs,
            'levels' => $levels,
            'sources' => $sources,
            'pagination' => $pagination,
        ]);

        // display response
        $this->sendResponse($html);
    }

    protected function handleStoreLogRequest(): void
    {
        $rawData = $this->getRequestBody();
        if (empty($rawData)) {
            throw new HttpBadRequestException('Error: Request body can not be empty.');
        }

        $logData = json_decode($rawData, true);
        if ($this->validateLogData($logData) === false) {
            throw new PileException('Error: Invalid data. Check log data format.');
        }

        $this->establishDbConnection($this->dbConfig);

        $attributes = $this->preprocessLogData($logData);
        $attributes['log_id'] = $this->storeLogData($attributes);

        $this->sendResponse(json_encode($attributes));
    }

    // -----------------------
    // Request Logic
    // -----------------------

    private function getFiltersFromRequest(array $request): array
    {
        return [
            'source' => $request['s'] ?? [],
            'level' => $request['l'] ?? [],
        ];
    }

    private function getPageFromRequest(array $request): int
    {
        $page = (int) ($request['page'] ?? 1);

        return ($page <= 0) ? 1 : $page;
    }

    private function getRequestBody(): string
    {
        $body = file_get_contents('php://input');
        if ($body === false) {
            return '';
        }

        return $body;
    }

    // -----------------------
    // Response Logic
    // -----------------------

    protected function sendResponse(string $content = '', int $code = 200): void
    {
        switch ($this->contentType) {
            case self::CONTENT_TYPE_JSON:
                header('Content-Type: application/json', true, $code);
                break;
            case self::CONTENT_TYPE_HTML:
                header('Content-Type: text/html; charset=utf-8', true, $code);
                break;
            default:
                throw new PileException('Error: Invalid content type for response.');
        }

        echo $content;
        exit;
    }

    protected function sendErrorResponse(\Throwable|\Exception $e): void
    {
        $errorMessage = $e->getMessage();
        if ($this->contentType === self::CONTENT_TYPE_JSON) {
            $errorMessage = json_encode($errorMessage);
        }

        $this->sendResponse($errorMessage, $e->getCode());
    }

    protected function sendRequestAuthorizationResponse(): void
    {
        header('WWW-Authenticate: Basic realm="Restricted access"', true, 401);
        exit;
    }

    // -----------------------
    // Authentication Logic
    // -----------------------

    private function webRequestIsAuthorized(array $server): bool
    {
        $credentials = $this->getCredentialsFromRequest($server);
        if (empty($credentials['username']) || empty($credentials['password'])) {
            return false;
        }

        return $this->validateCredentials($credentials['username'], $credentials['password']);
    }

    private function getCredentialsFromRequest(array $server): array
    {
        $credentials = [
            'username' => '',
            'password' => '',
        ];

        // Check if authentication header is present
        $authHeader = $server['HTTP_AUTHORIZATION'] ?? '';
        if (empty($authHeader)) {
            return $credentials;
        }

        // Check if authentication header is valid
        $authHeaderParts = explode(' ', $authHeader);
        if ($authHeaderParts[0] !== 'Basic') {
            return $credentials;
        }

        // Collect and return credentials
        $userPass = base64_decode($authHeaderParts[1]);
        if (!str_contains($userPass, ':')) {
            return $credentials;
        }

        $colonPos = strpos($userPass, ':');
        $credentials['username'] = trim(substr($userPass, 0, $colonPos));
        $credentials['password'] = trim(substr($userPass, $colonPos + 1));

        return $credentials;
    }

    private function validateCredentials(string $username, string $password): bool
    {
        if (empty($this->authConfig['users'])) {
            return false;
        }

        if (!array_key_exists($username, $this->authConfig['users'])) {
            return false;
        }

        return password_verify($password, $this->authConfig['users'][$username]);
    }

    private function apiRequestIsAuthorized(array $server): bool
    {
        $apiKey = (string) $server['HTTP_X_API_KEY'] ?? '';
        if ($apiKey === '') {
            return false;
        }

        return $this->apiKeyIsValid($apiKey);
    }

    private function apiKeyIsValid(string $apiKey): bool
    {
        $validKeys = $this->authConfig['api_keys'] ?? [];

        return in_array($apiKey, $validKeys);
    }

    // -----------------------
    // Validation Logic
    // -----------------------

    protected function validateFilters(array $filters, array $sources): void
    {
        if (!empty($filters['source'])) {
            foreach ($filters['source'] as $source) {
                if (!in_array($source, $sources)) {
                    throw new PileException('Error: Invalid source filter.');
                }
            }
        }

        if (!empty($filters['level'])) {
            foreach ($filters['level'] as $level) {
                if (!array_key_exists($level, $this->validLevels)) {
                    throw new PileException('Error: Invalid level filter.');
                }
            }
        }
    }

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
        if (
            !empty($attributes['datetime'])
            && \DateTime::createFromFormat('Y-m-d H:i:s', $attributes['datetime']) === false
        ) {
            return false;
        }

        return true;
    }

    // -----------------------
    // CRUD Logic
    // -----------------------

    private function establishDbConnection(array $dbConfig): void
    {
        try {
            $dsn = $dbConfig['dsn'] ?? '';
            $username = $dbConfig['username'] ?? '';
            $password = $dbConfig['password'] ?? '';

            $this->pdo = new \PDO($dsn, $username, $password);
        } catch (\PDOException $e) {
            throw new DatabaseException('Error: Could not connect to database.');
        }
    }

    private function getLogsTotal(array $filters = []): int
    {
        // get statement
        $query = $this->buildGetLogsQuery(true, $filters);

        // run query and return count
        $pdoStatement = $this->pdo->prepare($query['statement']);
        $pdoStatement->execute($query['bindings']);

        return (int) $pdoStatement->fetchColumn();
    }

    private function getLogs(array $filters = [], int $limit = 0, int $offset = 0): array
    {
        // get statement
        $query = $this->buildGetLogsQuery(false, $filters, $limit, $offset);

        // run query and return count
        $pdoStatement = $this->pdo->prepare($query['statement']);
        $pdoStatement->execute($query['bindings']);

        return $pdoStatement->fetchAll(\PDO::FETCH_OBJ);
    }

    private function buildGetLogsQuery(bool $count, array $filters = [], int $limit = 0, int $offset = 0): array
    {
        $wheres = [];
        $bindings = [];
        if (!empty($filters['source'])) {
            $placeholders = str_repeat('?, ', count($filters['source']) - 1) . '?';
            $wheres[] = "`source` IN ($placeholders)";
            $bindings = $filters['source'];
        }
        if (!empty($filters['level'])) {
            $placeholders = str_repeat('?, ', count($filters['level']) - 1) . '?';
            $wheres[] = "`level` IN ($placeholders)";
            $bindings = array_merge($bindings, $filters['level']);
        }

        // build statement
        $statement = ($count === true) ? "SELECT COUNT(*) FROM `logs`" : "SELECT * FROM `logs`";

        if ($wheres !== []) {
            $statement .= ' WHERE ' . implode(' AND ', $wheres);
        }

        if ($count === false) {
            $statement .= " ORDER BY `log_id` DESC";
            $statement .= " LIMIT " . $offset . ", " . $limit;
        }

        return [
            'statement' => $statement,
            'bindings' => $bindings,
        ];
    }

    private function getErrorLevelList(): array
    {
        $statement = "SELECT DISTINCT `level`, `level_name` FROM `logs`";
        $pdoStatement = $this->pdo->query($statement);
        $rows = $pdoStatement->fetchAll();
        $levels = [];
        foreach ($rows as $row) {
            $levels[$row['level']] = $row['level_name'];
        }

        return  $levels;
    }

    private function getSourcesList(): array
    {
        $statement = "SELECT DISTINCT `source` FROM `logs`";
        $pdoStatement = $this->pdo->query($statement);

        return $pdoStatement->fetchAll(\PDO::FETCH_COLUMN);
    }

    public function storeLogData(array $logData): int
    {
        $statement = "
            INSERT INTO `logs`
                (`source`, `message`, `context`, `level`, `level_name`, `channel`, `extra`, `created_at`)
            VALUES (:source, :message, :context, :level, :level_name, :channel, :extra, :created_at)";

        $pdoStatement = $this->pdo->prepare($statement);
        $pdoStatement->bindValue(':source', $logData['source']);
        $pdoStatement->bindValue(':message', $logData['message']);
        $pdoStatement->bindValue(':context', $logData['context']);
        $pdoStatement->bindValue(':level', $logData['level'], \PDO::PARAM_INT);
        $pdoStatement->bindValue(':level_name', $logData['level_name']);
        $pdoStatement->bindValue(':channel', $logData['channel']);
        $pdoStatement->bindValue(':extra', $logData['extra']);
        $pdoStatement->bindValue(':created_at', $logData['created_at']);

        $pdoStatement->execute();

        return (int) $this->pdo->lastInsertId();
    }

    // -----------------------
    // View/Preparation Logic
    // -----------------------

    private function getPagination(
        string $urlPath,
        int $itemsTotal,
        int $itemsPerPage,
        int $currentPage,
        array $filters
    ): array {
        $pages = (int) ceil($itemsTotal / $itemsPerPage);
        $pagination = [
            'pages' => $pages,
            'total' => $itemsTotal,
            'current' => $currentPage,
        ];

        if ($pages < 1) {
            return $pagination;
        }

        $pagination['first'] = $this->buildUrl($urlPath, $filters, 1);
        $pagination['last'] = $this->buildUrl($urlPath, $filters, $pages);
        $pagination['prev'] = '';
        if ($currentPage > 1) {
            $pagination['prev'] = $this->buildUrl($urlPath, $filters, $currentPage - 1);
        }
        $pagination['next'] = '';
        if ($currentPage < $pages) {
            $pagination['next'] = $this->buildUrl($urlPath, $filters, $currentPage + 1);
        }

        return $pagination;
    }

    protected function buildUrl(string $path = '/', array $filters = [], int $page = 0): string
    {
        $urlParams = [];
        if (!empty($filters['source'])) {
            $urlParams['s'] = $filters['source'];
        }
        if (!empty($filters['level'])) {
            $urlParams['l'] = $filters['level'];
        }
        if ($page > 0) {
            $urlParams['page'] = $page;
        }

        $query = preg_replace('/%5B[0-9]+%5D/imU', '%5B%5D', http_build_query($urlParams));

        return $path . '?' . $query;
    }

    protected function preprocessLogData(array $logData): array
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
            $attributes['datetime'] = date('Y-m-d H:i:s');
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

    private function renderTemplate(string $templateFile, array $payload): string
    {
        $templateFile = ltrim($templateFile, '/');
        $pathToTemplate = $this->pathViews . '/' . $templateFile;
        extract($payload);

        ob_start();
        if (!file_exists($pathToTemplate)) {
            throw new PileException('Error: View file not found.');
        }

        include $pathToTemplate;

        return ob_get_clean();
    }
}
