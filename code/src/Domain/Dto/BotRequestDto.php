<?php

declare(strict_types=1);

namespace Art\Code\Domain\Dto;

use Art\Code\Application\UseCase\Bot\GroupUseCase;
use Art\Code\Application\UseCase\Bot\TextUseCase;
use Art\Code\Domain\Contract\ListEventRepositoryInterface;
use Art\Code\Domain\Contract\QueueMessageRepositoryInterface;
use Art\Code\Domain\Contract\TelegramGroupRepositoryInterface;
use Art\Code\Domain\Contract\TelegramMessageRepositoryInterface;
use Art\Code\Domain\Contract\TelegramUserRepositoryInterface;
use Art\Code\Domain\Entity\TelegramUser;
use Telegram\Bot\Api;

class BotRequestDto
{
    public TelegramUserRepositoryInterface $telegramUserRepository;
    public TelegramMessageRepositoryInterface $telegramMessageRepository;
    public TelegramGroupRepositoryInterface $telegramGroupRepository;
    public QueueMessageRepositoryInterface $queueMessageRepository;
    public ListEventRepositoryInterface $listEventRepository;

    public Api $telegram;
    public TextUseCase $textUseCase;
    public GroupUseCase $groupUseCase;
    public TelegramUser $telegramUser;
    public int $messageId;
}