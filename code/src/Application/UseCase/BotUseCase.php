<?php

declare(strict_types=1);

namespace Art\Code\Application\UseCase;

use Telegram\Bot\Api;

class BotUseCase
{
    public function hook(Api $telegram)
    {
        $updates = $telegram->getWebhookUpdate();
        $message = $updates->getMessage();
    }
}