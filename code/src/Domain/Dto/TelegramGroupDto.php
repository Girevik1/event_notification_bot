<?php

declare(strict_types=1);

namespace Art\Code\Domain\Dto;

class TelegramGroupDto
{
    public string $name;
    public string $group_chat_id;
    public string $user_chat_id;

    public function __construct($message)
    {
        $groupName = $message["chat"]["title"] ?? '';
        $this->name = strtolower($groupName);
        $this->group_chat_id = (string)$message["chat"]["id"];
        $this->user_chat_id = (string)$message["from"]["id"];
    }
}