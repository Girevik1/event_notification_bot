<?php
declare(strict_types=1);

namespace Art\Code\Domain\Contract;

use Art\Code\Domain\Dto\TelegramUserDto;
use Art\Code\Domain\Entity\TelegramUser;

//use Art\Code\Domain\ValueObject\Id;

interface TelegramUserRepositoryInterface
{
    public function firstByChatId($chatId): ?TelegramUser;

//    public function firstById($id): ?TelegramUser;

    public function firstByLogin($login): ?TelegramUser;

//    public function isExistByLogin($login): bool;

    public function create(TelegramUserDto $telegramUserDto): TelegramUser;

//    public function updateByField(TelegramUser $telegramUser, string $field, mixed $value): TelegramUser;
}