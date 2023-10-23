<?php
declare(strict_types=1);

namespace Art\Code\Domain\Contract;

use Art\Code\Domain\Dto\TelegramGroupDto;
use Art\Code\Domain\Entity\TelegramGroup;

interface TelegramGroupRepositoryInterface
{
    public function create(TelegramGroupDto $telegramGroupDto): TelegramGroup;

    public function getListByUser(string $userChatId):TelegramGroup;

    public function deleteByChatId(string $chatId): bool;
}