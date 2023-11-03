<?php

declare(strict_types=1);

namespace Art\Code\Application\UseCase\Bot;

use Art\Code\Application\UseCase\Message\QueueMessageUseCase;
use Art\Code\Domain\Dto\BotRequestDto;
use Art\Code\Domain\Dto\DataEditMessageDto;
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

        $dataEditMessageDto = new DataEditMessageDto();
        $dataEditMessageDto->message_id = $this->botRequestDto->messageId;
        $dataEditMessageDto->chat_id = $this->botRequestDto->telegramUser->telegram_chat_id;
        $dataEditMessageDto->text = $text;
        $dataEditMessageDto->keyboard = 'process_set_event';
        $this->botRequestDto->telegram::editMessageTextSend($dataEditMessageDto);
//            [s
//            'chat_id' => $this->botRequestDto->telegramUser->telegram_chat_id,
//            'message_id' => $this->botRequestDto->messageId,
//            'text' => $text,
//            'reply_markup' => $this->botRequestDto->telegram::getKeyboard('process_set_event'),
////            'reply_markup' => TelegramSender::getKeyboard('process_set_event'),
//            'parse_mode' => 'HTML',
//        ]
//        );
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
    public static function checkBirthdayByCron(BotRequestDto $botRequestDto): void
    {
        $now = Carbon::now()->addHours(3);

        $listBirthdayEvents = ListEvent::where('type', 'birthday')
            ->whereMonth('date_event_at', $now->format('m'))
            ->whereDay('date_event_at', $now->format('d'))
            ->where('notification_time_at', $now->format('H:i'))
            ->get();

        foreach ($listBirthdayEvents as $event) {

            $telegramUser = $botRequestDto->telegramUserRepository->firstById($event->telegram_user_id);

            if ($event->group_id === 0) {
                $chat_id = $telegramUser->telegram_chat_id;
            } else {
                $group = $botRequestDto->telegramGroupRepository->getFirstById(
                    $event->group_id,
                    $telegramUser->telegram_chat_id
                );
                $chat_id = $group->group_chat_id;
            }

            $dateOfBirth = Carbon::parse($event->date_event_at);
            $diffYears = $dateOfBirth->diffInYears($now);
            $correctFormat = self::yearTextArg($diffYears);
            $zodiac = self::getZodiacalSign($dateOfBirth->format('m'), $dateOfBirth->format('d'));
            $onEasternCalendar = self::getOnEasternCalendar((int)$dateOfBirth->format('Y'));

            $messageSendDto = new MessageSendDto();
            $messageSendDto->text = "🎂<b>Сегодня день рождения</b>!";
            $messageSendDto->text .= "\n\n     " . $event->name . " <b>" . $diffYears . " " . $correctFormat . "!</b>";
            $messageSendDto->text .= "\n\n     Год рождения: <b>" . $dateOfBirth->format('Y') . "г.</b>";
            $messageSendDto->text .= "\n     Знак задиака: <b>" . $zodiac . "</b>";
            $messageSendDto->text .= "\n     По восточному календарю: <b>" . $onEasternCalendar . "</b>";
            $messageSendDto->chat_id = $chat_id;
            $messageSendDto->command = 'cron_birthday';
            $messageSendDto->telegramMessageRepository = $botRequestDto->telegramMessageRepository;

            TelegramMessage::newMessage($messageSendDto);
        }
    }

    /**
     * @param $year
     * @return string
     */
    private static function yearTextArg($year): string
    {
        $year = abs($year);
        $t1 = $year % 10;
        $t2 = $year % 100;

        return ($t1 == 1 && $t2 != 11 ? "год" : ($t1 >= 2 && $t1 <= 4 && ($t2 < 10 || $t2 >= 20) ? "года" : "лет"));
    }

    /**
     * @param $month
     * @param $day
     * @return string
     */
    private static function getZodiacalSign($month, $day): string
    {
        $signs = ["Козерог", "Водолей", "Рыбы", "Овен", "Телец", "Близнецы", "Рак", "Лев", "Девы", "Весы", "Скорпион", "Стрелец"];
        $signsStart = [1 => 21, 2 => 20, 3 => 20, 4 => 20, 5 => 20, 6 => 20, 7 => 21, 8 => 22, 9 => 23, 10 => 23, 11 => 23, 12 => 23];

        return $day < $signsStart[$month + 1] ? $signs[$month - 1] : $signs[$month % 12];
    }

    /**
     * @param int $needYear
     * @return string
     */
    private static function getOnEasternCalendar(int $needYear): string
    {
        $zodiac = [
            "1" => "Год крысы",
            "2" => "Год коровы",
            "3" => "Год тигра",
            "4" => "Год зайца",
            "5" => "Год дракона",
            "6" => "Год змеи",
            "7" => "Год лошади",
            "8" => "Год овцы",
            "9" => "Год обезьяны",
            "10" => "Год петуха",
            "11" => "Год собаки",
            "12" => "Год свиньи"
        ];

        $start_year = 1900;
        $start_zodiac = 1;

        $sign = '';

        while (!($start_year > $needYear)) {
            $start_year++;
            $sign = $zodiac[$start_zodiac++];
            if ($start_zodiac == 13) $start_zodiac = 1;
        }

        return $sign;
    }
}