<?php

declare(strict_types=1);

namespace Art\Code\Application\UseCase\Bot;

use Art\Code\Domain\Contract\TelegramGroupRepositoryInterface;
use Art\Code\Domain\Entity\TelegramUser;
use Carbon\Carbon;

class TextUseCase
{
    public function getChangeLoginText(string $username): string
    {
        $txt = "Вы сменили username в Telegram.";
        $txt .= "\n\nВаш новый username перезаписан на @" . $username;

        return $txt;
    }

    public function getGreetingsText(bool $isNewUser): string
    {
        if ($isNewUser) {
            $text = "Привет! Я бот для напоминаний твоих событий.\n";
            $text .= "Давай познакомимся? 😎";
        } else {
            $text = "И снова здравствуйте! 😎";
        }

        return $text;
    }

    public function getGreetingsGroupText(TelegramUser $user): string
    {
        $name = $user->name ?? '';
        $surname = $user->surname ?? '';
        $userLogin = $user->login ?? '';

        $text = "Всем привет! Я бот, который будет уведомлять вас о разных событиях.\n\n";
        $text .= "Подробнее обо мне можно прочитать начав со мной диалог -";
        $text .= "\n@reminders_event_bot";
        $text .= "\nP.S. Меня добавил в чат и настроил события " . $name . " " . $surname . " (@" . $userLogin . "), поэтому тыкайте палочкой его 😎";

        return $text;
    }

    public function getListGroupText($listGroups): string
    {
        if(count($listGroups)>0){
            $text = "<b>Список добавленных групп\n\n</b>";
            foreach ($listGroups as $key => $group){
                $text .= "<b>" . $key + 1 . ".</b> " . $group->name . "\n";
            }
            return $text;
        }
        return "<b>Бот не добавлен в группы.</b>";
    }

    public function getListEventText($listEvents, TelegramGroupRepositoryInterface $groupRepository): string
    {
        if (count($listEvents) > 0) {

            $text = "<b>Список ваших coбытий\n\n</b>";

            foreach ($listEvents as $key => $event) {

                $eventName = $this->getEventNameByType()[$event->type];
                $dateOfEvent = Carbon::parse($event->date_event_at)->format('d.m.Y');
                $periodicity = $this->getPeriodText()[$event->period];

                $groupName = '';
                if($event->group_id === 0){
                    $notificationMethod = 'лично';
                }else{
                    $notificationMethod = 'в группе';
                    $group = $groupRepository->getFirstById((int)$event->group_id);
//                    if($group != null){
//                        $groupName = $group->name;
//                    }

                }

                $text .= "<b>" . $key + 1 . ".</b> " . $eventName . "\n";
                $text .= "    Имя: <i>" . $event->name . "</i>\n";
                $text .= "    Дата: <i>" .  $dateOfEvent . "</i>\n";
                $text .= "    Способ оповещения: <i>" .  $notificationMethod . "</i>\n";
                if($event->group_id !== 0){
                    $text .= "    Группа: <i>" .  $groupName . "</i>\n";
                }
                $text .= "    Время оповещения: <i>" .  $event->notification_time_at . "</i>\n";
                $text .= "    Периодичность: <i>" .  $periodicity . "</i>\n\n";
            }

            $text .= "<b>Для удаления события отправьте номер записи</b>";
            $text .= "\n<b>через слэш</b> <i>(например: /1)</i>";

            return $text;
        }
        return "<b>Ошибка получения ваших событий.</b>";
    }

    public function getPrivateCabinetText(): string
    {
        $text = "<b>🏠 Личный кабинет\n\n</b>";

        return $text;
    }

    public function getSuccessConfirmText(string $type): string
    {
        $text = '';
        if ($type === 'birthday') {
            $text = "<b>🎉 День рождения добавлено!</b>";
        }

        return $text;
    }

    public function getAboutText(): string
    {
        $text = "<b>❔О проекте</b>";

        $text .= "\n\nМы часто забываем про дни рождения, годовщины..";
        $text .= "\nБот создан для уведомления события в чатах, каналах или лично";
        $text .= "\n- исходя от Ваших установок в личном кабинете бота.";

        $text .= "\n\nТеперь все участники группы будут в курсе важного события!";

        $text .= "\n\nФункционал развивается, не судите строго";
        $text .= "\nVersion: 1.0.0";

        $text .= "\n\nДля фидбека и предложений пишите - <a href='https://t.me/artur_timerkhanov'>Создатель</a>";

        return $text;
    }

    public function getWhatCanText(): string
    {
        $text = "<b>❔Что я могу</b>";

        $text .= "\n\n- Напоминать Тебе о дне рождения, событии раз в год";
        $text .= "\nили единожды в указанное время (лично или в указанной группе)";

        $text .= "\n\n- Напоминать Тебе о твоих заметках указаных ранее";

        return $text;
    }

    public function getHowUseText(): string
    {
        $text = "<b>❔Как меня использовать</b>";

        $text .= "\n\n1. Зайдите в личный кабинет.";

        $text .= "\n\n2. Выберите тип уведомлений который хотите добавить.";

        $text .= "\n\n3. Укажите имя человека - если это день рождения. В ином случае имя события, заметки.";

        $text .= "\n\n4. Укажите дату рождения - если это день рождения или дату события.";
        $text .= "\nВ случае заметок укажите дату показа уведомления.";

        $text .= "\n\n5. Укажите как вас уведомлять - лично или в группе";

        $text .= "\n\n6. В случае уведомления в группе - выберите группу из списка (в которые добавлен бот)";

        $text .= "\n\nWell done!";

        return $text;
    }

    private function getEventNameByType(): array
    {
        return [
            'birthday' => 'День рождения',
            'note' => 'Заметка'
        ];
    }

    private function getPeriodText(): array
    {
        return [
            'annually' => 'раз в год',
            'quarterly' => 'раз в квартал',
            'monthly' => 'раз в месяц',
            'weekly' => 'раз в неделю',
            'daily' => 'ежедневно',
            'once' => 'один раз',
        ];
    }
}