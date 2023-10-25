<?php

declare(strict_types=1);

namespace Art\Code\Application\UseCase\Bot;

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

                    $text = $this->textUseCase->getAboutText();
                    $this->telegram->editMessageText([
                        'chat_id' => $telegramUser->telegram_chat_id,
                        'message_id' => $message_id,
                        'text' => $text,
                        'reply_markup' => TelegramSender::getKeyboard('to_the_beginning'),
                        'parse_mode' => 'HTML',
                    ]);

                    return;
                case "to_the_beginning":

                    $text = $this->textUseCase->getGreetingsText($isNewUser);
                    $this->telegram->editMessageText([
                        'chat_id' => $telegramUser->telegram_chat_id,
                        'message_id' => $message_id,
                        'text' => $text,
                        'reply_markup' => TelegramSender::getKeyboard('main_menu'),
                        'parse_mode' => 'HTML',
                    ]);

                    return;
                case "what_can_bot":

                    $text = $this->textUseCase->getWhatCanText();
                    $this->telegram->editMessageText([
                        'chat_id' => $telegramUser->telegram_chat_id,
                        'message_id' => $message_id,
                        'text' => $text,
                        'reply_markup' => TelegramSender::getKeyboard('to_the_beginning'),
                        'parse_mode' => 'HTML',
                    ]);

                    return;
                case "how_use":

                    $text = $this->textUseCase->getHowUseText();
                    $this->telegram->editMessageText([
                        'chat_id' => $telegramUser->telegram_chat_id,
                        'message_id' => $message_id,
                        'text' => $text,
                        'reply_markup' => TelegramSender::getKeyboard('to_the_beginning'),
                        'parse_mode' => 'HTML',
                    ]);

                    return;
                case "settings_menu":
                case "private_cabinet":
                case "changed_my_mind":

                $text = $this->textUseCase->getPrivateCabinetText();
                $this->telegram->editMessageText([
                    'chat_id' => $telegramUser->telegram_chat_id,
                    'message_id' => $message_id,
                    'text' => $text,
                    'reply_markup' => TelegramSender::getKeyboard('settings_menu'),
                    'parse_mode' => 'HTML',
                ]);

                return;
                case "list_groups":

                    $listGroups = $this->telegramGroupRepository->getListByUser($telegramUser->telegram_chat_id);
                    $text = $this->textUseCase->getListGroupText($listGroups);
                    $this->telegram->editMessageText([
                        'chat_id' => $telegramUser->telegram_chat_id,
                        'message_id' => $message_id,
                        'text' => $text,
                        'reply_markup' => TelegramSender::getKeyboard('to_the_settings_menu'),
                        'parse_mode' => 'HTML',
                    ]);

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
                        $text = $this->textUseCase->getPrivateCabinetText();

                        $this->telegram->editMessageText([
                            'chat_id' => $telegramUser->telegram_chat_id,
                            'message_id' => $message_id,
                            'text' => $text,
                            'reply_markup' => TelegramSender::getKeyboard('settings_menu'), // process_set_event
                            'parse_mode' => 'HTML',
                        ]);
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

                        $previousMessage->answer = '';
                        $previousMessage->save();

                        $text = AddBirthdayUseCase::getMessageByType($previousMessage);

                        $this->telegram->editMessageText([
                            'chat_id' => $telegramUser->telegram_chat_id,
                            'message_id' => $message_id,
                            'text' => $text,
                            'reply_markup' => TelegramSender::getKeyboard('process_set_event'),
                            'parse_mode' => 'HTML',
                        ]);
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

                $queueMessageByUser = QueueMessage::where('state','SENT')
                    ->where('telegram_user_id',$telegramUser->id)
                    ->orderBy('id','desc')
                    ->first();
                if($queueMessageByUser && $text != ''){

                    // VALIDATION

                    $queueMessageByUser->answer = $text;
                    $queueMessageByUser->save();

//                    $queueMessageByUser = QueueMessage::where('id', $queueMessageByUser->next_id)->orderBy('id','desc')->first();
                    $queueMessageByUser = $this->queueMessageRepository->getQueueMessageById($queueMessageByUser->next_id);

                    $text = AddBirthdayUseCase::getMessageByType($queueMessageByUser);

                    TelegramSender::deleteMessage($telegramUser->telegram_chat_id, $message['message_id']);

                    $this->telegramMessageRepository->deleteByMessageId($message['message_id']);
//                    TelegramMessage::where('message_id', $message['message_id'])->delete();

                    $lastTelegramMessage = TelegramMessage::where('chat_id', $telegramUser->telegram_chat_id)
                        ->orderBy('id','desc')
                        ->first();

                    $this->telegram->editMessageText([
                        'chat_id' => $telegramUser->telegram_chat_id,
                        'message_id' => $lastTelegramMessage->message_id,
                        'text' => $text,
                        'reply_markup' => TelegramSender::getKeyboard('process_set_event'),
                        'parse_mode' => 'HTML',
                    ]);
                }
                break;
        }
    }

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

    private function checkText($message): bool
    {
        if (
            $message->text === ""
        ) {
            return false;
        }
        return true;
    }

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
