<?php

declare(strict_types=1);

namespace Art\Code\Application\UseCase\Bot;

use Art\Code\Application\UseCase\Message\QueueMessageUseCase;
use Art\Code\Domain\Dto\DataEditMessageDto;
use Art\Code\Domain\Dto\ListEventDto;
use Art\Code\Domain\Dto\MessageDto;
use Art\Code\Domain\Dto\MessageSendDto;
use Art\Code\Domain\Dto\TelegramUserDto;
use Art\Code\Domain\Entity\QueueMessage;
use Art\Code\Domain\Entity\TelegramMessage;
use Art\Code\Domain\Entity\TelegramSender;
use Art\Code\Domain\Entity\TelegramUser;
use Art\Code\Domain\Exception\EventNotFoundException;
use Art\Code\Domain\Exception\QueueTypeException;
use Art\Code\Domain\Exception\TelegramMessageDataException;
use Carbon\Carbon;
use Exception;
use Illuminate\Support\Collection;
use Telegram\Bot\Api;
use Telegram\Bot\Exceptions\TelegramSDKException;

final class BotUseCase
{
    private Api $telegram;
    private TextUseCase $textUseCase;
    private GroupUseCase $groupUseCase;
    private mixed $newRequest;
    private DataEditMessageDto $dataEditMessageDto;

    /**
     * @throws TelegramSDKException
     */
    public function __construct(
        public $telegramUserRepository,
        public $telegramMessageRepository,
        public $telegramGroupRepository,
        public $queueMessageRepository,
        public $listEventRepository,
//        private readonly TelegramUserRepositoryInterface    $telegramUserRepository,
//        private readonly TelegramMessageRepositoryInterface $telegramMessageRepository
    )
    {
        $this->telegram = new Api($_ENV['TELEGRAM_BOT_TOKEN']);
        $this->textUseCase = new TextUseCase();
        $this->groupUseCase = new GroupUseCase();
        $this->dataEditMessageDto = new DataEditMessageDto();

//        $this->newRequest = json_decode(file_get_contents("php://input"), true); // for test/
    }

