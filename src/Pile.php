<?php

declare(strict_types=1);

namespace Bloatless\Pile;

use Bloatless\Pile\Exceptions\CommandException;
use Bloatless\Pile\Exceptions\DatabaseException;
use Bloatless\Pile\Exceptions\HttpBadRequestException;
use Bloatless\Pile\Exceptions\HttpMethodNotAllowedException;
use Bloatless\Pile\Exceptions\HttpNotFoundException;
use Bloatless\Pile\Exceptions\HttpUnauthorizedException;
use Bloatless\Pile\Exceptions\PileException;
use DateTime;
use Exception;
use PDO;
use PDOException;
use Throwable;

class Pile
{
    private const string CONTENT_TYPE_HTML = 'html';

    private const string CONTENT_TYPE_JSON = 'json';

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

    protected int $logsPerPage = 50;

    protected int $keepLogsDays = 360;

    protected string $pathViews = '';

    protected array $dbConfig = [];

    protected array $authConfig = [];

    private array $config;

    private string $contentType = self::CONTENT_TYPE_HTML;

    private array $htmlEntitiesCache = [];

    private PDO $pdo;



    // -----------------------
    // Main Logic
    // -----------------------

    public function __construct(array $config)
    {
        $this->config = $config;
        spl_autoload_register(array($this, 'registerAutoload'));
    }

    public function __invoke($request, $server): string
    {
        // @todo adjust config.sample
        // @todo update readme
        // @todo cleanup functionality

        try {
            $this->initConfiguration();
            $requestMethod = $server['REQUEST_METHOD'];
            $requestUri = $server['REQUEST_URI'] ?? '';
            $action = $this->route($requestUri, $requestMethod);

            return $this->dispatch($action, $request, $server);
        } catch (Exception | Throwable $e) {
            return $this->sendErrorResponse($e);
        }
    }

    public function runCommand(string $command): int
    {
        try {
            $this->initConfiguration();

            switch ($command) {
                case 'cleanup':
                    $this->handleCleanupCommand();
                    return 0;
                default:
                    throw new CommandException('Unknown command.');
            }
        } catch (CommandException $e) {
            echo 'Error: ' . $e->getMessage() . PHP_EOL;
            return 1;
        } catch (Exception | Throwable $e) {
            printf("Error: %s (File: %s, Line: %d\n", $e->getMessage(), $e->getFile(), $e->getLine());
            return 1;
        }
    }

    protected function registerAutoload(): void
    {
        spl_autoload_register(function ($class) {
            // only load classes in project namespace
            $namespace = 'Bloatless\\Pile\\';
            $len = strlen($namespace);
            if (strncmp($namespace, $class, $len) !== 0) {
                return;
            }

            // generate filename from classname and include file
            $relativeClass = substr($class, $len);
            $file = __DIR__ . '/' . str_replace('\\', '/', $relativeClass) . '.php';
            if (file_exists($file)) {
                require_once $file;
            }
        });
    }

    /**
     * @throws PileException
     */
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
        $pathViews = $this->config['path_views'] ?? '';
        $pathViews = $pathViews !== '/' ? rtrim($pathViews, '/') : '/';
        if (empty($pathViews)) {
            throw new PileException('Error: Path to views missing in config. Check config file.');
        }
        if (!file_exists($pathViews) || !is_dir($pathViews)) {
            throw new PileException('Error: Paths to views is invalid. Check config file.');
        }
        $this->pathViews = $pathViews;

        // validate single values
        if (array_key_exists('logs_per_page', $this->config)) {
            $logsPerPage = (int) $this->config['logs_per_page'];
            $this->logsPerPage = $logsPerPage ?: $this->logsPerPage;
        }

