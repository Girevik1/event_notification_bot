<?php

declare(strict_types=1);

namespace Art\Code\Application\UseCase\Bot;

use Art\Code\Application\UseCase\Message\QueueMessageUseCase;
use Art\Code\Domain\Dto\BotRequestDto;
use Art\Code\Domain\Dto\DataEditMessageDto;
use Art\Code\Domain\Dto\ListEventDto;
use Art\Code\Domain\Dto\MessageDto;
use Art\Code\Domain\Dto\MessageSendDto;
use Art\Code\Domain\Dto\TelegramUserDto;
use Art\Code\Domain\Entity\ListEvent;
use Art\Code\Domain\Entity\QueueMessage;
use Art\Code\Domain\Entity\TelegramMessage;
use Art\Code\Domain\Entity\TelegramUser;
use Art\Code\Domain\Exception\EventNotFoundException;
use Art\Code\Domain\Exception\QueueTypeException;
use Art\Code\Domain\Exception\TelegramMessageDataException;
use Carbon\Carbon;
use Exception;
use Illuminate\Support\Collection;
use Telegram\Bot\Exceptions\TelegramSDKException;

final class BotUseCase
{
    const BOT_ID = 6598212367;

    private TextUseCase $textUseCase;
    private GroupUseCase $groupUseCase;
    private DataEditMessageDto $dataEditMessageDto;
    private BotRequestDto $botRequestDto;

    public function __construct(
        public $telegramUserRepository,
        public $telegramMessageRepository,
        public $telegramGroupRepository,
        public $queueMessageRepository,
        public $listEventRepository,
        public $telegram
    )
    {
        $this->textUseCase = new TextUseCase();
        $this->groupUseCase = new GroupUseCase();
        $this->dataEditMessageDto = new DataEditMessageDto();

        $this->botRequestDto = new BotRequestDto();
        $this->botRequestDto->telegramUserRepository = $this->telegramUserRepository;
        $this->botRequestDto->telegramMessageRepository = $this->telegramMessageRepository;
        $this->botRequestDto->telegramGroupRepository = $this->telegramGroupRepository;
        $this->botRequestDto->queueMessageRepository = $this->queueMessageRepository;
        $this->botRequestDto->listEventRepository = $this->listEventRepository;
        $this->botRequestDto->telegram = $this->telegram;
        $this->botRequestDto->textUseCase = $this->textUseCase;
        $this->botRequestDto->groupUseCase = $this->groupUseCase;
    }

