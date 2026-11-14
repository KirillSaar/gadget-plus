<?php

namespace Tests;

use Illuminate\Foundation\Testing\TestCase as BaseTestCase;

abstract class TestCase extends BaseTestCase
{
    /**
     * Защита: RefreshDatabase стирает все таблицы, поэтому тесты нельзя
     * запускать на рабочей базе (shop_laravel).
     */
    protected function refreshApplication(): void
    {
        parent::refreshApplication();

        $connection = config('database.default');
        $database = (string) config("database.connections.{$connection}.database");

        if ($connection !== 'sqlite' && ! str_ends_with($database, '_test')) {
            throw new \RuntimeException(
                "Тесты нельзя запускать на базе «{$database}». "
                .'Используйте SQLite в памяти (phpunit.xml) или отдельную базу с именем *_test.'
            );
        }
    }
}
