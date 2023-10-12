<?php

declare(strict_types=1);

namespace Art\Code\Infrastructure\Http\Controllers;

use Art\Code\Application\UseCase\Bot\BotUseCase;

final class BotController
{
    /**
     * @param BotUseCase $botUseCase
     * @return void
     */
    public function run(BotUseCase $botUseCase): void
    {
        $botUseCase->hook();
    }
}