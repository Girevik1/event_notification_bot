<?php

declare(strict_types=1);

namespace Art\Code\Infrastructure\Http\Controllers;

use Art\Code\Application\UseCase\Bot\BotUseCase;
use Art\Code\Domain\Exception\DatabaseConnectionException;
use Art\Code\Infrastructure\Cron\Controllers\CronController;
use Exception;
use Telegram\Bot\Exceptions\TelegramSDKException;

final class AppController
{
    private string $uri;
    private BotUseCase $botUseCase;
    private array $dependence;

    /**
     * @throws TelegramSDKException
     * @throws DatabaseConnectionException
     */
    public function __construct()
    {
        $this->dependence = require '../dependence.php';

        $this->botUseCase = new BotUseCase(
            $this->dependence[\Art\Code\Domain\Contract\TelegramUserRepositoryInterface::class],
            $this->dependence[\Art\Code\Domain\Contract\TelegramMessageRepositoryInterface::class],
            $this->dependence[\Art\Code\Domain\Contract\TelegramGroupRepositoryInterface::class],
            $this->dependence[\Art\Code\Domain\Contract\QueueMessageRepositoryInterface::class],
            $this->dependence[\Art\Code\Domain\Contract\ListEventRepositoryInterface::class],
            $this->dependence[\Art\Code\Domain\Contract\TelegramHandlerInterface::class],
        );

        $this->uri = $this->getURI();
    }

    /**
     * @throws Exception
     */
    public function run(): void
    {
        if ($this->uri === 'bot') {
            (new BotController())->runHook($this->botUseCase);
        }
        if ($this->uri === 'cron') {
            (new CronController())->checkAvailableEvents($this->botUseCase);
        }
    }

    /**
     * @return string
     */
    private function getURI(): string
    {
        if (isset($_SERVER['REQUEST_URI'])) {
            return trim($_SERVER['REQUEST_URI'], '/');
        }
        return '';
    }
}