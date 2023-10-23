<?php

declare(strict_types=1);

namespace Art\Code\Application\UseCase\Bot;

use Art\Code\Domain\Dto\TelegramGroupDto;
use Art\Code\Domain\Entity\TelegramUser;
use Art\Code\Domain\Exception\GroupCreateException;
use Art\Code\Domain\Exception\GroupDeleteException;
use Art\Code\Infrastructure\Repository\TelegramGroupRepository;
use Telegram\Bot\Api;
use Telegram\Bot\Exceptions\TelegramSDKException;

class GroupUseCase
{
    /**
     * @throws TelegramSDKException
     * @throws GroupCreateException
     * @throws GroupDeleteException
     */
    public function groupHandlerByMessage(
        array                   $message,
//        TelegramGroupRepository $groupRepository,
        Api                     $telegram,
//        TextUseCase             $textUseCase,
//        TelegramUser $user
    ): bool
    {
        $telegram->sendMessage([
            'chat_id' => -1001743972342,
            'parse_mode' => 'HTML',
            'text' => 'testest'
        ]);//

//        if (isset($message['left_chat_member'])) {
//            $resulDelete = $groupRepository->deleteByChatId($message['chat']['id']);
//            if (!$resulDelete) {
//                throw new GroupDeleteException('Не удалось удалить группу с БД');
//            }
//            // TODO удалить все эвенты связанные с этой группой
//            return true;
//        }
//
//        $telegramGroupDto = new TelegramGroupDto($message);
//
//        if (!$groupRepository->create($telegramGroupDto)) {
//            throw new GroupCreateException('Ошибка добавления группы в БД');
//        }
//
//        $message = $textUseCase->getGreetingsGroupText($user);
//
//        $telegram->sendMessage([
////            'chat_id' => -1001743972342,
//            'chat_id' => $telegramGroupDto->group_chat_id,
//            'parse_mode' => 'HTML',
//            'text' => $message
//        ]);
//
//        return true;
    }
}