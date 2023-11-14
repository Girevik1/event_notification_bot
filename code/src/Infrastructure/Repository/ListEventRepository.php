<?php

declare(strict_types=1);

namespace Art\Code\Infrastructure\Repository;

use Art\Code\Domain\Contract\ListEventRepositoryInterface;
use Art\Code\Domain\Dto\ListEventDto;
use Art\Code\Domain\Entity\ListEvent;
use Illuminate\Database\Eloquent\Collection;

class ListEventRepository implements ListEventRepositoryInterface
{
    /**
     * @param ListEventDto $listEventDto
     * @return ListEvent
     */
    public function create(ListEventDto $listEventDto): ListEvent
    {
        return ListEvent::create([
            'name' => $listEventDto->name,
            'date_event_at' => $listEventDto->date_event_at,
            'type' => $listEventDto->type,
            'group_id' => $listEventDto->group_id,
            'telegram_user_id' => $listEventDto->telegram_user_id,
            'notification_time_at' => $listEventDto->notification_time_at,
            'period' => $listEventDto->period
        ]);
    }

    /**
     * @param int $userId
     * @return Collection
     */
    public function getListByUser(int $userId): Collection
    {
        return ListEvent::where('telegram_user_id', '=', $userId)
            ->orderBy('id','desc')
            ->get();
    }

    /**
     * @param int $id
     * @param int $userId
     * @return mixed
     */
    public function deleteEventById(int $id, int $userId): int
    {
        return ListEvent::where('id', $id)
            ->where('telegram_user_id', $userId)
            ->delete();
    }

    /**
     * @param int $groupId
     * @param int $userId
     * @param string $field
     * @param mixed $value
     * @return int
     */
    public function updateAllByGroup(int $groupId, int $userId, string $field, mixed $value): int
    {
        return ListEvent::where('group_id', $groupId)
            ->where('telegram_user_id', $userId)
            ->update([$field => $value]);
    }

    public function findEventsToday($month, $day, $notificationTime): Collection
    {
        return ListEvent::where('type', 'birthday')
            ->whereMonth('date_event_at', $month)
            ->whereDay('date_event_at', $day)
            ->where('notification_time_at', $notificationTime)
            ->get();
    }
}