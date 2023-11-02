<?php

declare(strict_types=1);

namespace Art\Code\Infrastructure\Cron\Controllers;

use Art\Code\Application\UseCase\Bot\BotUseCase;
use Telegram\Bot\Exceptions\TelegramSDKException;

class CronController
{
    /**
     * @throws TelegramSDKException
     */
    public function checkAvailableEvents(BotUseCase $botUseCase): void
    {
        $botUseCase->checkBirthdayToday();
//        $botUseCase->checkAnniversaryToday();
    }
}