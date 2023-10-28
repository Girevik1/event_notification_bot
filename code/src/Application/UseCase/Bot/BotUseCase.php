<?php

declare(strict_types=1);

namespace Art\Code\Application\UseCase\Bot;

use Art\Code\Application\UseCase\Message\QueueMessageUseCase;
use Art\Code\Domain\Dto\DataEditMessageDto;
use Art\Code\Domain\Dto\MessageDto;
use Art\Code\Domain\Dto\MessageSendDto;
use Art\Code\Domain\Dto\TelegramUserDto;
use Art\Code\Domain\Entity\QueueMessage;
use Art\Code\Domain\Entity\TelegramMessage;
use Art\Code\Domain\Entity\TelegramSender;
use Art\Code\Domain\Entity\TelegramUser;
use Art\Code\Domain\Exception\GroupCreateException;
use Art\Code\Domain\Exception\GroupDeleteException;
use Art\Code\Domain\Exception\TelegramMessageDataException;
use Telegram\Bot\Api;
use Telegram\Bot\Exceptions\TelegramSDKException;

class BotUseCase
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
     * @throws TelegramSDKException
     * @throws GroupCreateException
     * @throws GroupDeleteException
     * @throws TelegramMessageDataException
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

        if (
            !isset($message['message_id']) ||
            $message['message_id'] === 0
        ) {
            throw new TelegramMessageDataException('Some data is missing');
        }

