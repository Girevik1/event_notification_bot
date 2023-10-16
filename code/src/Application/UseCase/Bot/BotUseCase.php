<?php

declare(strict_types=1);

namespace Art\Code\Application\UseCase\Bot;

use Art\Code\Application\Dto\TelegramUserDto;
use Art\Code\Domain\Entity\TelegramMessage;
use Telegram\Bot\Api;
use Telegram\Bot\Exceptions\TelegramSDKException;

class BotUseCase
{
    private Api $telegram;
    public array $newRequest;
    /**
     * @throws TelegramSDKException
     */
    public function __construct(
//        private readonly TelegramUserRepositoryInterface    $telegramUserRepository,
//        private readonly TelegramMessageRepositoryInterface $telegramMessageRepositor
        private $telegramUserRepository,
        private $telegramMessageRepository
    )
    {
//        $this->newRequest = json_decode(file_get_contents("php://input"), true); // for test
        $this->telegram = new Api($_ENV['TELEGRAM_KEY']);
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

//        $message = [
//            "chat" => [
//                "id" => 12345678901,
//                "username" => "test"
//            ],
//            "text" => "/start",
//            "message_id" => 100
//        ];

//        $message = $this->newRequest;


        if (!$this->checkMessage($message)) {
            return 'check data msg!';
        };
        echo 111;
        $text = $message["text"];
        $reply_to_message = [];

        $chat_id = $message["chat"]["id"];
        $username = strtolower($message["chat"]["username"]);
        $message_id = $message["message_id"];

        if (isset($message["reply_to_message"])) {
            $reply_to_message = $message["reply_to_message"];
        }

        $user = $this->telegramUserRepository->firstByChatId($chat_id);

        if ($user) {
            $was_message = false;
            if ($user->login != $username) {
                $user->login = strtolower($username);
                $user->save();
                $txt = "Вы сменили username в Telegram.";
                $txt .= "\n\nВаш новый username перезаписан на @" . $username;
                $txt .= "\nВаш логин в систему теперь " . strtolower($username);
                TelegramMessage::newMessage($user, $txt, '/change-username');
                $was_message = true;
            }
        }

        switch ($text) {
            case "/start":
                if ($user) {

                    $txt = 'Ваши настройки бота';
                    TelegramMessage::newMessage($user, $txt, '/settings');
                }else{
                    $result = $this->start(new TelegramUserDto($message));

                    TelegramMessage::newMessage($result['telegram_user'], $result['text'], '/start');
                }
                $command = $text;
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
//            case "/prepayment":
//                $answer = $this->prepayment($user);
//                $command = $text;
//                break;
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

    private function start(TelegramUserDto $telegramUserDto): array
    {
//        $user = User::where('login', $telegramUserDto->username)->first();
//        if ($this->telegramUserRepository->isExistByLogin($telegramUserDto->username)) {
//            return "Вы уже запустили бота!";
//        }
        $telegramUser = $this->telegramUserRepository->create($telegramUserDto);
//            $user->telegram_chat_id = $telegramUserDto->chat_id;
//            $user->save();
        return [
            'text' => "Успех, теперь Вы можете начать авторизацию",
            'telegram_user' => $telegramUser
        ];
//        } else {
//            return "@" . $username . " не зарегестрирован в системе или неправильно указан Telegram login.\n\n Обратитесь к администратору.";
//        }
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
