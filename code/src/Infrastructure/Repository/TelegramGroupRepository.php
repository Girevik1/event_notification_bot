<?php

declare(strict_types=1);

namespace Art\Code\Infrastructure\Repository;

use Art\Code\Domain\Contract\TelegramGroupRepositoryInterface;
use Art\Code\Domain\Dto\TelegramGroupDto;
use Art\Code\Domain\Entity\TelegramGroup;
use Illuminate\Database\Eloquent\Collection;

class TelegramGroupRepository implements TelegramGroupRepositoryInterface
{
    /**
     * @param TelegramGroupDto $telegramGroupDto
     * @return TelegramGroup
     */
    public function create(TelegramGroupDto $telegramGroupDto): TelegramGroup
    {
        return TelegramGroup::create([
            'name' => $telegramGroupDto->name,
            'group_chat_id' => $telegramGroupDto->group_chat_id,
            'user_chat_id' => $telegramGroupDto->user_chat_id,
        ]);
    }

    /**
     * @param string $userChatId
     * @return Collection
     */
    public function getListByUser(string $userChatId): Collection
    {
        return TelegramGroup::select('id', 'name')
            ->where('user_chat_id', '=', $userChatId)
            ->get();
    }

    /**
     * @param int $id
     * @return TelegramGroup|null
     */
    public function getFirstById(int $id): ?TelegramGroup
    {
        return TelegramGroup::select('id', 'name')
            ->where('id', '=', $id)
            ->first();
    }

    /**
     * @param string $groupChatId
     * @param string $userChatId
     * @return mixed
     */
    public function deleteByChatId(string $groupChatId, string $userChatId): mixed
    {
        return TelegramGroup::where('group_chat_id', $groupChatId)
            ->where('user_chat_id', $userChatId)
            ->delete();
    }

    public function deleteById(int $id, string $userChatId): int
    {
        return TelegramGroup::where('id', $id)
            ->where('user_chat_id', $userChatId)
            ->delete();
    }

    /**
     * @param string $userChatId
     * @return int
     */
    public function getCountByUser(string $userChatId): int
    {
        return TelegramGroup::where('user_chat_id', '=', $userChatId)
            ->count();
    }
}