<?php

declare(strict_types=1);

namespace Art\Code\Application\Dto;

class TelegramUserDto
{
    public string $username;
    public string $first_name;
    public string $last_name;
    public string|int $chat_id;

    public function __construct(array $message)
    {
        $this->username = strtolower($message["chat"]["username"]);
        $this->chat_id = $message["chat"]["id"];
        $this->first_name = $message["chat"]["first_name"] ?? '';
        $this->last_name = $message["chat"]["last_name"] ?? '';
    }
}