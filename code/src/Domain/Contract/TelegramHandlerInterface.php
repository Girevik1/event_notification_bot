<?php
declare(strict_types=1);

namespace Art\Code\Domain\Contract;

use Art\Code\Domain\Dto\DataEditMessageDto;
use CurlHandle;

interface TelegramHandlerInterface
{
    public static function sendMessage(string $telegramChatId, string $text, string $typeBtn = '', int $replyToMessageId = 0);

    public static function editMessageTextSend(DataEditMessageDto $dataEditMessage): void;

    public static function deleteMessage(string $telegram_chat_id, int $msg_id): CurlHandle|bool;

    public static function getKeyboard(string $type, mixed $keyboardData = ''): bool|string;
}