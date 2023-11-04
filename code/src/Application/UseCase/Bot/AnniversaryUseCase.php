<?php

declare(strict_types=1);

namespace Art\Code\Application\UseCase\Bot;

class AnniversaryUseCase
{
    public function __construct()
    {
    }

    public function addAnniversary(): void
    {
       // TODO раздел в разработке - новый вид эвента
    }

    public static function getMessagesQueueImportantEvent(): array
    {
        return [
            "NANE_EVENT" => "👶 <b>Укажите имя события</b>",
            "DATE_OF_BIRTH" => "📆 <b>Дату годовщины</b> (формат: 01.01.1970)",
            "NOTIFICATION_TYPE" => "🔊 <b>Как уведомлять?</b>",
            "GROUP" => "👥<b> Укажите номер группы для оповещения</b> (например: 1) \n",
            "TIME_NOTIFICATION" => "⏰  <b>Укажите время оповещения в день рождения</b> (формат: 12:00)",
            "CONFIRMATION" => "<b>‼️ Подтвердите даннные:</b>",
        ];
    }
}