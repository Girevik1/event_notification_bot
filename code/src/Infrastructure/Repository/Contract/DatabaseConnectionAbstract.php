<?php
declare(strict_types=1);

namespace Art\Code\Infrastructure\Repository\Contract;

abstract class DatabaseConnectionAbstract
{
    abstract static function getInstance();
}