    /**
     * @throws Exception
     */
    public function hook()
    {
        $message = [];

//        $message = $this->newRequest;
//        $updates['callback_query'] = $message['callback_query'];

        if ($_ENV['APP_ENV'] === 'prod') {
            $updates = $this->telegram->getWebhookUpdate();
            $message = $updates->getMessage();
        }

//        $this->telegram->editMessageText([
//            'chat_id' => 500264009,
//            'message_id' => $message['message_id'],
//            'text' => 'testetetst',
//            'reply_markup' => TelegramSender::getKeyboard('process_set_event'),
//            'parse_mode' => 'HTML',
//        ]);

//        $message['callback_query'] = $updates->callback_query ?? '';
        $messageDto = new MessageDto($message);

        $this->telegramMessageRepository->create($messageDto);

        if (!$this->checkMessage($messageDto) && !$this->checkChatTitle($messageDto)) {
            throw new TelegramMessageDataException('Some data is missing');
        };

        /*
         * Create or remove a group in db (on added in group or left)
         * */
        if ($this->checkChatTitle($messageDto)) {
            $telegramUser = $this->telegramUserRepository->firstByChatId($messageDto->from_id);

            $this->groupUseCase->groupHandlerByMessage(
                $message,
                $this->telegramGroupRepository,
                $this->telegram,
                $this->textUseCase,
                $telegramUser
            );

            return '';
        }

        $telegramUser = $this->telegramUserRepository->firstByChatId($messageDto->chat_id);
        $isNewUser = false;
        if ($telegramUser === null) {
            $telegramUser = $this->telegramUserRepository->create(new TelegramUserDto($message));
            $isNewUser = true;
        }

        /*
         * It`s callback of line keyboard
         * */
        if (isset($updates['callback_query'])) {
            $inlineKeyboardData = $updates['callback_query']['data'];
            $messageId = $updates['callback_query']['message']['message_id'];

            switch ($inlineKeyboardData) {

                case "about_project":

                    $this->dataEditMessageDto->text = $this->textUseCase->getAboutText();
                    $this->dataEditMessageDto->keyboard = 'to_the_beginning';
                    $this->dataEditMessageDto->chat_id = $telegramUser->telegram_chat_id;
                    $this->dataEditMessageDto->message_id = $messageId;

                    TelegramSender::editMessageTextSend($this->dataEditMessageDto);

                    return;
                case "to_the_beginning":

                    $this->dataEditMessageDto->text = $this->textUseCase->getGreetingsText($isNewUser);
                    $this->dataEditMessageDto->keyboard = 'main_menu';
                    $this->dataEditMessageDto->chat_id = $telegramUser->telegram_chat_id;
                    $this->dataEditMessageDto->message_id = $messageId;

                    TelegramSender::editMessageTextSend($this->dataEditMessageDto);

                    return;
                case "what_can_bot":

                    $this->dataEditMessageDto->text = $this->textUseCase->getWhatCanText();
                    $this->dataEditMessageDto->keyboard = 'to_the_beginning';
                    $this->dataEditMessageDto->chat_id = $telegramUser->telegram_chat_id;
                    $this->dataEditMessageDto->message_id = $messageId;

                    TelegramSender::editMessageTextSend($this->dataEditMessageDto);

                    return;
                case "how_use":

                    $this->dataEditMessageDto->text = $this->textUseCase->getHowUseText();
                    $this->dataEditMessageDto->keyboard = 'to_the_beginning';
                    $this->dataEditMessageDto->chat_id = $telegramUser->telegram_chat_id;
                    $this->dataEditMessageDto->message_id = $messageId;

                    TelegramSender::editMessageTextSend($this->dataEditMessageDto);

                    return;

                case "settings_menu":
                case "private_cabinet":
                case "changed_my_mind":

                $this->dataEditMessageDto->text = $this->textUseCase->getPrivateCabinetText();
                $this->dataEditMessageDto->keyboard = 'settings_menu';
                $this->dataEditMessageDto->chat_id = $telegramUser->telegram_chat_id;
                $this->dataEditMessageDto->message_id = $messageId;

                TelegramSender::editMessageTextSend($this->dataEditMessageDto);
                $this->queueMessageRepository->deleteAllMessageByUser($telegramUser->id);

                return;

                case "list_groups":

                    $listGroups = $this->telegramGroupRepository->getListByUser($telegramUser->telegram_chat_id);
                    $this->dataEditMessageDto->text = $this->textUseCase->getListGroupText($listGroups);
                    $this->dataEditMessageDto->keyboard = 'to_the_settings_menu';
                    $this->dataEditMessageDto->chat_id = $telegramUser->telegram_chat_id;
                    $this->dataEditMessageDto->message_id = $messageId;

                    TelegramSender::editMessageTextSend($this->dataEditMessageDto);

                    $messageDto->command = 'list_groups';
                    $this->telegramMessageRepository->create($messageDto);

                    return;

                    case "list_events":

                    $listEvents = $this->listEventRepository->getListByUser($telegramUser->id);
                    $this->dataEditMessageDto->text = $this->textUseCase->getListEventText($listEvents, $this->telegramGroupRepository);
                    $this->dataEditMessageDto->keyboard = 'to_the_settings_menu';
                    $this->dataEditMessageDto->chat_id = $telegramUser->telegram_chat_id;
                    $this->dataEditMessageDto->message_id = $messageId;

                    TelegramSender::editMessageTextSend($this->dataEditMessageDto);

                        $messageDto->command = 'list_events';
                        $this->telegramMessageRepository->create($messageDto);

                    return;

                case "add_birthday":

                    $addBirthdayUseCase = new AddBirthdayUseCase(
                        $this->telegram,
                        $telegramUser,
                        $messageId,
                        $this->queueMessageRepository
                    );

                    $addBirthdayUseCase->addBirthday();

//                    $messageDto->command = 'add_birthday';
//                    $this->telegramMessageRepository->create($messageDto);

                    return;

                case "confirm_event":

                    $queueMessagesByUser = $this->queueMessageRepository->getAllByUserId($telegramUser->id);
                    $this->dataMappingListEvent($queueMessagesByUser, $telegramUser, $messageId);

                    return;

                case "personal_notice":

                    $lastSentQueueMessage = $this->queueMessageRepository->getLastSentMsg($telegramUser->id);
                    $this->queueMessageRepository->updateFieldById('answer', 'personal', $lastSentQueueMessage->id);
                    $queueMessageByUser = $this->queueMessageRepository->getQueueMessageById($lastSentQueueMessage->next_id);

                    $this->queueMessageRepository->updateFieldById('answer', '0', $queueMessageByUser->id);
                    $this->queueMessageRepository->updateFieldById('state', 'SENT', $queueMessageByUser->id);
                    $queueMessageByUser = $this->queueMessageRepository->getQueueMessageById($queueMessageByUser->next_id);

                    $this->dataEditMessageDto->text = $this->getTextByEventType($queueMessageByUser, $telegramUser->telegram_chat_id);
                    $this->dataEditMessageDto->keyboard = $this->gerKeyboardByQueueType($queueMessageByUser);
                    $this->dataEditMessageDto->chat_id = $telegramUser->telegram_chat_id;
                    $this->dataEditMessageDto->message_id = $messageId;

                    TelegramSender::editMessageTextSend($this->dataEditMessageDto);

                    return;

                    case "group_notice":

                    $countAccessGroup = $this->telegramGroupRepository->getCountByUser($telegramUser->telegram_chat_id);
                    if ($countAccessGroup === 0) {
                        return;
                    }

                    $lastSentQueueMessage = $this->queueMessageRepository->getLastSentMsg($telegramUser->id);
                    $this->queueMessageRepository->updateFieldById('answer', 'group', $lastSentQueueMessage->id);
                    $queueMessageByUser = $this->queueMessageRepository->getQueueMessageById($lastSentQueueMessage->next_id);

//                    $this->queueMessageRepository->updateFieldById('answer', '0', $queueMessageByUser->id);
//                    $this->queueMessageRepository->updateFieldById('state', 'SENT', $queueMessageByUser->id);
//                    $queueMessageByUser = $this->queueMessageRepository->getQueueMessageById($queueMessageByUser->next_id);
                        $this->dataEditMessageDto->text = $this->getTextByEventType($queueMessageByUser, $telegramUser->telegram_chat_id);
                    $this->dataEditMessageDto->keyboard = $this->gerKeyboardByQueueType($queueMessageByUser);
                    $this->dataEditMessageDto->chat_id = $telegramUser->telegram_chat_id;
                    $this->dataEditMessageDto->message_id = $messageId;

                    TelegramSender::editMessageTextSend($this->dataEditMessageDto);

                    return;

                case "to_previous_question":

                    $lastSentQueueMessage = $this->queueMessageRepository->getLastSentMsg($telegramUser->id);

                    /*
                     * Если есть предыдущего сообщения нет (равно 0), то кидаем в личный кабинет
                     * */
                    if ($lastSentQueueMessage !== null && $lastSentQueueMessage->previous_id === 0) {

                        $this->dataEditMessageDto->text = $this->textUseCase->getPrivateCabinetText();
                        $this->dataEditMessageDto->keyboard = 'settings_menu';
                        $this->dataEditMessageDto->chat_id = $telegramUser->telegram_chat_id;
                        $this->dataEditMessageDto->message_id = $messageId;

                        TelegramSender::editMessageTextSend($this->dataEditMessageDto);
                        $this->queueMessageRepository->deleteAllMessageByUser($telegramUser->id);

                        return;
                    }

                    /*
                     * Если есть предыдущее сообщение то у текушего сообщения меняем статус на NOT_SEND
                     * */
                    if ($lastSentQueueMessage !== null && $lastSentQueueMessage->pevious_id !== 0) {
                        $this->queueMessageRepository->makeNotSendState($lastSentQueueMessage->id);
                    }

                    $previousMessage = $this->queueMessageRepository->getQueueMessageById($lastSentQueueMessage->previous_id);

                    if ($previousMessage !== null) {
                        /*
                         * Пропустить раздел выбора группы - если это личное оповещение
                         * */
                        if($previousMessage->type === 'GROUP' && $previousMessage->answer === '0'){
                            $this->queueMessageRepository->updateFieldById('state', 'NOT_SEND', $previousMessage->id);
                            $this->queueMessageRepository->updateFieldById('answer', '', $previousMessage->id);
                            $previousMessage = $this->queueMessageRepository->getQueueMessageById($previousMessage->previous_id);
                        }

                        $this->queueMessageRepository->updateFieldById('answer', '', $previousMessage->id);

                        if($previousMessage->type === 'NOTIFICATION_TYPE'){
                            $this->dataEditMessageDto->keyboard = 'notification_type';
                            $this->dataEditMessageDto->keyboardData = $this->telegramGroupRepository->getCountByUser($telegramUser->telegram_chat_id);
                        }else{
                            $this->dataEditMessageDto->keyboard = 'process_set_event';
                        }

                        $this->dataEditMessageDto->text = QueueMessageUseCase::getMessageByType(
                            $previousMessage,
                            $this->queueMessageRepository,
                            $this->telegramGroupRepository,
                            $telegramUser->telegram_chat_id
                        );

                        $this->dataEditMessageDto->chat_id = $telegramUser->telegram_chat_id;
                        $this->dataEditMessageDto->message_id = $messageId;

                        TelegramSender::editMessageTextSend($this->dataEditMessageDto);
                    }
                    return;

                default:
                    break;
            }
        }

        $text = $messageDto->text;

        switch ($text) {
            case "/start":
                $this->start($telegramUser, $messageDto, $isNewUser);
                $this->queueMessageRepository->deleteAllMessageByUser($telegramUser->id);

                return;

//            case (bool)preg_match('/\d{2}\.\d{2}\.\d{2}/', $text):
//            case (bool)preg_match('/[0-9]+-[0-9]+-[0-9]+/', $text):
//            case (bool)preg_match('/\/[0-9]{1,3}/', $text):
            case (bool)preg_match('/^event [0-9]{1,3}$/', $text):

                $textArray = explode(' ', $text);
                $idEvent = end($textArray);
                $result = $this->listEventRepository->deleteEventById((int)$idEvent, $telegramUser->id);

                TelegramSender::deleteMessage($telegramUser->telegram_chat_id, $messageDto->message_id);

                if(!$result){
                    return;
                }

                $telegramMessage = $this->telegramMessageRepository->getLastMessageByCommand($telegramUser->telegram_chat_id, 'list_events');

                $listEvents = $this->listEventRepository->getListByUser($telegramUser->id);
                $this->dataEditMessageDto->text = $this->textUseCase->getListEventText($listEvents, $this->telegramGroupRepository);
                $this->dataEditMessageDto->keyboard = 'to_the_settings_menu';
                $this->dataEditMessageDto->chat_id = $telegramUser->telegram_chat_id;
                $this->dataEditMessageDto->message_id = $telegramMessage->message_id ?? 0;

                TelegramSender::editMessageTextSend($this->dataEditMessageDto);

                return;

                case (bool)preg_match('/^group [0-9]{1,3}$/', $text):

                $textArray = explode(' ', $text);
                $idGroup = end($textArray);
                $result = $this->telegramGroupRepository->deleteById((int)$idGroup, $telegramUser->telegram_chat_id);

                TelegramSender::deleteMessage($telegramUser->telegram_chat_id, $messageDto->message_id);

                if(!$result){
                    return;
                }

                $telegramMessage = $this->telegramMessageRepository->getLastMessageByCommand($telegramUser->telegram_chat_id, 'list_groups');

                $listGroups = $this->telegramGroupRepository->getListByUser($telegramUser->telegram_chat_id);
                $this->dataEditMessageDto->text = $this->textUseCase->getListGroupText($listGroups);
                $this->dataEditMessageDto->keyboard = 'to_the_settings_menu';
                $this->dataEditMessageDto->chat_id = $telegramUser->telegram_chat_id;
                $this->dataEditMessageDto->message_id = $telegramMessage->message_id ?? 0;

                TelegramSender::editMessageTextSend($this->dataEditMessageDto);

                return;

            default:
                $queueMessageByUser = $this->queueMessageRepository->getLastSentMsg($telegramUser->id);
                if($queueMessageByUser && $text != ''){

                    // VALIDATION

                    /*
                     * Если на этапе выбора "как уведомлять" -> отправили текст, пересекаем
                     * */
                    if ($queueMessageByUser->type === 'NOTIFICATION_TYPE') {
                        TelegramSender::deleteMessage($telegramUser->telegram_chat_id, $message['message_id']);
                        return;
                    }

                    // temp
                    $queueMessageByUser->answer = $text;
                    $queueMessageByUser->save(); //

                    $queueMessageByUser = $this->queueMessageRepository->getQueueMessageById($queueMessageByUser->next_id);

                    TelegramSender::deleteMessage($telegramUser->telegram_chat_id, $message['message_id']);

                    $this->telegramMessageRepository->deleteByMessageId($message['message_id']);

//                    $lastTelegramMessage = $this->telegramMessageRepository->getLastByChatId($telegramUser->telegram_chat_id);

                    $this->dataEditMessageDto->text = $this->getTextByEventType($queueMessageByUser, $telegramUser->telegram_chat_id);

                    $this->dataEditMessageDto->keyboard = $this->gerKeyboardByQueueType($queueMessageByUser);

                    if($queueMessageByUser->type === "NOTIFICATION_TYPE"){
                        $this->dataEditMessageDto->keyboardData = $this->telegramGroupRepository->getCountByUser($telegramUser->telegram_chat_id);
                    }

//                    $messageSendDto = new MessageSendDto();
//                    $messageSendDto->text = $this->dataEditMessageDto->text;
//                    $messageSendDto->user = $telegramUser;
//                    $messageSendDto->command = 'test';
//                    $messageSendDto->type_btn = '';
//                    TelegramMessage::newMessage($messageSendDto);

                    $this->dataEditMessageDto->chat_id = $telegramUser->telegram_chat_id;

                    $this->dataEditMessageDto->message_id = $queueMessageByUser->message_id;

                    TelegramSender::editMessageTextSend($this->dataEditMessageDto);

//                    $messageDto->command = $queueMessageByUser->type;
//                    $this->telegramMessageRepository->create($messageDto);
                }
                break;
        }
        TelegramSender::deleteMessage($telegramUser->telegram_chat_id, $messageDto->message_id);
    }

