<?php

declare(strict_types=1);

namespace Art\Code\Application\UseCase\Bot;

use Art\Code\Domain\Dto\MessageDto;
use Art\Code\Domain\Dto\MessageSendDto;
use Art\Code\Domain\Dto\TelegramUserDto;
use Art\Code\Domain\Entity\TelegramMessage;
use Art\Code\Domain\Entity\TelegramSender;
use Art\Code\Domain\Entity\TelegramUser;
use Art\Code\Domain\Exception\GroupCreateException;
use Art\Code\Domain\Exception\GroupDeleteException;
use Telegram\Bot\Api;
use Telegram\Bot\Exceptions\TelegramSDKException;

class BotUseCase
{
    private Api $telegram;
    public array $newRequest;
    private TextUseCase $textUseCase;
    private GroupUseCase $groupUseCase;

    /**
     * @throws TelegramSDKException
     */
    public function __construct(
        public $telegramUserRepository,
        public $telegramMessageRepository,
        public $telegramGroupRepository,
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
     */
    public function hook()
    {
        $message = [];

        if ($_ENV['APP_ENV'] == 'prod') {
            $updates = $this->telegram->getWebhookUpdate();
            $message = $updates->getMessage();
        }
//        $message = $this->newRequest;
        $messageDto = new MessageDto($message);

        $this->telegramMessageRepository->create($messageDto);

        if (!$this->checkText($messageDto) && !$this->checkChatTitle($messageDto)) {
            return 'Not enough data!';
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

        $isNewUser = false;
        if ($telegramUser === null) {
            $telegramUser = $this->telegramUserRepository->create(new TelegramUserDto($message));
            $isNewUser = true;
        } else {
            /*
             * If user change login in telegram
             * */
            if ($telegramUser->login != $messageDto->user_name) {
                $this->telegramMessageRepository->updateByField($telegramUser, 'login', $messageDto->user_name);
                $txt = $this->textUseCase->getChangeLoginText($messageDto->user_name);

                $messageSendDto = new MessageSendDto();
                $messageSendDto->text = $txt;
                $messageSendDto->user = $telegramUser;
                $messageSendDto->command = '/change-username';

                TelegramMessage::newMessage($messageSendDto);
            }
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

                    break;
                case "to_the_beginning":

                    $text = $this->textUseCase->getGreetingsText($isNewUser);
                    $this->telegram->editMessageText([
                        'chat_id' => $telegramUser->telegram_chat_id,
                        'message_id' => $message_id,
                        'text' => $text,
                        'reply_markup' => TelegramSender::getKeyboard('main_menu'),
                        'parse_mode' => 'HTML',
                    ]);

                    break;
                case "what_can_bot":

                    $text = $this->textUseCase->getWhatCanText();
                    $this->telegram->editMessageText([
                        'chat_id' => $telegramUser->telegram_chat_id,
                        'message_id' => $message_id,
                        'text' => $text,
                        'reply_markup' => TelegramSender::getKeyboard('to_the_beginning'),
                        'parse_mode' => 'HTML',
                    ]);

                    break;
                case "how_use":

                    $text = $this->textUseCase->getHowUseText();
                    $this->telegram->editMessageText([
                        'chat_id' => $telegramUser->telegram_chat_id,
                        'message_id' => $message_id,
                        'text' => $text,
                        'reply_markup' => TelegramSender::getKeyboard('to_the_beginning'),
                        'parse_mode' => 'HTML',
                    ]);

                    break;
                case "private_cabinet":

                    $text = $this->textUseCase->getPrivateCabinetText();
                    $this->telegram->editMessageText([
                        'chat_id' => $telegramUser->telegram_chat_id,
                        'message_id' => $message_id,
                        'text' => $text,
                        'reply_markup' => TelegramSender::getKeyboard('settings_menu'),
                        'parse_mode' => 'HTML',
                    ]);

                    break;
                case "list_groups":
                    $listGroups = $this->telegramGroupRepository->getListByUser($telegramUser->telegram_chat_id);
                    $text = $this->textUseCase->getListGroupText($listGroups);
                    $this->telegram->editMessageText([
                        'chat_id' => $telegramUser->telegram_chat_id,
                        'message_id' => $message_id,
                        'text' => $text,
                        'reply_markup' => TelegramSender::getKeyboard('settings_menu'),
                        'parse_mode' => 'HTML',
                    ]);

                    break;

                case "add_birthday":

                    $addBirthdayUseCase = new AddBirthdayUseCase($this->telegram, $this->textUseCase, $telegramUser, $message_id);
                    $addBirthdayUseCase->addBirthday();

                    break;
                default:
                    break;
            }
        }

        switch ($text) {
            case "/start":
                $this->start($telegramUser, $isNewUser);
                break;

            default:
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
