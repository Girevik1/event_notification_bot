<?php

declare(strict_types=1);

namespace Art\Code\Domain\Dto;

class TelegramUserDto
{
    public string $login;
    public string $name;
    public string $surname;
    public string $telegram_chat_id;

    public function __construct($message)
    {
        $login = $message["chat"]["username"] ?? '';
        $this->login = strtolower($login);
        $this->telegram_chat_id = (string)$message["chat"]["id"];
        $this->name = $message["chat"]["first_name"] ?? '';
        $this->surname = $message["chat"]["last_name"] ?? '';
    }
}