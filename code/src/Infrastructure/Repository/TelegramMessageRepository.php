<?php

declare(strict_types=1);

namespace Art\Code\Infrastructure\Repository;

use Art\Code\Domain\Contract\TelegramMessageRepositoryInterface;
use Art\Code\Domain\Dto\MessageDto;
use Art\Code\Domain\Entity\TelegramMessage;
use Illuminate\Database\Eloquent\Collection;

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
//            'data_test' => $message->data_test
        ]);
    }

    /**
     * @return TelegramMessage
     */
    public function getLastMessage(): TelegramMessage
    {
        return TelegramMessage::orderBy('id', 'desc')->first();
    }

    /**
     * @param int $telegramUserId
     * @return Collection|null
     */
    public function getAllByUser(int $telegramUserId): ?Collection
    {
        return TelegramMessage::where("telegram_user_id", $telegramUserId)->get();
    }

    /**
     * @param int $message_id
     * @return mixed
     */
    public function deleteByMessageId(int $message_id): mixed
    {
        return TelegramMessage::where('message_id', $message_id)->delete();
    }

    /**
     * @param int $telegramChatId
     * @return TelegramMessage
     */
    public function getLastByChatId(int $telegramChatId): TelegramMessage
    {
        return TelegramMessage::where('chat_id', $telegramChatId)
            ->orderBy('id','desc')
            ->first();
    }
}