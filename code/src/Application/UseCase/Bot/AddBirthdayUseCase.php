<?php

declare(strict_types=1);

namespace Art\Code\Application\UseCase\Bot;

use Art\Code\Application\UseCase\Message\QueueMessageUseCase;
use Art\Code\Domain\Contract\QueueMessageRepositoryInterface;
use Art\Code\Domain\Entity\TelegramSender;
use Art\Code\Domain\Entity\TelegramUser;
use Art\Code\Domain\Exception\EventNotFoundException;
use Art\Code\Domain\Exception\QueueTypeException;
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

        $this->message_id = $message_id;
        $this->telegram = $telegram;
        $this->telegramUser = $telegramUser;
        $this->queueMessageRepository = $queueMessageRepository;
        $this->queueMessageUseCase = new QueueMessageUseCase($this->queueMessageRepository);
//        $this->telegram->editMessageText([
//            'chat_id' => '500264009',
//            'message_id' => $this->message_id,
//            'text' => 'test',
//            'reply_markup' => TelegramSender::getKeyboard('process_set_event'),
//            'parse_mode' => 'HTML',
//        ]);
    }

    /**
     * @throws TelegramSDKException
     * @throws EventNotFoundException;
     * @throws QueueTypeException;
     */
    public function addBirthday(): void
    {
        $queueBirthday = $this->getMessagesQueueBirthday();

        $this->queueMessageUseCase->processQueueMessage($queueBirthday, $this->telegramUser, 'birthday');

        $firsQueueMessage = $this->queueMessageRepository->getFirstOpenMsg($this->telegramUser->id);


//                $this->telegram->editMessageText([
//            'chat_id' => '500264009',
//            'message_id' => $this->message_id,
//            'text' => $firsQueueMessage->type,
//            'reply_markup' => TelegramSender::getKeyboard('process_set_event'),
//            'parse_mode' => 'HTML',
//        ]);


//        try {
            $text = QueueMessageUseCase::getMessageByType($firsQueueMessage, $this->telegram, $this->message_id);
//        } catch (EventNotFoundException $e) {
//            $this->telegram->sendMessage([
//                'chat_id' => $this->telegramUser->telegram_chat_id,
//                'parse_mode' => 'HTML',
//                'text' => $e->getMessage()
//            ]);
//        } catch (QueueTypeException $e) {
//            $this->telegram->sendMessage([
//                'chat_id' => $this->telegramUser->telegram_chat_id,
//                'parse_mode' => 'HTML',
//                'text' => $e->getMessage()
//            ]);
//        }


        $this->telegram->editMessageText([
            'chat_id' => $this->telegramUser->telegram_chat_id,
            'message_id' => $this->message_id,
            'text' => '333333',
            'reply_markup' => TelegramSender::getKeyboard('process_set_event'),
            'parse_mode' => 'HTML',
        ]);
    }

    public static function getMessagesQueueBirthday(): array
    {
        return [
            "NANE_WHOSE_BIRTHDAY" => "👶 <b>укажите имя</b>",
            "DATE_OF_BIRTH" => "📆 <b>дата рождения</b> (формат: 01-01-1970)",
            "GROUP" => "👥<b> укажите номер группы для оповещения</b> (например: 1) \n",
            "TIME_NOTIFICATION" => "🛎 <b>укажите время оповещения в день рождения</b> (формат: 12:00)",
            "CONFIRMATION" => "<b>Подтвердите даннные:</b>"
        ];
    }




}