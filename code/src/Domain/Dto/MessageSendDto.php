<?php

declare(strict_types=1);

namespace Art\Code\Domain\Dto;

class MessageSendDto
{
//    public TelegramUser $user;
    public string $chat_id;
    public string $text;
    public string $command;
    public string $model = '';
    public int $model_id = 0;
    public array $reply_to_message = [];
    public string $type_btn = '';

//    public function __construct($message)
//    {
//        $this->username = strtolower($message["chat"]["username"]);
//        $this->chat_id = $message["chat"]["id"];
//        $this->first_name = $message["chat"]["first_name"] ?? '';
//        $this->last_name = $message["chat"]["last_name"] ?? '';
//    }
}