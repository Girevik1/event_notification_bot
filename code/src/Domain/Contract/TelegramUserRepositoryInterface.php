<?php
declare(strict_types=1);

namespace Art\Code\Domain\Contract;

use Art\Code\Domain\Entity\TelegramUser;
//use Art\Code\Domain\ValueObject\Id;

interface TelegramUserRepositoryInterface
{
    public function nextId();

    public function firstById($id): ?TelegramUser;
}