//        $message['callback_query'] = $updates->callback_query ?? '';
        $messageDto = new MessageDto($message);

        $this->telegramMessageRepository->create($messageDto);

        if (!$this->checkText($messageDto) && !$this->checkChatTitle($messageDto)) {
            throw new TelegramMessageDataException('Some data is missing');
        };

        /*
         * Create group in db on added in group
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

        $text = $messageDto->text;
        $telegramUser = $this->telegramUserRepository->firstByChatId($messageDto->chat_id);


//        $telegramUser = $this->telegramUserRepository->firstByChatId('500264009');
//        $isNewUser = false;
//        $text = '';


        $isNewUser = false;
        if ($telegramUser === null) {
            $telegramUser = $this->telegramUserRepository->create(new TelegramUserDto($message));
            $isNewUser = true;
        }

        /*
         * It`s callback of line keyboard
         * */
        if (isset($updates['callback_query'])) {
            $inline_keyboard_data = $updates['callback_query']['data'];
            $message_id = $updates['callback_query']['message']['message_id'];

            switch ($inline_keyboard_data) {

                case "about_project":


                    $this->dataEditMessageDto->text = $this->textUseCase->getAboutText();
                    $this->dataEditMessageDto->keyboard = 'to_the_beginning';
                    $this->dataEditMessageDto->chat_id = $telegramUser->telegram_chat_id;
                    $this->dataEditMessageDto->message_id = $message_id;

                    TelegramSender::editMessageTextSend($this->dataEditMessageDto);

                    return;
                case "to_the_beginning":

                    $this->dataEditMessageDto->text = $this->textUseCase->getGreetingsText($isNewUser);
                    $this->dataEditMessageDto->keyboard = 'main_menu';
                    $this->dataEditMessageDto->chat_id = $telegramUser->telegram_chat_id;
                    $this->dataEditMessageDto->message_id = $message_id;

                    TelegramSender::editMessageTextSend($this->dataEditMessageDto);

                    return;
                case "what_can_bot":

                    $this->dataEditMessageDto->text = $this->textUseCase->getWhatCanText();
                    $this->dataEditMessageDto->keyboard = 'to_the_beginning';
                    $this->dataEditMessageDto->chat_id = $telegramUser->telegram_chat_id;
                    $this->dataEditMessageDto->message_id = $message_id;

                    TelegramSender::editMessageTextSend($this->dataEditMessageDto);

                    return;
                case "how_use":

                    $this->dataEditMessageDto->text = $this->textUseCase->getHowUseText();
                    $this->dataEditMessageDto->keyboard = 'to_the_beginning';
                    $this->dataEditMessageDto->chat_id = $telegramUser->telegram_chat_id;
                    $this->dataEditMessageDto->message_id = $message_id;

                    TelegramSender::editMessageTextSend($this->dataEditMessageDto);

                    return;
                case "settings_menu":
                case "private_cabinet":
                case "changed_my_mind":

                $this->dataEditMessageDto->text = $this->textUseCase->getPrivateCabinetText();
                $this->dataEditMessageDto->keyboard = 'settings_menu';
                $this->dataEditMessageDto->chat_id = $telegramUser->telegram_chat_id;
                $this->dataEditMessageDto->message_id = $message_id;

                TelegramSender::editMessageTextSend($this->dataEditMessageDto);

                return;
                case "list_groups":

                    $listGroups = $this->telegramGroupRepository->getListByUser($telegramUser->telegram_chat_id);
                    $this->dataEditMessageDto->text = $this->textUseCase->getListGroupText($listGroups);
                    $this->dataEditMessageDto->keyboard = 'to_the_settings_menu';
                    $this->dataEditMessageDto->chat_id = $telegramUser->telegram_chat_id;
                    $this->dataEditMessageDto->message_id = $message_id;

                    TelegramSender::editMessageTextSend($this->dataEditMessageDto);

                    return;

                case "add_birthday":

                    $addBirthdayUseCase = new AddBirthdayUseCase(
                        $this->telegram,
                        $telegramUser,
                        $message_id,
                        $this->queueMessageRepository
                    );

                    $addBirthdayUseCase->addBirthday();

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
                        $this->dataEditMessageDto->message_id = $message_id;

                        TelegramSender::editMessageTextSend($this->dataEditMessageDto);

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
                        $this->queueMessageRepository->updateFieldById('answer', '', $previousMessage->id);

                        $this->dataEditMessageDto->text = QueueMessageUseCase::getMessageByType($previousMessage);
                        $this->dataEditMessageDto->keyboard = 'process_set_event';
                        $this->dataEditMessageDto->chat_id = $telegramUser->telegram_chat_id;
                        $this->dataEditMessageDto->message_id = $message_id;

                        TelegramSender::editMessageTextSend($this->dataEditMessageDto);
                    }
                    return;

                default:
                    break;
            }
        }

        switch ($text) {
            case "/start":
                $this->start($telegramUser, $isNewUser);
                return;

            default:

                $queueMessageByUser = $this->queueMessageRepository->getLastSentMsg($telegramUser->id);

                if($queueMessageByUser && $text != ''){

                    // VALIDATION

                    // temp
                    $queueMessageByUser->answer = $text;
                    $queueMessageByUser->save(); //

                    $this->telegram->editMessageText([
                        'chat_id' => '500264009',
                        'message_id' => $message['message_id'],
                        'text' => 'testetetst',
                        'reply_markup' => TelegramSender::getKeyboard('process_set_event'),
                        'parse_mode' => 'HTML',
                    ]);

                    $queueMessageByUser = $this->queueMessageRepository->getQueueMessageById($queueMessageByUser->next_id);



                    TelegramSender::deleteMessage($telegramUser->telegram_chat_id, $message['message_id']);

                    $this->telegramMessageRepository->deleteByMessageId($message['message_id']);

                    $lastTelegramMessage = $this->telegramMessageRepository->getLastByChatId($telegramUser->telegram_chat_id);

                    $this->dataEditMessageDto->text = $this->getTextByEventType($queueMessageByUser);
//                    $this->dataEditMessageDto->text = AddBirthdayUseCase::getMessageByType($queueMessageByUser);

                    if ($queueMessageByUser->type === 'CONFIRMATION') {
                        $this->dataEditMessageDto->keyboard = 'confirmation_event';
                    } else {
                        $this->dataEditMessageDto->keyboard = 'process_set_event';
                    }

                    $this->dataEditMessageDto->chat_id = $telegramUser->telegram_chat_id;
                    $this->dataEditMessageDto->message_id = $lastTelegramMessage->message_id;

                    TelegramSender::editMessageTextSend($this->dataEditMessageDto);
                }
                break;
        }
    }

    /**
     * @param QueueMessage $queueMessageByUser
     * @return string|null
     */
    private function getTextByEventType(QueueMessage $queueMessageByUser): ?string
    {
        return match ($queueMessageByUser->event_type) {
            "birthday" => QueueMessageUseCase::getMessageByType($queueMessageByUser)
        };
    }

    /**
     * @param TelegramUser $telegramUser
     * @param bool $isNewUser
     * @return void
     */
    private function start(TelegramUser $telegramUser, bool $isNewUser): void
    {
        $text = $this->textUseCase->getGreetingsText($isNewUser);

        $messageSendDto = new MessageSendDto();
        $messageSendDto->text = $text;
        $messageSendDto->user = $telegramUser;
        $messageSendDto->command = '/start';
        $messageSendDto->type_btn = 'main_menu';

        TelegramMessage::newMessage($messageSendDto);
    }

    /**
     * @param $message
     * @return bool
     */
    private function checkText($message): bool
    {
        if (
            $message->text === ""
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
