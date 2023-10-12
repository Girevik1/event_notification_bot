<?php

declare(strict_types=1);

namespace Art\Code\Infrastructure\Http\Controllers;

use Art\Code\Application\UseCase\BotUseCase;

class BotController
{
    public function run(BotUseCase $botUseCase): void
    {
        $botUseCase->hook();
    }
}