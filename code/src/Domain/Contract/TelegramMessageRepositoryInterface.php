<?php
declare(strict_types=1);

namespace Art\Code\Domain\Contract;

use Art\Code\Domain\Dto\MessageDto;
use Art\Code\Domain\Dto\TelegramMessageDto;
use Art\Code\Domain\Entity\TelegramMessage;

interface TelegramMessageRepositoryInterface
{
    public function create(MessageDto|TelegramMessageDto $message): TelegramMessage;

    public function getLastMessage(string $chat_id): TelegramMessage;

    public function getLastMessageByCommand(string $chat_id, string $command): TelegramMessage;

    public function deleteByMessageId(int $message_id): int;

    public function getLastByChatId(string $telegramChatId): ?TelegramMessage;
}