<?php

declare(strict_types=1);

namespace Art\Code\Infrastructure\Repository;

use Art\Code\Domain\Contract\TelegramGroupRepositoryInterface;
use Art\Code\Domain\Dto\TelegramGroupDto;
use Art\Code\Domain\Entity\TelegramGroup;
use Illuminate\Database\Eloquent\Collection;

class TelegramGroupRepository implements TelegramGroupRepositoryInterface
{
    public function create(TelegramGroupDto $telegramGroupDto): TelegramGroup
    {
        return TelegramGroup::create([
            'name' => $telegramGroupDto->name,
            'group_chat_id' => $telegramGroupDto->group_chat_id,
            'user_chat_id' => $telegramGroupDto->user_chat_id,
        ]);
    }

    public function getListByUser(string $userChatId): Collection
    {
        return TelegramGroup::select('name')
            ->where('user_chat_id', '=', $userChatId)
            ->get();
    }

    public function deleteByChatId(string $chatId): mixed
    {
        return TelegramGroup::where('group_chat_id', $chatId)
            ->delete();
    }
}