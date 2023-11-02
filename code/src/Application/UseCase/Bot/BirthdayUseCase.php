<?php

declare(strict_types=1);

namespace Art\Code\Application\UseCase\Bot;

use Art\Code\Application\UseCase\Message\QueueMessageUseCase;
use Art\Code\Domain\Dto\BotRequestDto;
use Art\Code\Domain\Dto\MessageSendDto;
use Art\Code\Domain\Entity\ListEvent;
use Art\Code\Domain\Entity\TelegramMessage;
use Art\Code\Domain\Entity\TelegramSender;
use Carbon\Carbon;
use Exception;
use Telegram\Bot\Exceptions\TelegramSDKException;

class BirthdayUseCase
{
    private QueueMessageUseCase $queueMessageUseCase;

    public function __construct(public BotRequestDto $botRequestDto)
    {
        $this->queueMessageUseCase = new QueueMessageUseCase($this->botRequestDto->queueMessageRepository);
    }

    /**
     * @throws Exception;
     */
    public function addBirthday(): void
    {
        $queueBirthday = $this->getMessagesQueueBirthday();

        $this->queueMessageUseCase->processQueueMessage(
            $queueBirthday,
            $this->botRequestDto->telegramUser,
            $this->botRequestDto->messageId,
            'birthday'
        );

        $firstQueueMessage = $this->botRequestDto->queueMessageRepository->getFirstOpenMsg($this->botRequestDto->telegramUser->id);

        $text = QueueMessageUseCase::getMessageByType($firstQueueMessage, $this->botRequestDto->queueMessageRepository);

        $this->botRequestDto->telegram->editMessageText([
            'chat_id' => $this->botRequestDto->telegramUser->telegram_chat_id,
            'message_id' => $this->botRequestDto->messageId,
            'text' => $text,
            'reply_markup' => TelegramSender::getKeyboard('process_set_event'),
            'parse_mode' => 'HTML',
        ]);
    }

    public static function getMessagesQueueBirthday(): array
    {
        return [
            "NANE_WHOSE_BIRTHDAY" => "👶 <b>Укажите имя именинника(цы)</b>",
            "DATE_OF_BIRTH" => "📆 <b>Дата рождения</b> (формат: 01.01.1970)",
            "NOTIFICATION_TYPE" => "🔊 <b>Как уведомлять?</b>",
            "GROUP" => "👥 <b>Укажите номер группы для оповещения</b> (например: 1) \n",
            "TIME_NOTIFICATION" => "⏰  <b>Укажите время оповещения в день рождения</b> (формат: 12:00)",
            "CONFIRMATION" => "<b>‼️ Подтвердите даннные:</b>",
        ];
    }

    /**
     * @throws TelegramSDKException
     */
    public static  function checkBirthdayByCron(BotRequestDto $botRequestDto): void
    {
        $now = Carbon::now()->addHours(3);

        $listBirthdayEvents = ListEvent::where('type', 'birthday')
            ->whereMonth('date_event_at', $now->format('m'))
            ->whereDay('date_event_at', $now->format('d'))
            ->where('notification_time_at', $now->format('H:i'))
            ->get();

        foreach ($listBirthdayEvents as $event) {

            $telegramUser = $botRequestDto->telegramUserRepository->firstById($event->telegram_user_id);

            if($event->group_id === 0){
                $chat_id = $telegramUser->telegram_chat_id;
            }else{
                $group = $botRequestDto->telegramGroupRepository->getFirstById($event->group_id, $telegramUser->telegram_chat_id);
                $chat_id = $group->group_chat_id;
            }

            $dateOfBirth = Carbon::parse($event->date_event_at);
            $diffYears = $dateOfBirth->diffInYears($now);
            $correctFormat = self::yearTextArg($diffYears);

            $messageSendDto = new MessageSendDto();
            $messageSendDto->text = "🎂<b>Сегодня день рождения</b>!";
            $messageSendDto->text .= "\n\n     " . $event->name . " <b>" . $diffYears . " " . $correctFormat . "!</b>";
            $messageSendDto->chat_id = $chat_id;
            $messageSendDto->command = 'cron_birthday';

            TelegramMessage::newMessage($messageSendDto);
        }
    }

    private static function yearTextArg($year): string
    {
        $year = abs($year);
        $t1 = $year % 10;
        $t2 = $year % 100;

        return ($t1 == 1 && $t2 != 11 ? "год" : ($t1 >= 2 && $t1 <= 4 && ($t2 < 10 || $t2 >= 20) ? "года" : "лет"));
    }
}