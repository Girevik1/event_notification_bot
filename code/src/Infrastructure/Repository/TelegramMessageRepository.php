<?php

declare(strict_types=1);

namespace Art\Code\Infrastructure\Repository;

use Art\Code\Domain\Contract\TelegramMessageRepositoryInterface;
use Art\Code\Domain\Dto\MessageDto;
use Art\Code\Domain\Dto\TelegramMessageDto;
use Art\Code\Domain\Entity\TelegramMessage;
use Illuminate\Database\Eloquent\Collection;

class TelegramMessageRepository implements TelegramMessageRepositoryInterface
{
    /**
     * @param MessageDto|TelegramMessageDto $message
     * @return TelegramMessage
     */
    public function create(MessageDto|TelegramMessageDto $message): TelegramMessage
    {
        return TelegramMessage::create([
            'chat_id' => $message->chat_id,
            'message_id' => $message->message_id,
            'text' => $message->text,
            'reply_to' => $message->reply_to,
            'command' => $message->command,
            'is_deleted_from_chat' => $message->is_deleted_from_chat,
//            'data_test' => $message->data_test // For test
        ]);
    }

    /**
     * @param string $chat_id
     * @return TelegramMessage
     */
    public function getLastMessage(string $chat_id): TelegramMessage
    {
        return TelegramMessage::where('chat_id', $chat_id)
            ->orderBy('id', 'desc')
            ->first();
    }

    /**
     * @param string $chat_id
     * @param string $command
     * @return TelegramMessage
     */
    public function getLastMessageByCommand(string $chat_id, string $command): TelegramMessage
    {
        return TelegramMessage::where('chat_id', $chat_id)
            ->where('command', '=', $command)
            ->orderBy('id', 'desc')
            ->first();
    }

    /**
     * @param int $message_id
     * @return mixed
     */
    public function deleteByMessageId(int $message_id): int
    {
        return TelegramMessage::where('message_id', $message_id)->delete();
    }

    /**
     * @param string $telegramChatId
     * @return TelegramMessage|null
     */
    public function getLastByChatId(string $telegramChatId): ?TelegramMessage
    {
        return TelegramMessage::where('chat_id', $telegramChatId)
            ->orderBy('id','desc')
            ->first();
    }

    /**
     * @param string $telegramChatId
     * @return Collection
     */
    public function getAllMessageByChatId(string $telegramChatId):Collection
    {
        return TelegramMessage::where('chat_id', $telegramChatId)->get();
    }
}