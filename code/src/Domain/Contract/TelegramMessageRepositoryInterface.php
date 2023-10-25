<?php
declare(strict_types=1);

namespace Art\Code\Domain\Contract;

use Art\Code\Domain\Dto\MessageDto;
use Art\Code\Domain\Entity\TelegramMessage;
use Illuminate\Database\Eloquent\Collection;

interface TelegramMessageRepositoryInterface
{
    public function create(MessageDto $message): TelegramMessage;

    public function getLastMessage(): TelegramMessage;

    public function getAllByUser(int $telegramUserId): ?Collection;

    public function deleteByMessageId(int $message_id): mixed;
}