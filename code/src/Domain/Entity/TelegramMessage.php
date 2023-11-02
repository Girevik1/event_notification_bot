<?php

declare(strict_types=1);

namespace Art\Code\Domain\Entity;

use Art\Code\Domain\Dto\MessageSendDto;
use Illuminate\Database\Eloquent\Model;
use Telegram\Bot\Exceptions\TelegramSDKException;

class TelegramMessage extends Model
{
    protected $table = 'telegram_message';
    protected $guarded = [];

    /**
     * @param MessageSendDto $messageDataDto
     * @return void
     * @throws TelegramSDKException
     */
    public static function newMessage(MessageSendDto $messageDataDto): void
    {
        $textArray = [$messageDataDto->text];

        if (mb_strlen($messageDataDto->text, '8bit') > 4096) {
            $textArray = [];
            $start = 0;
            do {
                $textArray[] = mb_strcut($messageDataDto->text, $start, 4096);
                $start += 4096;
            } while (mb_strlen($messageDataDto->text, '8bit') > $start);
        }

        foreach ($textArray as $textItem) {
            if (
                $_ENV['APP_ENV'] === 'prod' ||
                $_ENV['APP_ENV'] === 'dev'
            ) {
                $msg_id = TelegramSender::sendMessage($messageDataDto->chat_id, $textItem, $messageDataDto->type_btn);

                $message = new TelegramMessage();
                $message->chat_id = $messageDataDto->chat_id;
                $message->message_id = $msg_id;
                $message->text = $textItem;
                $message->command = $messageDataDto->command;

                if (count($messageDataDto->reply_to_message) > 0) {
                    $message->reply_to = $messageDataDto->reply_to_message['message_id'];
                } else {
                    $message->reply_to = 0;
                }
                $message->save();
            }

        }
    }
}