<?php

namespace Tests;

use Illuminate\Foundation\Testing\TestCase as BaseTestCase;
use Illuminate\Support\Facades\DB;
use RuntimeException;

abstract class TestCase extends BaseTestCase
{
    public function createApplication()
    {
        $app = parent::createApplication();

        // Safety enforcement: Force test database and array cache store
        $app['config']->set('database.default', 'mysql');
        $app['config']->set('database.connections.mysql.database', 'microfinance_erp_test');
        $app['config']->set('cache.default', 'array');

        DB::purge('mysql');

        // Safety Circuit Breaker: Fail immediately if testing against microfinance_erp
        $dbConn = $app['config']->get('database.default');
        $dbName = $app['config']->get("database.connections.{$dbConn}.database");

        if ($dbName === 'microfinance_erp') {
            throw new RuntimeException(
                'CRITICAL SAFETY STOP: Automated tests CANNOT run against the development ERP database (microfinance_erp). ' .
                'Tests must ONLY run against microfinance_erp_test.'
            );
        }

        return $app;
    }
}
