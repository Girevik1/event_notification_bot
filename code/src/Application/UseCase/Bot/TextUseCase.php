<?php

declare(strict_types=1);

namespace Art\Code\Application\UseCase\Bot;

use Art\Code\Domain\Contract\TelegramGroupRepositoryInterface;
use Art\Code\Domain\Entity\TelegramUser;
use Carbon\Carbon;
use Illuminate\Database\Eloquent\Collection;

class TextUseCase
{
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

    public function getListGroupText(Collection $listGroups): string
    {
        if(count($listGroups) > 0){

            $text = "📋 <b>Список добавленных групп\n\n</b>";

            foreach ($listGroups as $group){
                $text .= "<b>" . $group->id . ".</b> " . $group->name . "\n";
            }

            $text .= "\n<b>Для удаления - отправьте номер группы после слова <i>group</i></b>";
            $text .= "\n<i>(например: group 1)</i>";
            $text .= "\n‼️<b>Учтите после удаления - бот выйдет из группы и все закрепленные за группой события - будет оповещать лично</b>";

            return $text;
        }
        return "<b>Бот не добавлен в группы.</b>";
    }

    public function getListEventText(Collection $listEvents, TelegramGroupRepositoryInterface $groupRepository, string $userChatId): string
    {
        if (count($listEvents) > 0) {

            $text = "📝 <b>Список ваших coбытий\n\n</b>";

            foreach ($listEvents as $event) {

                $eventName = $this->getEventNameByType()[$event->type];
                $dateOfEvent = Carbon::parse($event->date_event_at)->format('d.m.Y');
                $periodicity = $this->getPeriodText()[$event->period];

                $groupName = '';
                if($event->group_id === 0){
                    $notificationMethod = 'лично';
                }else{
                    $notificationMethod = 'в группе';
                    $group = $groupRepository->getFirstById($event->group_id, $userChatId);
                    $groupName = $group->name ?? 'группа не найдена!';
                }

                $text .= "<b>" . $event->id . ".</b> " . $eventName . "\n";
                $text .= "    Имя: <i>" . $event->name . "</i>\n";
                $text .= "    Дата: <i>" .  $dateOfEvent . "</i>\n";
                $text .= "    Способ оповещения: <i>" .  $notificationMethod . "</i>\n";
                if($event->group_id !== 0){
                    $text .= "    Группа: <i>" .  $groupName . "</i>\n";
                }
                $text .= "    Время оповещения: <i>" .  $event->notification_time_at . "</i>\n";
                $text .= "    Периодичность: <i>" .  $periodicity . "</i>\n\n";
            }

            $text .= "<b>Для удаления события отправьте номер записи после слова <i>event</i></b>";
            $text .= "\n<i>(например: event 1)</i>";

            return $text;
        }
        return "<b>У вас нет добавленных событий.</b>";
    }

    public function getPrivateCabinetText(): string
    {
        return "<b>🏠 Личный кабинет\n\n</b>";
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
        $text .= "\nБот создан для уведомления события в чатах, каналах или лично,";
        $text .= "\nисходя от ваших установок в личном кабинете бота.";

        $text .= "\n\nТеперь все участники группы будут в курсе важного события!";

        $text .= "\n\nФункционал развивается, не судите строго 😎";
        $text .= "\nVersion: 1.0.0";

        $text .= "\n\nДля фидбека и предложений пишите - <a href='https://t.me/artur_timerkhanov'>Создатель</a>";

        return $text;
    }

    public function getWhatCanText(): string
    {
        $text = "<b>❔Что я могу</b>";

        $text .= "\n\n- Напоминать Тебе о дне рождения, событии раз в год";
        $text .= "\nили единожды в указанное время <i>(лично или в указанной группе)</i>";

        $text .= "\n\n- Напоминать Тебе о твоих заметках указаных ранее";

        return $text;
    }

    public function getHowUseText(): string
    {
        $text = "<b>❔Как меня использовать</b>";

        $text .= "\n\n<b>1.</b>  Зайдите в личный кабинет.";

        $text .= "\n\n<b>2.</b>  Выберите тип уведомлений который хотите добавить.";

        $text .= "\n\n<b>3.</b>  Укажите имя человека - если это день рождения.";
        $text .= "\n      В ином случае имя события, заметки.";

        $text .= "\n\n<b>4.</b>  Укажите дату рождения - если это день рождения или дату события.";
        $text .= "\n      В случае заметок укажите дату показа уведомления.";

        $text .= "\n\n<b>5.</b>  Укажите как вас уведомлять - лично или в группе.";

        $text .= "\n\n<b>6.</b>  В случае уведомления в группе - выберите группу из списка.";
        $text .= "\n      <i>(в которые добавлен бот)</i>";

        $text .= "\n\n      <b>Well done!</b>";

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