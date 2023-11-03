<?php

declare(strict_types=1);

namespace Art\Code\Application\UseCase\Bot;

use Art\Code\Domain\Contract\TelegramGroupRepositoryInterface;
use Art\Code\Domain\Dto\BotRequestDto;
use Art\Code\Domain\Dto\TelegramGroupDto;
use Art\Code\Domain\Exception\GroupCreateException;
use Art\Code\Domain\Exception\GroupDeleteException;
use Telegram\Bot\Exceptions\TelegramSDKException;

class GroupUseCase
{
    /**
     * @throws TelegramSDKException
     * @throws GroupCreateException
     * @throws GroupDeleteException
     */
    public function groupHandlerByMessage(BotRequestDto $botRequestDto): bool
    {
        $message = $botRequestDto->message;

        if (isset($message['left_chat_member'])) {

            $group = $botRequestDto->telegramGroupRepository->getFirstByGroupChatId(
                (string)$message['chat']['id'],
                $botRequestDto->telegramUser->telegram_chat_id
            );

            if ($group) {
                $botRequestDto->listEventRepository->updateAllByGroup(
                    $group->id,
                    $botRequestDto->telegramUser->id,
                    'group_id', 0
                );
            }

            $resultOfDelete = $botRequestDto->telegramGroupRepository->deleteByChatId(
                (string)$message['chat']['id'],
                (string)$message['from']['id']
            );

            if (!$resultOfDelete) throw new GroupDeleteException('Не удалось удалить группу с БД');

            return true;
        }

        $telegramGroupDto = new TelegramGroupDto($botRequestDto->message);

        if (!$botRequestDto->telegramGroupRepository->create($telegramGroupDto)) throw new GroupCreateException('Ошибка добавления группы в БД');

        $text = $botRequestDto->textUseCase->getGreetingsGroupText($botRequestDto->telegramUser);

        $botRequestDto->telegram::sendMessage($telegramGroupDto->group_chat_id, $text);

        return true;
    }

    /**
     * @param string $answer
     * @param TelegramGroupRepositoryInterface|null $groupRepository
     * @param string $userChatId
     * @return string
     */
    public static function getNameGroup(
        string                            $answer,
        ?TelegramGroupRepositoryInterface $groupRepository,
        string                            $userChatId
    ): string
    {
        if ($groupRepository !== null) {
            $group = $groupRepository->getFirstById((int)$answer, $userChatId);
            return "\nГруппа: <i>" . $group->name . "</i>";
        }
        return "\nГруппа: <i>Не найдена!</i>";
    }
}