        if (array_key_exists('keep_logs_days', $this->config)) {
            $keepLogsDays = (int) $this->config['keep_logs_days'];
            $this->keepLogsDays = $keepLogsDays ?: $this->keepLogsDays;
        }
    }

    /**
     * @throws HttpNotFoundException
     * @throws HttpMethodNotAllowedException
     */
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

    /**
     * @throws DatabaseException
     * @throws PileException
     * @throws HttpUnauthorizedException
     * @throws HttpBadRequestException
     */
    protected function dispatch(string $action, array $request, array $server): string
    {
        switch ($action) {
            case 'showLogs':
                $this->contentType = self::CONTENT_TYPE_HTML;
                if ($this->webRequestIsAuthorized($server) === false) {
                    return $this->sendRequestAuthorizationResponse();
                }

                return $this->handleShowLogsRequest($request, $server);
            case 'storeLog':
                $this->contentType = self::CONTENT_TYPE_JSON;
                if ($this->apiRequestIsAuthorized($server) === false) {
                    throw new HttpUnauthorizedException();
                }

                return $this->handleStoreLogRequest();
            default:
                throw new PileException('Error: Unknown Action.');
        }
    }

    // -----------------------
    // Pile Handlers
    // -----------------------

    /**
     * @throws PileException
     * @throws DatabaseException
     */
    protected function handleShowLogsRequest(array $request, array $server): string
    {
        // collect data from request
        $urlPath = (string) parse_url($server['REQUEST_URI'], PHP_URL_PATH);
        $filters = $this->getFiltersFromRequest($request);
        $page = $this->getPageFromRequest($request);

        // connect to database
        $this->establishDbConnection($this->dbConfig);

        // validate input data
        $sources = $this->getSourcesList();
        $this->validateFilters($filters, $sources);

        // collect additional data required to generate response
        $offset = (($page - 1) * $this->logsPerPage);
        $logsTotal = $this->getLogsTotal($filters);
        $logs = $this->getLogs($filters, $this->logsPerPage, $offset);
        $levels = $this->getErrorLevelList();

        // prepare data for view
        $logs = $this->prepareLogsForView($logs);
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
        return $this->sendResponse($html);
    }

    /**
     * @throws HttpBadRequestException
     * @throws DatabaseException
     * @throws PileException
     */
    protected function handleStoreLogRequest(): string
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

        return $this->sendResponse(json_encode($attributes));
    }

    /**
     * @throws DatabaseException
     */
    protected function handleCleanupCommand(): void
    {
        printf("Deleting logs older than %d days...\n", $this->keepLogsDays);

        $this->establishDbConnection($this->dbConfig);
        $rowsDeleted = $this->deleteLogsOlderThanDays($this->keepLogsDays);

        printf("Done. %d rows deleted\n", $rowsDeleted);
    }

    // -----------------------
    // Request Logic
    // -----------------------

    protected function getFiltersFromRequest(array $request): array
    {
        return [
            'source' => $request['s'] ?? [],
            'level' => $request['l'] ?? [],
        ];
    }

    protected function getPageFromRequest(array $request): int
    {
        $page = (int) ($request['page'] ?? 1);

        return ($page <= 0) ? 1 : $page;
    }

    protected function getRequestBody(): string
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

    protected function sendResponse(string $content = '', int $code = 200): string
    {
        switch ($this->contentType) {
            case self::CONTENT_TYPE_JSON:
                header('Content-Type: application/json', true, $code);
                break;
            case self::CONTENT_TYPE_HTML:
                header('Content-Type: text/html; charset=utf-8', true, $code);
                break;
        }

        return $content;
    }

    protected function sendErrorResponse(Throwable|Exception $e): string
    {
        $errorMessage = $e->getMessage();
        if ($this->contentType === self::CONTENT_TYPE_JSON) {
            $errorMessage = json_encode($errorMessage);
        }

        return $this->sendResponse($errorMessage, $e->getCode());
    }

    protected function sendRequestAuthorizationResponse(): string
    {
        header('WWW-Authenticate: Basic realm="Restricted access"', true, 401);

        return 'Authorization required. Please log in.';
    }

    // -----------------------
    // Authentication Logic
    // -----------------------

    protected function webRequestIsAuthorized(array $server): bool
    {
        $credentials = $this->getCredentialsFromRequest($server);
        if (empty($credentials['username']) || empty($credentials['password'])) {
            return false;
        }

        return $this->validateCredentials($credentials['username'], $credentials['password']);
    }

    protected function getCredentialsFromRequest(array $server): array
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

    protected function validateCredentials(string $username, string $password): bool
    {
        if (empty($this->authConfig['users'])) {
            return false;
        }

        if (!array_key_exists($username, $this->authConfig['users'])) {
            return false;
        }

        return password_verify($password, $this->authConfig['users'][$username]);
    }

    protected function apiRequestIsAuthorized(array $server): bool
    {
        $apiKey = (string) $server['HTTP_X_API_KEY'] ?? '';
        if ($apiKey === '') {
            return false;
        }

        return $this->apiKeyIsValid($apiKey);
    }

    protected function apiKeyIsValid(string $apiKey): bool
    {
        $validKeys = $this->authConfig['api_keys'] ?? [];

        return in_array($apiKey, $validKeys);
    }

    // -----------------------
    // Validation Logic
    // -----------------------

    /**
     * @throws PileException
     */
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

    protected function validateLogData(array $data): bool
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
            && DateTime::createFromFormat('Y-m-d H:i:s', $attributes['datetime']) === false
        ) {
            return false;
        }

        return true;
    }

    // -----------------------
    // CRUD Logic
    // -----------------------

    /**
     * @throws DatabaseException
     */
    protected function establishDbConnection(array $dbConfig): void
    {
        try {
            $dsn = $dbConfig['dsn'] ?? '';
            $username = $dbConfig['username'] ?? '';
            $password = $dbConfig['password'] ?? '';

            $this->pdo = new PDO($dsn, $username, $password);
        } catch (PDOException) {
            throw new DatabaseException('Error: Could not connect to database.');
        }
    }

    protected function getLogsTotal(array $filters = []): int
    {
        // get statement
        $query = $this->buildGetLogsQuery(true, $filters);

        // run query and return count
        $pdoStatement = $this->pdo->prepare($query['statement']);
        $pdoStatement->execute($query['bindings']);

        return (int) $pdoStatement->fetchColumn();
    }

    protected function getLogs(array $filters = [], int $limit = 0, int $offset = 0): array
    {
        // get statement
        $query = $this->buildGetLogsQuery(false, $filters, $limit, $offset);

        // run query and return count
        $pdoStatement = $this->pdo->prepare($query['statement']);
        $pdoStatement->execute($query['bindings']);

        return $pdoStatement->fetchAll(PDO::FETCH_OBJ);
    }

    protected function buildGetLogsQuery(bool $count, array $filters = [], int $limit = 0, int $offset = 0): array
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

    protected function getErrorLevelList(): array
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

    protected function getSourcesList(): array
    {
        $statement = "SELECT DISTINCT `source` FROM `logs`";
        $pdoStatement = $this->pdo->query($statement);

        return $pdoStatement->fetchAll(PDO::FETCH_COLUMN);
    }

    protected function storeLogData(array $logData): int
    {
        $statement = "
            INSERT INTO `logs`
                (`source`, `message`, `context`, `level`, `level_name`, `channel`, `extra`, `created_at`)
            VALUES (:source, :message, :context, :level, :level_name, :channel, :extra, :created_at)";

        $pdoStatement = $this->pdo->prepare($statement);
        $pdoStatement->bindValue(':source', $logData['source']);
        $pdoStatement->bindValue(':message', $logData['message']);
        $pdoStatement->bindValue(':context', $logData['context']);
        $pdoStatement->bindValue(':level', $logData['level'], PDO::PARAM_INT);
        $pdoStatement->bindValue(':level_name', $logData['level_name']);
        $pdoStatement->bindValue(':channel', $logData['channel']);
        $pdoStatement->bindValue(':extra', $logData['extra']);
        $pdoStatement->bindValue(':created_at', $logData['created_at']);

        $pdoStatement->execute();

        return (int) $this->pdo->lastInsertId();
    }

    protected function deleteLogsOlderThanDays(int $days): int
    {
        $statement = 'DELETE FROM `logs` WHERE DATEDIFF(now(), created_at) > :days';
        $pdoStatement = $this->pdo->prepare($statement);
        $pdoStatement->bindValue(':days', $days);
        $pdoStatement->execute();

        return $pdoStatement->rowCount();
    }

    // -----------------------
    // View/Preparation Logic
    // -----------------------

    protected function prepareLogsForView(array $logs): array
    {
        foreach ($logs as $log) {
            $log->context = $log->context === '[]' ? '' : $log->context;
            $log->context = !empty($log->context) ? json_decode($log->context) : '';

            $log->extra = $log->extra === '[]' ? '' : $log->extra;
            $log->extra = !empty($log->extra) ? json_decode($log->extra) : '';
        }

        return $logs;
    }

    protected function strOut(string $input, int $maxLength = 0): string
    {
        if ($maxLength > 0) {
            $input = mb_strimwidth($input, 0, $maxLength, '…');
        }

        if (isset($this->htmlEntitiesCache[$input])) {
            return $this->htmlEntitiesCache[$input];
        }

        $encoded = htmlentities($input);
        $this->htmlEntitiesCache[$input] = $encoded;

        return $encoded;
    }

    protected function jsonOut(mixed $input): string
    {
        return $this->strOut(print_r($input, true));
    }

    protected function getPagination(
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

    /**
     * @throws PileException
     */
    protected function renderTemplate(string $templateFile, array $payload): string
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