    /**
     * @param QueueMessage $queueMessageByUser
     * @return string
     */
    private function gerKeyboardByQueueType(QueueMessage $queueMessageByUser): string
    {
//        if ($queueMessageByUser->type === 'CONFIRMATION') {
//            $textKeyboard = 'confirmation_event';
//        } elseif ($queueMessageByUser->type === 'NOTIFICATION_TYPE') {
//            $textKeyboard = 'notification_type';
//        } else {
//            $textKeyboard = 'process_set_event';
//        }
        return match($queueMessageByUser->type){
            'CONFIRMATION'=>'confirmation_event',
            'NOTIFICATION_TYPE'=>'notification_type',
            default =>'process_set_event'
        };
    }

    /**
     * @throws QueueTypeException
     * @throws TelegramSDKException
     */
    private function dataMappingListEvent(Collection $queueMessagesByUser, TelegramUser $telegramUser, int $messageId): void
    {
        if (count($queueMessagesByUser) === 0) return;

        $listEventDto = new ListEventDto();
        foreach ($queueMessagesByUser as $queueMessage) {
            match ($queueMessage->type) {
                "NANE_WHOSE_BIRTHDAY" => $listEventDto->name = $queueMessage->answer,
                "DATE_OF_BIRTH" => $listEventDto->date_event_at = Carbon::parse($queueMessage->answer),
                "NOTIFICATION_TYPE" => $listEventDto->notification_type = $queueMessage->answer,
                "GROUP" => $listEventDto->group_id = (int)$queueMessage->answer,
                "TIME_NOTIFICATION" => $listEventDto->notification_time_at = $queueMessage->answer,
                "PERIOD" => $listEventDto->period = $queueMessage->answer,
                "CONFIRMATION" => "",
                default => throw new QueueTypeException($queueMessage->type . ' - такой тип очереди не существует')
            };
        };

        $listEventDto->period = match ($queueMessagesByUser[0]->event_type) {
            "birthday" => 'annually'
        };

        $listEventDto->type = $queueMessagesByUser[0]->event_type;
        $listEventDto->telegram_user_id = $queueMessagesByUser[0]->telegram_user_id;
        $newEvent = $this->listEventRepository->create($listEventDto);

        if ($newEvent) {
            $this->dataEditMessageDto->text = $this->textUseCase->getSuccessConfirmText($queueMessagesByUser[0]->event_type);
            $this->dataEditMessageDto->keyboard = 'settings_menu';
            $this->dataEditMessageDto->chat_id = $telegramUser->telegram_chat_id;
            $this->dataEditMessageDto->message_id = $messageId;

            TelegramSender::editMessageTextSend($this->dataEditMessageDto);
        }
    }

