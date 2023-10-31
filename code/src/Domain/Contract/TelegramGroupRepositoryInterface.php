<?php
declare(strict_types=1);

namespace Art\Code\Domain\Contract;

use Art\Code\Domain\Dto\TelegramGroupDto;
use Art\Code\Domain\Entity\TelegramGroup;
use Illuminate\Database\Eloquent\Collection;

interface TelegramGroupRepositoryInterface
{
    public function create(TelegramGroupDto $telegramGroupDto): TelegramGroup;

    public function getListByUser(string $userChatId):Collection;

    public function getFirstById(int $id): ?TelegramGroup;

    public function deleteByChatId(string $groupChatId, string $userChatId): mixed;

    public function deleteById(int $id, string $userChatId): int;

    public function getCountByUser(string $userChatId): int;
}