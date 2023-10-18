<?php

declare(strict_types=1);

namespace Art\Code\Application\UseCase\Bot;

use Art\Code\Application\UseCase\Message\MessageTextUseCase;
use Art\Code\Domain\Dto\MessageDataDto;
use Art\Code\Domain\Dto\TelegramUserDto;
use Art\Code\Domain\Entity\TelegramMessage;
use Art\Code\Domain\Entity\TelegramSender;
use Art\Code\Domain\Entity\TelegramUser;
use Telegram\Bot\Api;
use Telegram\Bot\Exceptions\TelegramSDKException;

class BotUseCase
{
    private Api $telegram;
    public array $newRequest;
    private MessageTextUseCase $messageTextUseCase;

    /**
     * @throws TelegramSDKException
     */
    public function __construct(
        public $telegramUserRepository,
        public $telegramMessageRepository
//        private readonly TelegramUserRepositoryInterface    $telegramUserRepository,
//        private readonly TelegramMessageRepositoryInterface $telegramMessageRepository
    )
    {
        $this->telegram = new Api($_ENV['TELEGRAM_BOT_TOKEN']);
        $this->messageTextUseCase = new MessageTextUseCase();
//        $this->newRequest = json_decode(file_get_contents("php://input"), true); // for test/
    }

    /**
     * For test hook bot
     */
//    public static function testStart(): void
//    {
//        $message = [
//            "chat" => [
//                "id" => 12345678901,
//
////                "username" => "",
//                "username" => "test"
//            ],
//            "text" => "/start",
//            "message_id" => 100
//        ];
//        $t = new BotUseCase();
//        $t->hook($message);
//        echo  111;
//    }
//
//    public static function testHook()
//    {
//        $message = [
//            "chat" => [
//                "id" => 12345678901,
//
//                "username" => "test"
//            ],
//            "text" => "/confidential",
//            "message_id" => 100
//        ];
//        $t = new BotUseCase();
//        $t->hook($message);
//    }
//
//    public static function testA1()
//    {
//        $message = [
//            "chat" => [
//                "id" => 12345678901,
//
//                "username" => "test"
//            ],
//            "text" => "/c1",
//            "message_id" => 100
//        ];
//        $t = new BotUseCase();
//        $t->hook($message);
//    }
//
//    public static function testA($text = '')
//    {
//        $message = [
//            "chat" => [
//                "id" => 12345678901,
//
//                "username" => "test"
//            ],
//            "text" => 'Торговля',
//            "message_id" => 100
//        ];
//        $t = new BotUseCase();
//        $t->hook($message);
//    } //