    /**
     * @param QueueMessage $queueMessageByUser
     * @param string $chatId
     * @return string|null
     * @throws EventNotFoundException|QueueTypeException
     */
    private function getTextByEventType(QueueMessage $queueMessageByUser, string $chatId = ''): ?string
    {
        return match ($queueMessageByUser->event_type) {
            "birthday" => QueueMessageUseCase::getMessageByType(
                $queueMessageByUser,
                $this->queueMessageRepository,
                $this->telegramGroupRepository,
                $chatId
            )
        };
    }

    /**
     * @param TelegramUser $telegramUser
     * @param bool $isNewUser
     * @return void
     * @throws TelegramSDKException
     */
    private function start(TelegramUser $telegramUser, MessageDto $messageDto, bool $isNewUser): void
    {
        $text = $this->textUseCase->getGreetingsText($isNewUser);

        $messageSendDto = new MessageSendDto();
        $messageSendDto->text = $text;
        $messageSendDto->user = $telegramUser;
        $messageSendDto->command = '/start';
        $messageSendDto->type_btn = 'main_menu';


//        $messageDto->command = '/start';
//        $this->telegramMessageRepository->create($messageDto);

        TelegramMessage::newMessage($messageSendDto);
    }

    /**
     * @param $message
     * @return bool
     */
    private function checkMessage($message): bool
    {
        if (
            $message->text === "" ||
            !isset($message->message_id) ||
            $message->message_id === 0
        ) {
            return false;
        }
        return true;
    }

    /**
     * @param $message
     * @return bool
     */
    private function checkChatTitle($message): bool
    {
        if (
            $message->chat_title === ""
        ) {
            return false;
        }
        return true;
    }


}
