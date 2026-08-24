<?php

namespace Tests;

use Illuminate\Foundation\Testing\TestCase as BaseTestCase;
use RuntimeException;

abstract class TestCase extends BaseTestCase
{
    protected function refreshDatabase(): void
    {
        $environment = app()->environment();
        $connectionName = config('database.default');
        $databaseName = (string) config("database.connections.{$connectionName}.database");

        $looksLikeTestDatabase = preg_match('/(?:_test|test)$/i', $databaseName) === 1
            || str_contains(strtolower($databaseName), 'testing');

        if ($environment !== 'testing' || !$looksLikeTestDatabase) {
            throw new RuntimeException(
                "Refusing to refresh database outside a dedicated test database. ".
                "Current env: {$environment}, connection: {$connectionName}, database: {$databaseName}"
            );
        }

        parent::refreshDatabase();
    }
}
