<?php

declare(strict_types=1);

namespace Art\Code\Infrastructure\Repository;

use Art\Code\Domain\Contract\TelegramUserRepositoryInterface;
use Art\Code\Domain\Dto\TelegramUserDto;
use Art\Code\Domain\Entity\TelegramUser;

class TelegramUserRepository implements TelegramUserRepositoryInterface
{
    public function create(TelegramUserDto $telegramUserDto): TelegramUser
    {
        $telegramUser = new TelegramUser();
        $telegramUser->login = $telegramUserDto->username;
        $telegramUser->name = $telegramUserDto->first_name;
        $telegramUser->surname = $telegramUserDto->last_name;
        $telegramUser->information = 'artur test5';
        $telegramUser->telegram_chat_id = $telegramUserDto->chat_id;
        $telegramUser->save();

        return $telegramUser;
    }

    public function firstById($id): ?TelegramUser
    {
        return TelegramUser::where('id','=', $id)->first();
    }

    public function firstByChatId($chatId): ?TelegramUser
    {
        return TelegramUser::where('telegram_chat_id','=', $chatId)->first();
    }

    public function firstByLogin($login): ?TelegramUser
    {
        return TelegramUser::where('login','=', $login)->first();
    }

    public function isExistByLogin($login): bool
    {
        return TelegramUser::where('login','=', $login)->exist();
    }

    public function updateByField(TelegramUser $telegramUser, string $field, mixed $value): TelegramUser
    {
        $telegramUser->$field = $value;
        $telegramUser->save();

        return $telegramUser;
    }
}