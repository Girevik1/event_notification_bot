<?php

declare(strict_types=1);

namespace Art\Code\Application\UseCase\Bot;

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

    public function hook()
    {
        $updates = $this->telegram->getWebhookUpdate();
        $message = $updates->getMessage();

        if (!isset($message['text'])) {
            return 0;
        }
        if ($message['text'] == "" || $message['text'] == null || empty($message['text'])) {
            return 0;
        }
        if (!isset($message["chat"]["username"])) {
            return 0;
        }

        $text = $message["text"];
        $reply_to_message = [];

        $chat_id = $message["chat"]["id"];
        $username = strtolower($message["chat"]["username"]);
        $message_id = $message["message_id"];

        if (isset($message["reply_to_message"])) {
            $reply_to_message = $message["reply_to_message"];
        }

    }
}