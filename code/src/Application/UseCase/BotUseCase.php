<?php

declare(strict_types=1);

namespace Art\Code\Application\UseCase;

use Telegram\Bot\Api;
use Telegram\Bot\Exceptions\TelegramSDKException;

class BotUseCase
{
    public Api $telegram;

    /**
     * @throws TelegramSDKException
     */
    public function __construct()
    {
        $this->telegram = new Api($_ENV['TELEGRAM_KEY']);
    }

    public function hook(): void
    {
        $updates = $this->telegram->getWebhookUpdate();
        $message = $updates->getMessage();
    }
}