<?php

declare(strict_types=1);

namespace Art\Code\Application\UseCase\Bot;

use Art\Code\Domain\Contract\TelegramGroupRepositoryInterface;
use Art\Code\Domain\Dto\TelegramGroupDto;
use Art\Code\Domain\Entity\ListEvent;
use Art\Code\Domain\Entity\TelegramUser;
use Art\Code\Domain\Exception\GroupCreateException;
use Art\Code\Domain\Exception\GroupDeleteException;
use Art\Code\Infrastructure\Repository\TelegramGroupRepository;
use Illuminate\Support\Collection;
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
        Collection                   $message,
        TelegramGroupRepository $groupRepository,
        Api                     $telegram,
        TextUseCase             $textUseCase,
        TelegramUser $user
    ): bool
    {
//        $telegram->sendMessage([
//            'chat_id' => -1001743972342,
//            'parse_mode' => 'HTML',
//            'text' => 'testest'
//        ]);//

        if (isset($message['left_chat_member'])) {

            $group = $groupRepository->getFirstByGroupChatId((string)$message['chat']['id'], $user->telegram_chat_id);

            if($group){
                ListEvent::where('group_id', $group->id)
                    ->where('telegram_user_id', $user->id)
                    ->update(['group_id' => 0]);
            }

            $resulDelete = $groupRepository->deleteByChatId(
                (string)$message['chat']['id'],
                (string)$message['from']['id']
            );

            if (!$resulDelete) {
                throw new GroupDeleteException('Не удалось удалить группу с БД');
            }


            // TODO удалить все эвенты связанные с этой группой, upd эвенты оставить - сделать личное уведомление

            return true;
        }

        $telegramGroupDto = new TelegramGroupDto($message);

        if (!$groupRepository->create($telegramGroupDto)) {
            throw new GroupCreateException('Ошибка добавления группы в БД');
        }

        $message = $textUseCase->getGreetingsGroupText($user);

        $telegram->sendMessage([
//            'chat_id' => -1001743972342,
            'chat_id' => $telegramGroupDto->group_chat_id,
            'parse_mode' => 'HTML',
            'text' => $message
        ]);

        return true;
    }

    /**
     * @param string $answer
     * @param TelegramGroupRepositoryInterface|null $groupRepository
     * @param string $userChatId
     * @return string
     */
    public static function getNameGroup(string $answer, ?TelegramGroupRepositoryInterface $groupRepository, string $userChatId): string
    {
        if ($groupRepository !== null) {
            $group = $groupRepository->getFirstById((int)$answer, $userChatId);
            return "\nГруппа: <i>" . $group->name . "</i>";
        }
        return "\nГруппа: <i>Не найдена!</i>";
    }
}