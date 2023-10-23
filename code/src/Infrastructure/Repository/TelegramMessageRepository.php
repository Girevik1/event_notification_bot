<?php

declare(strict_types=1);

namespace Art\Code\Infrastructure\Repository;

use Art\Code\Domain\Contract\TelegramMessageRepositoryInterface;
use Art\Code\Domain\Dto\MessageDto;
use Art\Code\Domain\Entity\TelegramMessage;

class TelegramMessageRepository implements TelegramMessageRepositoryInterface
{
    /**
     * @param MessageDto $message
     * @return TelegramMessage
     */
    public function create(MessageDto $message): TelegramMessage
    {
        return TelegramMessage::create([
            'chat_id' => $message->chat_id,
            'message_id' => $message->message_id,
            'text' => $message->text,
            'reply_to' => $message->reply_to,
            'command' => $message->command,
            'is_deleted_from_chat' => $message->is_deleted_from_chat,
            'data_test' => $message->data_test
        ]);
    }

    /**
     * @return TelegramMessage
     */
    public function getLastMessage(): TelegramMessage
    {
        return TelegramMessage::orderBy('id', 'desc')->first();
    }
}