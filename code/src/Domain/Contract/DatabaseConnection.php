<?php
declare(strict_types=1);

namespace Art\Code\Domain\Contract;

abstract class DatabaseConnection
{
    abstract static function getInstance();
}