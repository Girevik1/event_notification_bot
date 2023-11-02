<?php

declare(strict_types=1);

namespace Art\Code\Domain\Dto;

use Art\Code\Domain\Contract\TelegramMessageRepositoryInterface;
use Art\Code\Domain\ValueObject\TelegramChatId;

class MessageSendDto
{
    public TelegramChatId $chat_id;
    public string $text;
    public string $command;
    public string $model = '';
    public array $reply_to_message = [];
    public string $type_btn = '';
    public TelegramMessageRepositoryInterface $telegramMessageRepository;
}