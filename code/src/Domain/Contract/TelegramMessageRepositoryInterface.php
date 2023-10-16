<?php
declare(strict_types=1);

namespace Art\Code\Domain\Contract;

//use Art\Code\Domain\ValueObject\Id;

interface TelegramMessageRepositoryInterface
{
    public function create($message);
}