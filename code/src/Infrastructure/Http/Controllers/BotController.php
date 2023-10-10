<?php

declare(strict_types=1);

namespace Art\Code\Infrastructure\Http\Controllers;

use Art\Code\Application\UseCase\BotUseCase;
use Telegram\Bot\Api;
use Telegram\Bot\Exceptions\TelegramSDKException;

class BotController
{
    public Api $telegram;

    /**
     * @throws TelegramSDKException
     */
    public function __construct(public BotUseCase $botUseCase)
    {
        $this->telegram = new Api($_ENV['TELEGRAM_KEY']);
    }

    public function run(): void
    {
        $this->botUseCase->hook($this->telegram);
    }
}