    public function hook()
    {
        $message = [];

        if ($_ENV['APP_ENV'] == 'prod') {
            $updates = $this->telegram->getWebhookUpdate();
            $message = $updates->getMessage();
        }
        $this->telegramMessageRepository->create($message);
//        $message = $this->newRequest;



//        $message = [
//            "chat" => [
//                "id" => 12345678901,
//                "username" => "test"
//            ],
//            "text" => "/start",
//            "message_id" => 100
//        ];




        if (!$this->checkMessage($message)) {
            return 'check data msg!';
        };

        $text = $message["text"];
//        $reply_to_message = [];
//
        $chat_id = $message["chat"]["id"];
        $username = strtolower($message["chat"]["username"]);
//        $message_id = $message["message_id"];
//
//        if (isset($message["reply_to_message"])) {
//            $reply_to_message = $message["reply_to_message"];
//        }

        $telegramUser = $this->telegramUserRepository->firstByChatId($chat_id);

//        $this->telegramMessageRepository->create($user);


        $isNewUser = false;
        if ($telegramUser === null) {
            $telegramUser = $this->telegramUserRepository->create(new TelegramUserDto($message));
            $isNewUser = true;
        } else {
            $was_message = false;
            if ($telegramUser->login != $username) {
                $this->telegramMessageRepository->updateByField($telegramUser, 'login', strtolower($username));
                $txt = $this->messageTextUseCase->getChangeLoginText($username);

                $messageDataDto = new MessageDataDto();
                $messageDataDto->text = $txt;
                $messageDataDto->user = $telegramUser;
                $messageDataDto->command = '/change-username';

                TelegramMessage::newMessage($messageDataDto);

                $was_message = true;
            }
        }

        // It`s callback of line keybord
        if (isset($updates['callback_query'])) {
            $inline_keyboard_data = $updates['callback_query']['data'];
            $message_id = $updates['callback_query']['message']['message_id'];

            switch ($inline_keyboard_data) {

                case "about_project":
                    $text = "<b>О проекте\n</b>";
                    $text .= "\n\nМы часто забываем про дни рождения, годовщины и тд..";
                    $text .= "\nБот создан для уведомления события в чатах и каналах -> исходя от Ваших установок в личном кабинете бота.";
                    $text .= "\nФункционал развивается, не судите строго";
                    $text .= "\n Version: 1.0.0";
                    $text .= "\n\nДля фидбека и предложений пишите - <a href='https://t.me/artur_timerkhanov'>Создатель</a>";

                    $this->telegram->editMessageText([
                        'chat_id' => $telegramUser->telegram_chat_id,
                        'message_id' => $message_id,
                        'text' => $text,
                        'reply_markup'=> TelegramSender::getKeyboard('to_the_beginning')
                    ]);

                    break;
                case "to_the_beginning":

                    $text = $this->messageTextUseCase->getGreatingsText($isNewUser);

                    $this->telegram->editMessageText([
                        'chat_id' => $telegramUser->telegram_chat_id,
                        'message_id' => $message_id,
                        'text' => $text,
                        'reply_markup'=> TelegramSender::getKeyboard('main_menu')
                    ]);

                    break;
                case "ceo-proc-skip":
                    $text = "/skip";
                    break;
                default:
                    break;
            }



//            $reply_to_message['message_id'] = $message_id;

//            TelegramSender::sendMessage($user->login, $text, '', $message_id);
        } //

        switch ($text) {
            case "/start":
                $this->start($telegramUser, $isNewUser);
//                $command = $text;
                break;
//            case "/block":
//                $answer = $this->block(strtolower($message["chat"]["username"]), $chat_id, $message["message_id"]);
//                $command = $text;
//                break;

//            case "-":
//            case "?":
//            case (bool)preg_match('/\d{2}\.\d{2}\.\d{2}/', $text):
//            case (bool)preg_match('/[0-9]+-[0-9]+-[0-9]+/', $text):
//            case "+":
//                $answer = $this->answer(
//                    strtolower($message["chat"]["username"]),
//                    $chat_id,
//                    $full_text,
//                    $reply_to_message
//                );
//                $command = $text;
//                break;
//
//            case "/skip":
//                $answer = $this->skipCFO(strtolower($message["chat"]["username"]), $chat_id, $reply_to_message);
//                $command = $text;
//                break;

            default:
                break;
        }

//        $this->telegramUserRepository->create();
//        $this->telegramMessageRepository->create($message);

//        return 'ok test';
//        $users = (new TelegramUserRepository())->firstById(new Id(1));
//        var_dump($users);

//        if (!isset($message['text'])) {
//            return 0;
//        }
//        if (
//            !isset($message["chat"]["username"]) ||
//            !isset($message['text']) ||
//            $message['text'] == "" ||
//            $message['text'] == null ||
//            empty($message['text'])
//        ) {
//            return 0;
//        }
//        if (!isset($message["chat"]["username"])) {
//            return 0;
//        }
    }

    private function start(TelegramUser $telegramUser, bool $isNewUser): void
    {
        $text = $this->messageTextUseCase->getGreatingsText($isNewUser);

        $messageDataDto = new MessageDataDto();
        $messageDataDto->text = $text;
        $messageDataDto->user = $telegramUser;
        $messageDataDto->command = '/start';
        $messageDataDto->typeBtn = 'main_menu';

        TelegramMessage::newMessage($messageDataDto);
    }

    private function checkMessage($message): bool
    {
        if (
            !isset($message["chat"]["username"]) ||
            !isset($message['text']) ||
            $message['text'] == "" ||
            $message['text'] == null
        ) {
            return false;
        }
        return true;
    }
}
