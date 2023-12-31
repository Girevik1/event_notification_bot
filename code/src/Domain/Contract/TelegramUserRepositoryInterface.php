<?php
declare(strict_types=1);

namespace Art\Code\Domain\Contract;

use Art\Code\Domain\Dto\TelegramUserDto;
use Art\Code\Domain\Entity\TelegramUser;

interface TelegramUserRepositoryInterface
{
    public function firstByChatId(string $chatId): ?TelegramUser;

    public function firstById(int $id): ?TelegramUser;

    public function firstByLogin(string $login): ?TelegramUser;

    public function create(TelegramUserDto $telegramUserDto): TelegramUser;
}