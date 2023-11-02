<?php

declare(strict_types=1);

namespace Art\Code\Infrastructure\Repository;

use Art\Code\Domain\Contract\TelegramUserRepositoryInterface;
use Art\Code\Domain\Dto\TelegramUserDto;
use Art\Code\Domain\Entity\TelegramUser;

class TelegramUserRepository implements TelegramUserRepositoryInterface
{
    /**
     * @param TelegramUserDto $telegramUserDto
     * @return TelegramUser
     */
    public function create(TelegramUserDto $telegramUserDto): TelegramUser
    {
        return TelegramUser::create([
            'login' => $telegramUserDto->login,
            'name' => $telegramUserDto->name,
            'surname' => $telegramUserDto->surname,
            'telegram_chat_id' => $telegramUserDto->telegram_chat_id
        ]);
    }

    /**
     * @param int $id
     * @return TelegramUser|null
     */
    public function firstById(int $id): ?TelegramUser
    {
        return TelegramUser::where('id','=', $id)->first();
    }

    /**
     * @param string $chatId
     * @return TelegramUser|null
     */
    public function firstByChatId(string $chatId): ?TelegramUser
    {
        return TelegramUser::where('telegram_chat_id','=', $chatId)->first();
    }

    /**
     * @param string $login
     * @return TelegramUser|null
     */
    public function firstByLogin(string $login): ?TelegramUser
    {
        return TelegramUser::where('login','=', $login)->first();
    }
}