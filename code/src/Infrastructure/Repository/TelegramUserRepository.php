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
        return TelegramUser::create([
            'login' => $telegramUserDto->login,
            'name' => $telegramUserDto->name,
            'surname' => $telegramUserDto->surname,
            'telegram_chat_id' => $telegramUserDto->telegram_chat_id
        ]);
    }

//    public function firstById($id): ?TelegramUser
//    {
//        return TelegramUser::where('id','=', $id)->first();
//    }

    public function firstByChatId(string $chatId): ?TelegramUser
    {
        return TelegramUser::where('telegram_chat_id','=', $chatId)->first();
    }

    public function firstByLogin(string $login): ?TelegramUser
    {
        return TelegramUser::where('login','=', $login)->first();
    }

//    public function isExistByLogin($login): bool
//    {
//        return (bool) TelegramUser::where('login','=', $login)->first();
//    }

//    public function updateByField(TelegramUser $telegramUser, string $field, mixed $value): TelegramUser
//    {
//        $telegramUser->$field = $value;
//        $telegramUser->save();
//
//        return $telegramUser;
//    }
}