<?php
declare(strict_types=1);

namespace Art\Code\Domain\Contract;

use Art\Code\Application\Dto\TelegramUserDto;
use Art\Code\Domain\Entity\TelegramUser;
//use Art\Code\Domain\ValueObject\Id;

interface TelegramUserRepositoryInterface
{
    public function firstByChatId($chatId): ?TelegramUser;

    public function firstById($id): ?TelegramUser;

    public function firstByLogin($id): ?TelegramUser;

    public function isExistByLogin($id): bool;

    public function create(TelegramUserDto $telegramUserDto);
}