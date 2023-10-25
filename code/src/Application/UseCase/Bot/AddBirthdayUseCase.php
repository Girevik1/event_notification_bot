<?php

declare(strict_types=1);

namespace Art\Code\Application\UseCase\Bot;

use Art\Code\Application\UseCase\Message\QueueMessageUseCase;
use Art\Code\Domain\Contract\QueueMessageRepositoryInterface;
use Art\Code\Domain\Entity\TelegramSender;
use Art\Code\Domain\Entity\TelegramUser;
use Telegram\Bot\Api;
use Telegram\Bot\Exceptions\TelegramSDKException;

class AddBirthdayUseCase
{
    private Api $telegram;
    private TelegramUser $telegramUser;
    public int $message_id;
    private QueueMessageUseCase $queueMessageUseCase;
    private QueueMessageRepositoryInterface $queueMessageRepository;
//    private QueueMessageRepositoryInterface $queueMessageRepository;

    public function __construct(
        Api          $telegram,
        TelegramUser $telegramUser,
        int          $message_id,
        QueueMessageRepositoryInterface $queueMessageRepository
    )
    {
        $this->telegram = $telegram;
        $this->telegramUser = $telegramUser;
        $this->message_id = $message_id;
        $this->queueMessageRepository = $queueMessageRepository;
        $this->queueMessageUseCase = new QueueMessageUseCase($this->queueMessageRepository);


    }

    /**
     * @throws TelegramSDKException
     */
    public function addBirthday(): void
    {
        $queueBirthday = $this->getAllMessageQueue();
        $this->telegram->editMessageText([
            'chat_id' => $this->telegramUser->telegram_chat_id,
            'message_id' => $this->message_id,
            'text' => 'rer',
            'reply_markup' => TelegramSender::getKeyboard('process_set_event'),
            'parse_mode' => 'HTML',
        ]);
        $this->queueMessageUseCase->processQueueMessage($queueBirthday, $this->telegramUser);

        $firsQueueMessage = $this->queueMessageRepository->getFirstOpenMsg($this->telegramUser->id);

        $text = $this->getMessageByType($firsQueueMessage);

        $this->telegram->editMessageText([
            'chat_id' => $this->telegramUser->telegram_chat_id,
            'message_id' => $this->message_id,
            'text' => $text,
            'reply_markup' => TelegramSender::getKeyboard('process_set_event'),
            'parse_mode' => 'HTML',
        ]);
    }

    public function getAllMessageQueue(): array
    {
        return [
            "BIRTHDAY" => "<b>👶 Укажите имя</b>",
            "DATE_OF_BIRTH" => "<b>Дата рождения</b> (формат: 01-01-1970)",
            "GROUP" => "<b>Укажите номер группы для оповещения</b> (например: 1)\n",
            "TIME_NOTIFICATION" => "<b>Укажите время оповещения в день рождения</b> (формат: 12:00)"
        ];
    }

    public function getMessageByType($message): ?string
    {
        $text = "";

        if ($message == null) {
            return null;
        }

        $message_texts = $this->getAllMessageQueue();
        $text .= $message_texts[$message->type];
//        $keyboard = null;
        switch ($message->type) {

            case "GROUP":

//                $text .= self::getRubrics();
                $text .= "\n   (или /cancel для отмены отзыва)";
                break;

//            case "CITY":
//                if ($message->answer != "") {
//                    return null;
//                }
//                $text .= "\n(или /cancel для отмены отзыва)";
//                break;

            default:
                break;
        }

        $message->state = "SENT";
        $message->save();

        return $text;
//        return [
//             'text' => $text,
//             'keyboard' => $keyboard,
//        ];
    }


}