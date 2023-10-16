<?php

declare(strict_types=1);

namespace Art\Code\Application\Dto;

class TelegramUserDto
{
    public string $username;
    public string|int $chat_id;

    public function __construct(array $message)
    {
        $this->username = strtolower($message["chat"]["username"]);
        $this->chat_id = $message["chat"]["id"];
    }
}