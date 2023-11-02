<?php

declare(strict_types=1);

namespace Art\Code\Infrastructure\Cron\Controllers;

use Art\Code\Application\UseCase\Bot\BotUseCase;

class CronController
{
    public function checkAvailableEvents(BotUseCase $botUseCase)
    {
        $botUseCase->checkBirthdayToday();
        $botUseCase->checkAnniversaryToday();

    }
}