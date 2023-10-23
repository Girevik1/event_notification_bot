<?php
declare(strict_types=1);

namespace Art\Code\Domain\Contract;

use Art\Code\Domain\Dto\MessageDto;
use Art\Code\Domain\Entity\TelegramMessage;

interface TelegramMessageRepositoryInterface
{
    public function create(MessageDto $message): TelegramMessage;

    public function getLastMessage(): TelegramMessage;
}