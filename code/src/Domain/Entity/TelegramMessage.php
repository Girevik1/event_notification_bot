<?php

declare(strict_types=1);

namespace Art\Code\Domain\Entity;

use Art\Code\Domain\Dto\MessageSendDto;
use Art\Code\Domain\Dto\TelegramMessageDto;
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
                $msg_id = $messageDataDto->telegram::sendMessage($messageDataDto->chat_id, $textItem, $messageDataDto->type_btn);

                $telegramMessage = new TelegramMessageDto();
                $telegramMessage->chat_id = $messageDataDto->chat_id;
                $telegramMessage->message_id = $msg_id;
                $telegramMessage->text = $textItem;
                $telegramMessage->command = $messageDataDto->command;
                $telegramMessage->reply_to = count($messageDataDto->reply_to_message) > 0 ?
                    $messageDataDto->reply_to_message['message_id'] : 0;

                $messageDataDto->telegramMessageRepository->create($telegramMessage);
            }
        }
    }
}