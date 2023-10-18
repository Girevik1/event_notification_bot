<?php

declare(strict_types=1);

namespace Art\Code\Application\UseCase\Message;

class MessageTextUseCase
{
    public function getChangeLoginText(string $username): string
    {
        $txt = "Вы сменили username в Telegram.";
        $txt .= "\n\nВаш новый username перезаписан на @" . $username;
        $txt .= "\nВаш логин в систему теперь " . strtolower($username);

        return $txt;
    }

    public function getGreatingsText(bool $isNewUser): string{
        if ($isNewUser) {
            $text = "Привет! Я бот для напоминаний твоих событий\n";
            $text .= "Давай познакомимся? 😎";
        }else{
            $text = "И снова здравствуйте! 😎";
        }

        return $text;
    }
}