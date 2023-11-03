<?php

declare(strict_types=1);

use PHPUnit\Framework\TestCase;

class PostgreSqlContainerTest extends TestCase
{
    public function test_connect_to_postgresql_with_pdo()
    {
        $db_host = $_ENV['db_host'];
        $db_name = $_ENV['db_database'];
        $db_user = $_ENV['db_username'];
        $db_password = $_ENV['db_password'];
        $pdo = new PDO(
            "pgsql:host=$db_host;dbname=$db_name;",
            $db_user,
            $db_password
        );
        $this->assertInstanceOf(PDO::class, $pdo);
    }

}