    /**
     * @throws Exception
     */
    public function hook(): void
    {
        $message = [];

        if ($_ENV['APP_ENV'] === 'prod') {
            $updates = $this->telegram->telegram->getWebhookUpdate();
            $message = $updates->getMessage();
        }

        $messageDto = new MessageDto($message);

        if (!$this->checkMessage($messageDto) && !$this->checkChatTitle($messageDto)) {
            throw new TelegramMessageDataException('Some data is missing');
        };

          // For test
//        $this->telegramMessageRepository->create($messageDto);

        /*
         * Create or remove a group in db (on added in group or left)
         *
         * P.S. Чтобы добавить бота нужно быть админом группы и выключить анонимность в настройках
         * */
        if ($this->checkChatTitle($messageDto)) {
            if($messageDto->new_chat_participant_id == self::BOT_ID){
                $this->botRequestDto->telegramUser = $this->telegramUserRepository->firstByChatId($messageDto->from_id);
                $this->botRequestDto->message = $message;
                $this->groupUseCase->groupHandlerByMessage($this->botRequestDto);
            }

            return;
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

                    $this->telegram::editMessageTextSend($this->dataEditMessageDto);

                    return;
                case "to_the_beginning":

                    $this->dataEditMessageDto->text = $this->textUseCase->getGreetingsText($isNewUser);
                    $this->dataEditMessageDto->keyboard = 'main_menu';
                    $this->dataEditMessageDto->chat_id = $telegramUser->telegram_chat_id;
                    $this->dataEditMessageDto->message_id = $messageId;

                    $this->telegram::editMessageTextSend($this->dataEditMessageDto);

                    return;
                case "what_can_bot":

                    $this->dataEditMessageDto->text = $this->textUseCase->getWhatCanText();
                    $this->dataEditMessageDto->keyboard = 'to_the_beginning';
                    $this->dataEditMessageDto->chat_id = $telegramUser->telegram_chat_id;
                    $this->dataEditMessageDto->message_id = $messageId;

                    $this->telegram::editMessageTextSend($this->dataEditMessageDto);

                    return;
                case "how_use":

                    $this->dataEditMessageDto->text = $this->textUseCase->getHowUseText();
                    $this->dataEditMessageDto->keyboard = 'to_the_beginning';
                    $this->dataEditMessageDto->chat_id = $telegramUser->telegram_chat_id;
                    $this->dataEditMessageDto->message_id = $messageId;

                    $this->telegram::editMessageTextSend($this->dataEditMessageDto);

                    return;

                case "settings_menu":
                case "private_cabinet":
                case "changed_my_mind":

                $this->dataEditMessageDto->text = $this->textUseCase->getPrivateCabinetText();
                $this->dataEditMessageDto->keyboard = 'settings_menu';
                $this->dataEditMessageDto->chat_id = $telegramUser->telegram_chat_id;
                $this->dataEditMessageDto->message_id = $messageId;

                // Удаляем все сообщения в чате по юзеру
                if ($inlineKeyboardData === 'settings_menu') {
                    $allTelegramMessageByUser = $this->telegramMessageRepository->getAllMessageByChatId($telegramUser->telegram_chat_id);
                    foreach ($allTelegramMessageByUser as $msg) {
                        if($msg->message_id === $messageId){
                           break;
                        }
                        $this->telegram::deleteMessage($msg->chat_id, $msg->message_id);
                        $this->telegramMessageRepository->deleteByMessageId($msg->message_id);
                    }
                } //

                $this->telegram::editMessageTextSend($this->dataEditMessageDto);
                $this->queueMessageRepository->deleteAllMessageByUser($telegramUser->id);

                return;

                case "list_groups":

                    $listGroups = $this->telegramGroupRepository->getListByUser($telegramUser->telegram_chat_id);
                    $this->dataEditMessageDto->text = $this->textUseCase->getListGroupText($listGroups);
                    $this->dataEditMessageDto->keyboard = 'to_the_settings_menu';
                    $this->dataEditMessageDto->chat_id = $telegramUser->telegram_chat_id;
                    $this->dataEditMessageDto->message_id = $messageId;

                    $this->telegram::editMessageTextSend($this->dataEditMessageDto);

                    $messageDto->command = 'list_groups';
                    $this->telegramMessageRepository->create($messageDto);

                    return;

                case (bool)preg_match('/^next_event_[0-9]{1,4}$/i', $inlineKeyboardData):

                    $rest = (int)substr($inlineKeyboardData, 11, 4);

                    $listEvents = ListEvent::where('telegram_user_id','=',$telegramUser->id)
                        ->orderBy('date_event_at','ASC');
//                        ->latest();
//                    $countEvents = $listEvents->count();

                    $listEvents = $listEvents
                        ->skip($rest)
                        ->take(10)
                        ->get();

                    $this->dataEditMessageDto->text = $this->textUseCase->getListEventText(
                        $listEvents,
                        $this->telegramGroupRepository,
                        $telegramUser->telegram_chat_id
                    );


//                    if ($rest === 0) {
//                        $next = 10;
//                        $back = 0;
//                        $this->dataEditMessageDto->keyboard = 'to_the_next_page';
//                    } else {
                        $next = $rest + 10;
                        $back = $rest - 10;

                        if(count($listEvents) < 10){
                            $this->dataEditMessageDto->keyboard = 'to_the_back_page';
                        }else{
                            $this->dataEditMessageDto->keyboard = 'to_the_next_back_page';
                        }

//                    }

                        $this->dataEditMessageDto->keyboardData['next'] = $next;
                    $this->dataEditMessageDto->keyboardData['back'] = $back;


                    $this->dataEditMessageDto->chat_id = $telegramUser->telegram_chat_id;
                    $this->dataEditMessageDto->message_id = $messageId;

                    $this->telegram::editMessageTextSend($this->dataEditMessageDto, $this->telegramMessageRepository);

                    return;

                    case (bool)preg_match('/^back_event_[0-9]{1,4}$/i', $inlineKeyboardData):

                    $rest = (int)substr($inlineKeyboardData, 11, 4);
                    $listEvents = ListEvent::where('telegram_user_id','=',$telegramUser->id)
                        ->orderBy('date_event_at','ASC');
//                        ->latest();

                    $listEvents = $listEvents
                        ->skip($rest)
                        ->take(10)
                        ->get();

                    $this->dataEditMessageDto->text = $this->textUseCase->getListEventText(
                        $listEvents,
                        $this->telegramGroupRepository,
                        $telegramUser->telegram_chat_id
                    );

                    if ($rest === 0) {
                        $back = 0;
                        $next = 10;
                        $this->dataEditMessageDto->keyboard = 'to_the_next_page';
                    } else {

                        $back = $rest - 10;
                        $next = $back + 20;
                        $this->dataEditMessageDto->keyboard = 'to_the_next_back_page';
                    }

                        $this->dataEditMessageDto->keyboardData['next'] = $next;
                    $this->dataEditMessageDto->keyboardData['back'] = $back;


                    $this->dataEditMessageDto->chat_id = $telegramUser->telegram_chat_id;
                    $this->dataEditMessageDto->message_id = $messageId;

                    $this->telegram::editMessageTextSend($this->dataEditMessageDto, $this->telegramMessageRepository);

                    return;

                case "list_events":

//                    $listEvents = $this->listEventRepository->getListByUser($telegramUser->id);
//
//                        $this->dataEditMessageDto->text = $this->textUseCase->getListEventText(
//                            $listEvents,
//                            $this->telegramGroupRepository,
//                            $telegramUser->telegram_chat_id
//                        );
//                    $this->dataEditMessageDto->keyboard = 'to_the_settings_menu';
//                    $this->dataEditMessageDto->chat_id = $telegramUser->telegram_chat_id;
//                    $this->dataEditMessageDto->message_id = $messageId;
//
//                        $this->telegram::editMessageTextSend($this->dataEditMessageDto, $this->telegramMessageRepository);
//
//                        if (mb_strlen($this->dataEditMessageDto->text, 'UTF-8') <= 4096) {
//                            $messageDto->command = 'list_events';
//                            $this->telegramMessageRepository->create($messageDto);
//                        }

                    $listEvents = ListEvent::where('telegram_user_id','=',$telegramUser->id)
//                        ->where([['title','LIKE',"%".$text_val."%"]])
                        ->orderBy('date_event_at','ASC');
//                ->latest();
                    $countEvents = $listEvents->count();

                    $listEvents = $listEvents->skip(0)
                        ->take(10)
                        ->get();

//                    $listEvents = $this->listEventRepository->getListByUser($telegramUser->id);

                        $this->dataEditMessageDto->text = $this->textUseCase->getListEventText(
                            $listEvents,
                            $this->telegramGroupRepository,
                            $telegramUser->telegram_chat_id
                        );

                    if ($countEvents > 10) {
                        $this->dataEditMessageDto->keyboard = 'to_the_next_page';
                        $this->dataEditMessageDto->keyboardData['next'] = 10;
                    } else {
                        $this->dataEditMessageDto->keyboard = 'to_the_settings_menu';
                    }
                    $this->dataEditMessageDto->chat_id = $telegramUser->telegram_chat_id;
                    $this->dataEditMessageDto->message_id = $messageId;

                        $this->telegram::editMessageTextSend($this->dataEditMessageDto, $this->telegramMessageRepository);

//                        if (mb_strlen($this->dataEditMessageDto->text, 'UTF-8') <= 4096) {
                            $messageDto->command = 'list_events';
                            $this->telegramMessageRepository->create($messageDto);
//                        }

                    return;

                case "add_anniversary":

                    $this->botRequestDto->telegramUser = $telegramUser;
                    $this->botRequestDto->messageId = $messageId;
                    $anniversaryUseCase = new AnniversaryUseCase($this->botRequestDto);

                    $anniversaryUseCase->addAnniversary();

                    return;

                case "add_birthday":

                $this->botRequestDto->telegramUser = $telegramUser;
                $this->botRequestDto->messageId = $messageId;
                $birthdayUseCase = new BirthdayUseCase($this->botRequestDto);

                $birthdayUseCase->addBirthday();

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

                    $this->telegram::editMessageTextSend($this->dataEditMessageDto);

                    return;

                case "group_notice":

                    $countAccessGroup = $this->telegramGroupRepository->getCountByUser($telegramUser->telegram_chat_id);
                    if ($countAccessGroup === 0) {
                        return;
                    }

                    $lastSentQueueMessage = $this->queueMessageRepository->getLastSentMsg($telegramUser->id);
                    $this->queueMessageRepository->updateFieldById('answer', 'group', $lastSentQueueMessage->id);
                    $queueMessageByUser = $this->queueMessageRepository->getQueueMessageById($lastSentQueueMessage->next_id);

                    $this->dataEditMessageDto->text = $this->getTextByEventType($queueMessageByUser, $telegramUser->telegram_chat_id);
                    $this->dataEditMessageDto->keyboard = $this->gerKeyboardByQueueType($queueMessageByUser);
                    $this->dataEditMessageDto->chat_id = $telegramUser->telegram_chat_id;
                    $this->dataEditMessageDto->message_id = $messageId;

                    $this->telegram::editMessageTextSend($this->dataEditMessageDto);

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

                        $this->telegram::editMessageTextSend($this->dataEditMessageDto);
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
                            $this->dataEditMessageDto->keyboardData['count_group'] = $this->telegramGroupRepository->getCountByUser($telegramUser->telegram_chat_id);
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

                        $this->telegram::editMessageTextSend($this->dataEditMessageDto);
                    }
                    return;

                default:
                    break;
            }
        }

        /* Прием и обработка отравленного текста в чат
         * */
        $text = $messageDto->text;

        switch ($text) {
            case "/start":
                $this->start($telegramUser, $isNewUser);
                $this->queueMessageRepository->deleteAllMessageByUser($telegramUser->id);

                return;

            case (bool)preg_match('/^event [0-9]{1,3}$/i', $text):

                $textArray = explode(' ', $text);
                $idEvent = end($textArray);
                $result = $this->listEventRepository->deleteEventById((int)$idEvent, $telegramUser->id);

                $this->telegram::deleteMessage($telegramUser->telegram_chat_id, $messageDto->message_id);

                if(!$result){
                    return;
                }



                $listEvents = ListEvent::where('telegram_user_id','=',$telegramUser->id)
//                        ->where([['title','LIKE',"%".$text_val."%"]])
//                        ->orderBy('id','ASC');
                    ->latest();
                $countEvents = $listEvents->count();

                $listEvents = $listEvents->skip(0)
                    ->take(10)
                    ->get();

//                    $listEvents = $this->listEventRepository->getListByUser($telegramUser->id);

                $this->dataEditMessageDto->text = $this->textUseCase->getListEventText(
                    $listEvents,
                    $this->telegramGroupRepository,
                    $telegramUser->telegram_chat_id
                );

                if ($countEvents > 10) {
                    $this->dataEditMessageDto->keyboard = 'to_the_next_page';
                    $this->dataEditMessageDto->keyboardData['next'] = 10;
                } else {
                    $this->dataEditMessageDto->keyboard = 'to_the_settings_menu';
                }
                $this->dataEditMessageDto->chat_id = $telegramUser->telegram_chat_id;

                $telegramMessage = $this->telegramMessageRepository->getLastMessageByCommand($telegramUser->telegram_chat_id, 'list_events');

                $this->dataEditMessageDto->message_id = $telegramMessage->message_id ?? 0;

                $this->telegram::editMessageTextSend($this->dataEditMessageDto, $this->telegramMessageRepository);





//                $telegramMessage = $this->telegramMessageRepository->getLastMessageByCommand($telegramUser->telegram_chat_id, 'list_events');
//
//                $listEvents = $this->listEventRepository->getListByUser($telegramUser->id);
//                $this->dataEditMessageDto->text = $this->textUseCase->getListEventText(
//                    $listEvents,
//                    $this->telegramGroupRepository,
//                    $telegramUser->telegram_chat_id
//                );
//                $this->dataEditMessageDto->keyboard = 'to_the_settings_menu';
//                $this->dataEditMessageDto->chat_id = $telegramUser->telegram_chat_id;
//                $this->dataEditMessageDto->message_id = $telegramMessage->message_id ?? 0;
//
//
//                     // Удаляем все сообщения в чате по юзеру
//                    $allTelegramMessageByUser = $this->telegramMessageRepository->getAllMessageByChatId($telegramUser->telegram_chat_id);
//                    foreach ($allTelegramMessageByUser as $msg) {
//                        if($msg->message_id === $telegramMessage->message_id){
//                            break;
//                        }
//                        $this->telegram::deleteMessage($msg->chat_id, $msg->message_id);
//                        $this->telegramMessageRepository->deleteByMessageId($msg->message_id);
//                    } //
//
//
//                $this->telegram::editMessageTextSend($this->dataEditMessageDto, $this->telegramMessageRepository);

                return;

            case (bool)preg_match('/^group [0-9]{1,3}$/i', $text):

                $textArray = explode(' ', $text);
                $idGroup = end($textArray);

                $this->telegram::deleteMessage($telegramUser->telegram_chat_id, $messageDto->message_id);

                $group = $this->telegramGroupRepository->getFirstById((int)$idGroup, $telegramUser->telegram_chat_id);
                if (!$group) return;

                $result = $this->telegramGroupRepository->deleteById($group->id, $telegramUser->telegram_chat_id);
                if (!$result) return;

                $this->listEventRepository->updateAllByGroup($group->id, $telegramUser->id, 'group_id', 0);

                $telegramMessage = $this->telegramMessageRepository->getLastMessageByCommand($telegramUser->telegram_chat_id, 'list_groups');

                $listGroups = $this->telegramGroupRepository->getListByUser($telegramUser->telegram_chat_id);

                $this->dataEditMessageDto->text = $this->textUseCase->getListGroupText($listGroups);
                $this->dataEditMessageDto->keyboard = 'to_the_settings_menu';
                $this->dataEditMessageDto->chat_id = $telegramUser->telegram_chat_id;
                $this->dataEditMessageDto->message_id = $telegramMessage->message_id ?? 0;

                $this->telegram::editMessageTextSend($this->dataEditMessageDto);

                $this->telegram->telegram->leaveChat(['chat_id' => $group->group_chat_id]);

                return;

            default:

                $queueMessageByUser = $this->queueMessageRepository->getLastSentMsg($telegramUser->id);

                /* Принимаем ответы на сообщение очереди эвента от клиента
                 * */
                if ($queueMessageByUser && $text !== '') {

                    /* Если на этапе выбора "как уведомлять" -
                     * отправили текст, пересекаем
                     * */
                    if ($queueMessageByUser->type === 'NOTIFICATION_TYPE') {
                        $this->telegram::deleteMessage($telegramUser->telegram_chat_id, $messageDto->message_id);
                        return;
                    }

                    if(!$text = $this->validationIncomingText(
                        $text,
                        $queueMessageByUser,
                        $telegramUser,
                        $messageDto->message_id)
                    ){
                        return;
                    }

                    // temp
                    $queueMessageByUser->answer = $text;
                    $queueMessageByUser->save(); //
                    $queueMessageByUser = $this->queueMessageRepository->getQueueMessageById($queueMessageByUser->next_id);

                    $this->prepareTextForSend(
                        $telegramUser,
                        $queueMessageByUser,
                        $messageDto->message_id
                    );
                }
                break;
        }
        $this->telegram::deleteMessage($telegramUser->telegram_chat_id, $messageDto->message_id);
    }

    /**
     * @throws TelegramSDKException
     */
    public function checkBirthdayToday(): void
    {
        BirthdayUseCase::checkBirthdayByCron($this->botRequestDto);
    }

    /**
     * @throws TelegramSDKException
     */
    public function checkAnniversaryToday(): void
    {
        AnniversaryUseCase::checkAnniversaryByCron($this->botRequestDto);
    }

    /**
     * @throws EventNotFoundException
     * @throws TelegramSDKException
     * @throws QueueTypeException
     */
    private function prepareTextForSend(
        TelegramUser $telegramUser,
        QueueMessage $queueMessageByUser,
        int          $messageId,
        string       $additionalText = ''
    ): void
    {
        $this->telegram::deleteMessage($telegramUser->telegram_chat_id, $messageId);

        $this->dataEditMessageDto->text = $this->getTextByEventType($queueMessageByUser, $telegramUser->telegram_chat_id);

        if ($additionalText) {
            $this->dataEditMessageDto->text .= $additionalText;
        }

        $this->dataEditMessageDto->keyboard = $this->gerKeyboardByQueueType($queueMessageByUser);

        if ($queueMessageByUser->type === "NOTIFICATION_TYPE") {
            $this->dataEditMessageDto->keyboardData['count_group'] = $this->telegramGroupRepository->getCountByUser($telegramUser->telegram_chat_id);
        }

        $this->dataEditMessageDto->chat_id = $telegramUser->telegram_chat_id;

        $this->dataEditMessageDto->message_id = $queueMessageByUser->message_id;

        $this->telegram::editMessageTextSend($this->dataEditMessageDto);
    }

    /**
     * @param QueueMessage $queueMessageByUser
     * @return string
     */
    private function gerKeyboardByQueueType(QueueMessage $queueMessageByUser): string
    {
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
    private function dataMappingListEvent(
        Collection $queueMessagesByUser,
        TelegramUser $telegramUser,
        int $messageId
    ): void
    {
        if (count($queueMessagesByUser) === 0) return;

        $listEventDto = new ListEventDto();
        foreach ($queueMessagesByUser as $queueMessage) {
            match ($queueMessage->type) {
                "NANE_WHOSE_BIRTHDAY", "NANE_EVENT" => $listEventDto->name = $queueMessage->answer,
                "DATE_OF_BIRTH", "DATE_OF_EVENT" => $listEventDto->date_event_at = Carbon::parse($queueMessage->answer),
                "NOTIFICATION_TYPE" => $listEventDto->notification_type = $queueMessage->answer,
                "GROUP" => $listEventDto->group_id = (int)$queueMessage->answer,
                "TIME_NOTIFICATION" => $listEventDto->notification_time_at = $queueMessage->answer,
                "PERIOD" => $listEventDto->period = $queueMessage->answer,
                "CONFIRMATION" => "",
                default => throw new QueueTypeException($queueMessage->type . ' - такой тип очереди не существует')
            };
        };

        $listEventDto->period = match ($queueMessagesByUser[0]->event_type) {
            "birthday", "anniversary" => 'annually'
        };

        $listEventDto->type = $queueMessagesByUser[0]->event_type;
        $listEventDto->telegram_user_id = $queueMessagesByUser[0]->telegram_user_id;
        $newEvent = $this->listEventRepository->create($listEventDto);

        if ($newEvent) {
            $this->dataEditMessageDto->text = $this->textUseCase->getSuccessConfirmText($queueMessagesByUser[0]->event_type);
            $this->dataEditMessageDto->keyboard = 'settings_menu';
            $this->dataEditMessageDto->chat_id = $telegramUser->telegram_chat_id;
            $this->dataEditMessageDto->message_id = $messageId;

            $this->telegram::editMessageTextSend($this->dataEditMessageDto);
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
            "birthday", "anniversary" => QueueMessageUseCase::getMessageByType(
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
    private function start(TelegramUser $telegramUser, bool $isNewUser): void
    {
        $text = $this->textUseCase->getGreetingsText($isNewUser);

        $messageSendDto = new MessageSendDto();
        $messageSendDto->text = $text;
        $messageSendDto->chat_id = $telegramUser->telegram_chat_id;
        $messageSendDto->command = '/start';
        $messageSendDto->type_btn = 'main_menu';
        $messageSendDto->telegramMessageRepository = $this->telegramMessageRepository;
        $messageSendDto->telegram = $this->telegram;

        TelegramMessage::newMessage($messageSendDto);
    }

    /**
     * @throws EventNotFoundException
     * @throws TelegramSDKException
     * @throws QueueTypeException
     */
    private function validationIncomingText(
        string $text,
        QueueMessage $queueMessageByUser,
        TelegramUser $telegramUser,
        int $messageId
    ):string|bool
    {
        $result = true;
        $validationText = '';

        $text = trim($text);
        $text = stripslashes($text);
        $text = htmlspecialchars($text);

        switch ($queueMessageByUser->type) {

            case "NANE_EVENT":
            case "NANE_WHOSE_BIRTHDAY":

                $lengthText = mb_strlen($text);
                if ($lengthText > 64) {
                    $result = false;
                    $validationText = "\n\n‼️ <b>Превышен максимальный размер текста 64 символа!</b>";
                }
                if ($lengthText < 2) {
                    $result = false;
                    $validationText = "\n\n‼️ <b>Минимальный размер текста 2 символа!</b>";
                }
                break;

            case "DATE_OF_EVENT":
            case "DATE_OF_BIRTH":

                $isValidFormat = preg_match('/^(0[1-9]|[12][0-9]|3[01])[\-\.](0[1-9]|1[012])[\-\.](19|20)\d\d$/', $text);

                if (!$isValidFormat) {
                    $result = false;
                    $validationText = "\n\n‼️ <b>Указан некорректный формат даты!</b>";
                }
                break;

            case "GROUP":

                $isValidFormat = preg_match('/^(\d{1,4})$/', $text);
                if (!$isValidFormat) {
                    $result = false;
                    $validationText = "\n\n‼️ <b>Указан некорректный формат, передайте номер группы!</b>";
                    break;
                }
                $group = $this->telegramGroupRepository->getFirstById((int)$text, $telegramUser->telegram_chat_id);
                if ($group === null) {
                    $result = false;
                    $validationText = "\n\n‼️ <b>Некорректый номер, группа не найдена!</b>";
                }
                break;

            case "TIME_NOTIFICATION":

                $isValidFormat = preg_match('/^([0-1]\d|2[0-3])(\:[0-5]\d)$/', $text);

                if (!$isValidFormat) {
                    $result = false;
                    $validationText = "\n\n‼️ <b>Указан некорректный формат времени!</b>";
                }
                break;

            default:
                return true;
        }

        if(!$result){
            $this->prepareTextForSend(
                $telegramUser,
                $queueMessageByUser,
                $messageId,
                $validationText
            );
             return false;
        }

        return $text;
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
        if ($message->chat_title === "") {
            return false;
        }
        return true;
    }

    /**
     * @param $year
     * @return string
     */
    public static function yearTextArg($year): string
    {
        $year = abs($year);
        $t1 = $year % 10;
        $t2 = $year % 100;

        return ($t1 == 1 && $t2 != 11 ? "год" : ($t1 >= 2 && $t1 <= 4 && ($t2 < 10 || $t2 >= 20) ? "года" : "лет"));
    }
}
