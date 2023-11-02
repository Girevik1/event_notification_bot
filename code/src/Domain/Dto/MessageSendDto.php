<?php

declare(strict_types=1);

namespace Art\Code\Domain\Dto;

use Art\Code\Domain\Contract\TelegramMessageRepositoryInterface;

class MessageSendDto
{
    public string $chat_id;
    public string $text;
    public string $command;
    public string $model = '';
    public int $model_id = 0;
    public array $reply_to_message = [];
    public string $type_btn = '';
    public TelegramMessageRepositoryInterface $telegramMessageRepository;
}