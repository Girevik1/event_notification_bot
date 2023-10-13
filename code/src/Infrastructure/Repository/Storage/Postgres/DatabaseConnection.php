<?php

declare(strict_types=1);

namespace Art\Code\Infrastructure\Repository\Storage\Postgres;

use Art\Code\Infrastructure\Repository\Contract\DatabaseConnectionAbstract;
use Exception;
use Illuminate\Container\Container;
use Illuminate\Database\Capsule\Manager;
use Illuminate\Events\Dispatcher;

class DatabaseConnection extends DatabaseConnectionAbstract
{
    private static $dbInstance = null;

    // Prevent from creating instance
    private function __construct()
    {
    }

    // Prevent cloning the object
    private function __clone()
    {
    }

    public static function getInstance(): ?Manager
    {
        // Check if database is null
        if (self::$dbInstance == null) {
            try {
                self::$dbInstance = new Manager();

                self::$dbInstance->addConnection([
                    'driver' => $_ENV['DB_DRIVER'],
                    'host' => $_ENV['DB_HOST'],
                    'database' => $_ENV['DB_DATABASE'],
                    'username' => $_ENV['DB_USERNAME'],
                    'password' => $_ENV['DB_PASSWORD'],
                    'charset' => 'utf8',
                    'collation' => 'utf8_unicode_ci',
                    'prefix' => '',
                ]);
                // Set the event dispatcher used by Eloquent models... (optional)
                // use Illuminate\Events\Dispatcher;
                // use Illuminate\Container\Container;
                // self::$dbInstance->setEventDispatcher(new Dispatcher(new Container));

                // Make this Capsule instance available globally via static methods... (optional)
                self::$dbInstance->setAsGlobal();

                // Setup the Eloquent ORM... (optional; unless you've used setEventDispatcher())
                self::$dbInstance->bootEloquent();

            } catch (Exception $e) {
                echo $e->getMessage();
            }
        }
        return self::$dbInstance;
    }
}