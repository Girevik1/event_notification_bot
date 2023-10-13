<?php

declare(strict_types=1);

namespace Art\Code\Application\UseCase\Bot;

use Art\Code\Infrastructure\Http\Controllers\BotController;
use Art\Code\Infrastructure\Repository\TelegramMessageRepository;
use Art\Code\Infrastructure\Repository\TelegramUserRepository;
use Telegram\Bot\Api;
use Telegram\Bot\Exceptions\TelegramSDKException;

class BotUseCase
{
    private Api $telegram;

    /**
     * @throws TelegramSDKException
     */
    public function __construct()
    {
        $this->telegram = new Api($_ENV['TELEGRAM_KEY']);
    }

    /**
     * For test hook bot
     */
    public static function testStart()
    {
        $message = [
            "chat" => [
                "id" => 12345678901,

//                "username" => "",
                "username" => "test"
            ],
            "text" => "/start",
            "message_id" => 100
        ];
        $t = new BotUseCase();
        $t->hook($message);
    }

    public static function testHook()
    {
        $message = [
            "chat" => [
                "id" => 12345678901,

                "username" => "test"
            ],
            "text" => "/confidential",
            "message_id" => 100
        ];
        $t = new BotUseCase();
        $t->hook($message);
    }

    public static function testA1()
    {
        $message = [
            "chat" => [
                "id" => 12345678901,

                "username" => "test"
            ],
            "text" => "/c1",
            "message_id" => 100
        ];
        $t = new BotUseCase();
        $t->hook($message);
    }

    public static function testA($text = '')
    {
        $message = [
            "chat" => [
                "id" => 12345678901,

                "username" => "test"
            ],
            "text" => 'Торговля',
            "message_id" => 100
        ];
        $t = new BotUseCase();
        $t->hook($message);
    } //

    public function hook()
    {
        $message = '';

        if ($_ENV['APP_URL'] === 'prod') {
            $updates = $this->telegram->getWebhookUpdate();
            $message = $updates->getMessage();
        }

//        $message = [
//            "chat" => [
//                "id" => 12345678901,
//
//                "username" => "test"
//            ],
//            "text" => "/c1",
//            "message_id" => 100
//        ];

        if (!$this->checkMessage($message)){
            return 'check data msg!';
        };

//        $testUser = Manager::table('telegram_user')->where('id', '=', 1)->get();
//        $results = Manager::select('select * from telegram_user where id = ?', [1]);
//        (new TelegramUserRepository())->create();


        (new TelegramUserRepository)->create();
        (new TelegramMessageRepository())->create($message);

        return 'ok test';
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

        $text = $message["text"];
        $reply_to_message = [];

        $chat_id = $message["chat"]["id"];
        $username = strtolower($message["chat"]["username"]);
        $message_id = $message["message_id"];

        if (isset($message["reply_to_message"])) {
            $reply_to_message = $message["reply_to_message"];
        }

    }

    private function checkMessage($message): bool
    {
//        if (!isset($message['text'])) return false;
//        if ($message['text'] == "") return false;
//        if ($message['text'] == null) return false;
//        if (empty($message['text'])) return false;
//
//        return true;

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