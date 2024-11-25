<?php

declare(strict_types=1);

namespace Bloatless\Pile\Tests\Fixtures;

class DatabaseFixture
{
    protected \PDO $pdo;

    public function __construct(
        protected array $config
    ) {
        // constructor body
    }

    public function __invoke(): void
    {
        $this->establishDbConnection($this->config['db']);
        $this->migrateDatabaseStructure();
        $this->seedTestData();
    }

    private function migrateDatabaseStructure(): void
    {
        $structureSql = PATH_ROOT . '/db_structure.sql';
        $queries = $this->loadQueriesFromFile($structureSql);

        foreach ($queries as $query) {
            $this->pdo->prepare($query)->execute();
        }
    }

    private function seedTestData()
    {
        $seedSql = PATH_TESTS . '/Fixtures/db_content.sql';
        $queries = $this->loadQueriesFromFile($seedSql);

        foreach ($queries as $query) {
            $this->pdo->prepare($query)->execute();
        }
    }

    protected function establishDbConnection(array $dbConfig): void
    {
        $dsn = $dbConfig['dsn'] ?? '';
        $username = $dbConfig['username'] ?? '';
        $password = $dbConfig['password'] ?? '';

        $this->pdo = new \PDO($dsn, $username, $password);
    }

    private function loadQueriesFromFile(string $file): array
    {
        $queries = [];
        $i = 0;
        foreach (file($file) as $line) {
            $line = trim($line);

            if ($line === '') {
                continue;
            }

            if (str_starts_with($line, '--')) {
                $i++;
                $queries[$i] = '';
                continue;
            }

            $queries[$i] .= $line;
        }

        return $queries;
    }
}
