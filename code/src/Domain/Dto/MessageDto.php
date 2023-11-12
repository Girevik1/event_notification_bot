<?php

declare(strict_types=1);

namespace Art\Code\Domain\Dto;

class MessageDto
{
    public string $chat_id;
    public string $chat_title;
    public string $user_name;
    public string $from_id;
    public int $message_id;
    public string $text;
    public int $reply_to;
    public string $command;
    public int $is_deleted_from_chat;
    public ?string $data_test = '';
    public mixed $new_chat_participant_id;

    public function __construct($message)
    {
        $userName = $message["chat"]["username"] ?? '';
        $this->chat_id = (string)$message["chat"]["id"];
        $this->new_chat_participant_id = $message["new_chat_participant"]["id"] ?? '';
        $this->chat_title = $message["chat"]["title"] ?? '';
        $this->user_name = strtolower($userName);
        $this->message_id = $message["message_id"] ?? 0;
        $this->from_id = (string)$message["from"]['id'] ?? 0;
        $this->text = $message["text"] ?? '';
        $this->reply_to = $message["reply_to_message"] ?? 0;
        $this->command = $message["command"] ?? '';
        $this->is_deleted_from_chat = $message["is_deleted_from_chat"] ?? 0;
        $this->data_test = json_encode($message);
    }
}